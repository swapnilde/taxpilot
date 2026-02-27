<?php
/**
 * Plugin deactivator.
 *
 * @package TaxPilot\Core
 */

declare( strict_types=1 );

namespace TaxPilot\Core;

/**
 * Handles plugin deactivation tasks.
 */
class Deactivator {

	/**
	 * Run on plugin deactivation.
	 */
	public static function deactivate(): void {
		// Clear scheduled cron events.
		wp_clear_scheduled_hook( 'taxpilot_daily_rate_check' );
		wp_clear_scheduled_hook( 'taxpilot_weekly_report' );

		// Reset WooCommerce auto-config flag so it runs on reactivation.
		delete_option( 'taxpilot_woo_configured' );

		// Clean rate cache transients.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_taxpilot_rates_%' OR option_name LIKE '%_transient_timeout_taxpilot_rates_%'"
		);
	}
}
