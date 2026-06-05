<?php
/**
 * WooCommerce Settings tab integration.
 *
 * @package TaxZen\Admin
 */

declare( strict_types=1 );

namespace TaxZen\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a TaxZen tab under WooCommerce → Settings.
 */
class SettingsPage {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_settings_tabs_array', [ $this, 'add_settings_tab' ], 50 );
		add_action( 'woocommerce_settings_tabs_taxzen', [ $this, 'output_settings' ] );
		add_action( 'woocommerce_update_options_taxzen', [ $this, 'save_settings' ] );
	}

	/**
	 * Add TaxZen tab to WooCommerce settings.
	 *
	 * @param array $tabs Existing tabs.
	 * @return array Modified tabs.
	 */
	public function add_settings_tab( array $tabs ): array {
		$tabs['taxzen'] = __( 'TaxZen', 'taxzen-for-woocommerce' );
		return $tabs;
	}

	/**
	 * Output the settings for the TaxZen tab.
	 */
	public function output_settings(): void {
		woocommerce_admin_fields( $this->get_settings() );
	}

	/**
	 * Save the settings for the TaxZen tab.
	 */
	public function save_settings(): void {
		woocommerce_update_options( $this->get_settings() );
	}

	/**
	 * Get settings fields for WooCommerce Settings API.
	 *
	 * @return array
	 */
	private function get_settings(): array {
		return [
			[
				'title' => __( 'TaxZen Settings', 'taxzen-for-woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'Configure TaxZen integration with WooCommerce.', 'taxzen-for-woocommerce' ),
				'id'    => 'taxzen_woo_settings_start',
			],
			[
				'title'   => __( 'Enable Tax Wizard', 'taxzen-for-woocommerce' ),
				'desc'    => __( 'Allow TaxZen to manage tax rates in WooCommerce.', 'taxzen-for-woocommerce' ),
				'id'      => 'taxzen_woo_enabled',
				'default' => 'yes',
				'type'    => 'checkbox',
			],
			[
				'title'   => __( 'Override Existing Rates', 'taxzen-for-woocommerce' ),
				'desc'    => __( 'When applying rates, replace any existing WooCommerce tax rates.', 'taxzen-for-woocommerce' ),
				'id'      => 'taxzen_woo_override_rates',
				'default' => 'no',
				'type'    => 'checkbox',
			],
			[
				'title'   => __( 'Tax Display', 'taxzen-for-woocommerce' ),
				'desc'    => __( 'How to display tax in the cart/checkout.', 'taxzen-for-woocommerce' ),
				'id'      => 'taxzen_woo_tax_display',
				'default' => 'inherit',
				'type'    => 'select',
				'options' => [
					'inherit' => __( 'Use WooCommerce default', 'taxzen-for-woocommerce' ),
					'incl'    => __( 'Including tax', 'taxzen-for-woocommerce' ),
					'excl'    => __( 'Excluding tax', 'taxzen-for-woocommerce' ),
				],
			],
			[
				'type' => 'sectionend',
				'id'   => 'taxzen_woo_settings_end',
			],
		];
	}
}
