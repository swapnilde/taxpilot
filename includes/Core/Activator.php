<?php
/**
 * Plugin activator.
 *
 * @package TaxPilot\Core
 */

declare( strict_types=1 );

namespace TaxPilot\Core;

use TaxPilot\Database\Migrator;

/**
 * Handles plugin activation tasks.
 */
class Activator {

	/**
	 * Run on plugin activation.
	 */
	public static function activate(): void {
		// Create or update custom database tables.
		Migrator::create_tables();

		// Set default options.
		self::set_defaults();

		// Schedule cron events.
		self::schedule_events();

		// Record installation time.
		if ( ! get_option( 'taxpilot_installed_at' ) ) {
			update_option( 'taxpilot_installed_at', time() );
		}

		// Auto-enable WooCommerce tax calculation.
		if ( class_exists( 'WooCommerce' ) ) {
			update_option( 'woocommerce_calc_taxes', 'yes' );
		}

		// Flush rewrite rules for REST API.
		flush_rewrite_rules();
	}

	/**
	 * Set default plugin options.
	 */
	private static function set_defaults(): void {
		$defaults = [
			'api_provider'     => 'static',
			'api_key'          => '',
			'base_country'     => '',
			'base_currency'    => '',
			'product_types'    => [],
			'target_countries' => [],
			'refresh_interval' => 'daily',
			'alerts_enabled'   => true,
			'alert_email'      => get_option( 'admin_email' ),
			'wizard_completed' => false,
		];

		if ( ! get_option( 'taxpilot_settings' ) ) {
			add_option( 'taxpilot_settings', $defaults );
		}
	}

	/**
	 * Schedule cron events.
	 */
	private static function schedule_events(): void {
		if ( ! wp_next_scheduled( 'taxpilot_daily_rate_check' ) ) {
			wp_schedule_event( time(), 'daily', 'taxpilot_daily_rate_check' );
		}
		if ( ! wp_next_scheduled( 'taxpilot_weekly_report' ) ) {
			wp_schedule_event( time(), 'weekly', 'taxpilot_weekly_report' );
		}
	}
}
