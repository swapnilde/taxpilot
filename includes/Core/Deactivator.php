<?php
/**
 * Plugin deactivator.
 *
 * @package TaxZen\Core
 */

declare( strict_types=1 );

namespace TaxZen\Core;

/**
 * Handles plugin deactivation tasks.
 */
class Deactivator {

	/**
	 * Run on plugin deactivation.
	 */
	public static function deactivate(): void {
		// Clear scheduled cron events.
		wp_clear_scheduled_hook( 'taxzen_daily_rate_check' );
		wp_clear_scheduled_hook( 'taxzen_weekly_report' );

		// Reset WooCommerce auto-config flag so it runs on reactivation.
		delete_option( 'taxzen_woo_configured' );

		// Clean rate cache transients.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_taxzen_rates_%' OR option_name LIKE '%_transient_timeout_taxzen_rates_%'"
		);
	}
}
