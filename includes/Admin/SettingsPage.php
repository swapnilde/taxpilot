<?php
/**
 * WooCommerce Settings tab integration.
 *
 * @package TaxPilot\Admin
 */

declare( strict_types=1 );

namespace TaxPilot\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a TaxPilot tab under WooCommerce → Settings.
 */
class SettingsPage {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_settings_tabs_array', [ $this, 'add_settings_tab' ], 50 );
		add_action( 'woocommerce_settings_tabs_taxpilot', [ $this, 'output_settings' ] );
		add_action( 'woocommerce_update_options_taxpilot', [ $this, 'save_settings' ] );
	}

	/**
	 * Add TaxPilot tab to WooCommerce settings.
	 *
	 * @param array $tabs Existing tabs.
	 * @return array Modified tabs.
	 */
	public function add_settings_tab( array $tabs ): array {
		$tabs['taxpilot'] = __( 'TaxPilot', 'taxpilot' );
		return $tabs;
	}

	/**
	 * Output the settings for the TaxPilot tab.
	 */
	public function output_settings(): void {
		woocommerce_admin_fields( $this->get_settings() );
	}

	/**
	 * Save the settings for the TaxPilot tab.
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
				'title' => __( 'TaxPilot Settings', 'taxpilot' ),
				'type'  => 'title',
				'desc'  => __( 'Configure TaxPilot integration with WooCommerce.', 'taxpilot' ),
				'id'    => 'taxpilot_woo_settings_start',
			],
			[
				'title'   => __( 'Enable Tax Wizard', 'taxpilot' ),
				'desc'    => __( 'Allow TaxPilot to manage tax rates in WooCommerce.', 'taxpilot' ),
				'id'      => 'taxpilot_woo_enabled',
				'default' => 'yes',
				'type'    => 'checkbox',
			],
			[
				'title'   => __( 'Override Existing Rates', 'taxpilot' ),
				'desc'    => __( 'When applying rates, replace any existing WooCommerce tax rates.', 'taxpilot' ),
				'id'      => 'taxpilot_woo_override_rates',
				'default' => 'no',
				'type'    => 'checkbox',
			],
			[
				'title'   => __( 'Tax Display', 'taxpilot' ),
				'desc'    => __( 'How to display tax in the cart/checkout.', 'taxpilot' ),
				'id'      => 'taxpilot_woo_tax_display',
				'default' => 'inherit',
				'type'    => 'select',
				'options' => [
					'inherit' => __( 'Use WooCommerce default', 'taxpilot' ),
					'incl'    => __( 'Including tax', 'taxpilot' ),
					'excl'    => __( 'Excluding tax', 'taxpilot' ),
				],
			],
			[
				'type' => 'sectionend',
				'id'   => 'taxpilot_woo_settings_end',
			],
		];
	}
}
