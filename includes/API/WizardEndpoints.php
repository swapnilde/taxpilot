<?php
/**
 * Wizard REST API endpoints.
 *
 * @package TaxPilot\API
 */

declare( strict_types=1 );

namespace TaxPilot\API;

use TaxPilot\Database\LogsTable;
use TaxPilot\Services\TaxRateService;
use TaxPilot\Services\WooTaxSync;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * REST endpoints for the onboarding wizard steps.
 */
class WizardEndpoints extends WP_REST_Controller {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'taxpilot/v1';

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		// Step 1: Store setup.
		register_rest_route(
			$this->namespace,
			'/wizard/store-setup',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'save_store_setup' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'country'  => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'currency' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		// Step 1: Get current store setup.
		register_rest_route(
			$this->namespace,
			'/wizard/store-setup',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_store_setup' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		// Step 2: Product types.
		register_rest_route(
			$this->namespace,
			'/wizard/product-types',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'save_product_types' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'product_types' => [
						'required' => true,
						'type'     => 'array',
					],
				],
			]
		);

		// Step 3: Target countries.
		register_rest_route(
			$this->namespace,
			'/wizard/target-countries',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'save_target_countries' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'countries' => [
						'required' => true,
						'type'     => 'array',
					],
				],
			]
		);

		// Step 4: Preview rates.
		register_rest_route(
			$this->namespace,
			'/wizard/preview-rates',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'preview_rates' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		// Step 5: Apply rates.
		register_rest_route(
			$this->namespace,
			'/wizard/apply-rates',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'apply_rates' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		// Wizard state.
		register_rest_route(
			$this->namespace,
			'/wizard/state',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_wizard_state' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);
	}

	/**
	 * Permission check — requires manage_woocommerce capability.
	 */
	public function check_permissions(): bool {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Step 1 GET: Get current store setup.
	 */
	public function get_store_setup(): WP_REST_Response {
		$settings = get_option( 'taxpilot_settings', [] );

		// Auto-detect from WooCommerce if not set.
		$country  = $settings['base_country'] ?? '';
		$currency = $settings['base_currency'] ?? '';

		if ( empty( $country ) && function_exists( 'WC' ) ) {
			$wc_country = get_option( 'woocommerce_default_country', '' );
			$parts      = explode( ':', $wc_country );
			$country    = $parts[0] ?? '';
		}

		if ( empty( $currency ) && function_exists( 'get_woocommerce_currency' ) ) {
			$currency = get_woocommerce_currency();
		}

		return new WP_REST_Response(
			[
				'country'  => $country,
				'currency' => $currency,
			],
			200
		);
	}

	/**
	 * Step 1 POST: Save store setup.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function save_store_setup( WP_REST_Request $request ): WP_REST_Response {
		$country  = $request->get_param( 'country' );
		$currency = $request->get_param( 'currency' );

		$settings                  = get_option( 'taxpilot_settings', [] );
		$settings['base_country']  = strtoupper( $country );
		$settings['base_currency'] = strtoupper( $currency );
		update_option( 'taxpilot_settings', $settings );

		// Also update WooCommerce settings.
		update_option( 'woocommerce_default_country', strtoupper( $country ) );
		update_option( 'woocommerce_currency', strtoupper( $currency ) );

		LogsTable::insert(
			'wizard_store_setup',
			wp_json_encode(
				[
					'country'  => $country,
					'currency' => $currency,
				]
			)
		);

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * Step 2: Save product types and create tax classes.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function save_product_types( WP_REST_Request $request ): WP_REST_Response {
		$types = $request->get_param( 'product_types' );

		if ( ! is_array( $types ) ) {
			return new WP_REST_Response( [ 'error' => 'Invalid product types.' ], 400 );
		}

		$allowed_types = [ 'physical', 'digital', 'services' ];
		$types         = array_intersect( $types, $allowed_types );

		$settings                  = get_option( 'taxpilot_settings', [] );
		$settings['product_types'] = array_values( $types );
		update_option( 'taxpilot_settings', $settings );

		// Create corresponding WooCommerce tax classes.
		$tax_class_map = [
			'physical' => 'Standard',
			'digital'  => 'Digital Goods',
			'services' => 'Services',
		];

		$created_classes = [];
		foreach ( $types as $type ) {
			if ( isset( $tax_class_map[ $type ] ) ) {
				$class_name = $tax_class_map[ $type ];
				\WC_Tax::create_tax_class( $class_name );
				$created_classes[] = $class_name;
			}
		}

		LogsTable::insert(
			'wizard_product_types',
			wp_json_encode(
				[
					'types'   => $types,
					'classes' => $created_classes,
				]
			)
		);

		return new WP_REST_Response(
			[
				'success'     => true,
				'tax_classes' => $created_classes,
			],
			200
		);
	}

	/**
	 * Step 3: Save target countries.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function save_target_countries( WP_REST_Request $request ): WP_REST_Response {
		$countries = $request->get_param( 'countries' );

		if ( ! is_array( $countries ) ) {
			return new WP_REST_Response( [ 'error' => 'Invalid countries list.' ], 400 );
		}

		// Sanitize — only allow 2-letter country codes.
		$countries = array_filter(
			$countries,
			fn( $code ) => preg_match( '/^[A-Z]{2}$/', strtoupper( $code ) )
		);
		$countries = array_map( 'strtoupper', $countries );

		$settings                     = get_option( 'taxpilot_settings', [] );
		$settings['target_countries'] = array_values( $countries );
		update_option( 'taxpilot_settings', $settings );

		LogsTable::insert( 'wizard_target_countries', wp_json_encode( [ 'countries' => $countries ] ) );

		return new WP_REST_Response(
			[
				'success'   => true,
				'countries' => $countries,
			],
			200
		);
	}

	/**
	 * Step 4: Preview rates for selected countries.
	 */
	public function preview_rates(): WP_REST_Response {
		$settings  = get_option( 'taxpilot_settings', [] );
		$countries = $settings['target_countries'] ?? [];

		if ( empty( $countries ) ) {
			return new WP_REST_Response( [ 'error' => 'No target countries selected.' ], 400 );
		}

		$service = new TaxRateService();
		$rates   = $service->get_rates_for_countries( $countries );

		return new WP_REST_Response(
			[
				'rates'  => $rates,
				'source' => $settings['api_provider'] ?? 'static',
				'count'  => count( $rates ),
			],
			200
		);
	}

	/**
	 * Step 5: Apply rates to WooCommerce.
	 */
	public function apply_rates(): WP_REST_Response {
		$settings  = get_option( 'taxpilot_settings', [] );
		$countries = $settings['target_countries'] ?? [];

		if ( empty( $countries ) ) {
			return new WP_REST_Response( [ 'error' => 'No target countries selected.' ], 400 );
		}

		$service = new TaxRateService();
		$rates   = $service->get_rates_for_countries( $countries );

		$sync   = new WooTaxSync();
		$result = $sync->apply_rates( $rates );

		// Mark wizard as completed.
		$settings['wizard_completed'] = true;
		update_option( 'taxpilot_settings', $settings );

		LogsTable::insert(
			'wizard_apply_rates',
			wp_json_encode(
				[
					'countries'     => $countries,
					'rates_count'   => count( $rates ),
					'applied_count' => $result['applied'],
				]
			)
		);

		return new WP_REST_Response(
			[
				'success' => true,
				'applied' => $result['applied'],
				'errors'  => $result['errors'],
			],
			200
		);
	}

	/**
	 * Get current wizard state (for resuming).
	 */
	public function get_wizard_state(): WP_REST_Response {
		$settings = get_option( 'taxpilot_settings', [] );

		return new WP_REST_Response(
			[
				'base_country'     => $settings['base_country'] ?? '',
				'base_currency'    => $settings['base_currency'] ?? '',
				'product_types'    => $settings['product_types'] ?? [],
				'target_countries' => $settings['target_countries'] ?? [],
				'wizard_completed' => $settings['wizard_completed'] ?? false,
			],
			200
		);
	}
}
