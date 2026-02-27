<?php
/**
 * TaxPilot for WooCommerce Uninstall.
 *
 * Fires when the plugin is deleted via WP Admin.
 * Removes all custom DB tables, options, and transients.
 *
 * @package TaxPilot
 */

declare( strict_types=1 );

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop custom tables.
$taxpilot_tables = [
	$wpdb->prefix . 'taxpilot_rates',
	$wpdb->prefix . 'taxpilot_logs',
	$wpdb->prefix . 'taxpilot_alerts',
];

foreach ( $taxpilot_tables as $taxpilot_table ) {
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $taxpilot_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

// Delete options.
$taxpilot_options = [
	'taxpilot_settings',
	'taxpilot_wizard_state',
	'taxpilot_db_version',
	'taxpilot_installed_at',
	'taxpilot_woo_configured',
	'taxpilot_rates_last_updated',
	'taxpilot_woo_enabled',
	'taxpilot_woo_override_rates',
	'taxpilot_woo_tax_display',
];

foreach ( $taxpilot_options as $taxpilot_option ) {
	delete_option( $taxpilot_option );
}

// Clean transients.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_taxpilot_%' OR option_name LIKE '%_transient_timeout_taxpilot_%'"
);

// Clear scheduled cron events.
wp_clear_scheduled_hook( 'taxpilot_daily_rate_check' );
wp_clear_scheduled_hook( 'taxpilot_weekly_report' );
