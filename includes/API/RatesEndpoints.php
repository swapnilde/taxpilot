<?php
/**
 * Rates REST API endpoints.
 *
 * @package TaxPilot\API
 */

declare( strict_types=1 );

namespace TaxPilot\API;

use TaxPilot\Database\RatesTable;
use TaxPilot\Services\TaxRateService;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST endpoints for tax rate CRUD operations.
 */
class RatesEndpoints extends WP_REST_Controller {

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
		// Get all rates.
		register_rest_route(
			$this->namespace,
			'/rates',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_rates' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'country'   => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'tax_class' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'limit'     => [
						'type'    => 'integer',
						'default' => 100,
					],
					'offset'    => [
						'type'    => 'integer',
						'default' => 0,
					],
				],
			]
		);

		// Refresh rates from API.
		register_rest_route(
			$this->namespace,
			'/rates/refresh',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'refresh_rates' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		// Delete a rate.
		register_rest_route(
			$this->namespace,
			'/rates/(?P<id>\d+)',
			[
				'methods'             => 'DELETE',
				'callback'            => [ $this, 'delete_rate' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'validate_callback' => fn( $value ) => is_numeric( $value ) && $value > 0,
					],
				],
			]
		);

		// Get rate stats.
		register_rest_route(
			$this->namespace,
			'/rates/stats',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_stats' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);
	}

	/**
	 * Permission check.
	 */
	public function check_permissions(): bool {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Get rates.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function get_rates( WP_REST_Request $request ): WP_REST_Response {
		$args = [];

		$country = $request->get_param( 'country' );
		if ( $country ) {
			$rates = RatesTable::get_by_country( strtoupper( $country ) );
		} else {
			if ( $request->get_param( 'tax_class' ) ) {
				$args['tax_class'] = $request->get_param( 'tax_class' );
			}
			$args['limit']  = $request->get_param( 'limit' );
			$args['offset'] = $request->get_param( 'offset' );

			$rates = RatesTable::get_all( $args );
		}

		return new WP_REST_Response(
			[
				'rates' => $rates,
				'total' => RatesTable::count(),
			],
			200
		);
	}

	/**
	 * Force refresh rates from the configured provider.
	 */
	public function refresh_rates(): WP_REST_Response {
		$settings  = get_option( 'taxpilot_settings', [] );
		$countries = $settings['target_countries'] ?? [];

		if ( empty( $countries ) ) {
			return new WP_REST_Response( [ 'error' => 'No target countries configured.' ], 400 );
		}

		$service = new TaxRateService();
		$rates   = $service->refresh_rates( $countries );

		return new WP_REST_Response(
			[
				'success' => true,
				'count'   => count( $rates ),
				'rates'   => $rates,
			],
			200
		);
	}

	/**
	 * Delete a rate.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function delete_rate( WP_REST_Request $request ): WP_REST_Response {
		$id      = (int) $request->get_param( 'id' );
		$deleted = RatesTable::delete( $id );

		if ( ! $deleted ) {
			return new WP_REST_Response( [ 'error' => 'Rate not found.' ], 404 );
		}

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * Get rate statistics for the dashboard.
	 */
	public function get_stats(): WP_REST_Response {
		$total = RatesTable::count();
		$rates = RatesTable::get_all( [ 'limit' => 500 ] );

		$countries   = [];
		$sources     = [
			'static' => 0,
			'api'    => 0,
		];
		$last_update = null;

		foreach ( $rates as $rate ) {
			$countries[ $rate['country_code'] ] = true;
			$source_key                         = 'static' === $rate['source'] ? 'static' : 'api';
			++$sources[ $source_key ];

			if ( null === $last_update || $rate['updated_at'] > $last_update ) {
				$last_update = $rate['updated_at'];
			}
		}

		return new WP_REST_Response(
			[
				'total_rates'     => $total,
				'total_countries' => count( $countries ),
				'sources'         => $sources,
				'last_update'     => $last_update,
			],
			200
		);
	}
}
