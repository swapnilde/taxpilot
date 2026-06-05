<?php
/**
 * TaxZen for WooCommerce Uninstall.
 *
 * Fires when the plugin is deleted via WP Admin.
 * Removes all custom DB tables, options, and transients.
 *
 * @package TaxZen
 */

declare( strict_types=1 );

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop custom tables.
$taxzen_tables = [
	$wpdb->prefix . 'taxzen_rates',
	$wpdb->prefix . 'taxzen_logs',
	$wpdb->prefix . 'taxzen_alerts',
];

foreach ( $taxzen_tables as $taxzen_table ) {
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $taxzen_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

// Delete options.
$taxzen_options = [
	'taxzen_settings',
	'taxzen_wizard_state',
	'taxzen_db_version',
	'taxzen_installed_at',
	'taxzen_woo_configured',
	'taxzen_rates_last_updated',
	'taxzen_woo_enabled',
	'taxzen_woo_override_rates',
	'taxzen_woo_tax_display',
];

foreach ( $taxzen_options as $taxzen_option ) {
	delete_option( $taxzen_option );
}

// Clean transients.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_taxzen_%' OR option_name LIKE '%_transient_timeout_taxzen_%'"
);

// Clear scheduled cron events.
wp_clear_scheduled_hook( 'taxzen_daily_rate_check' );
wp_clear_scheduled_hook( 'taxzen_weekly_report' );
