<?php
/**
 * Database migrator.
 *
 * @package TaxPilot\Database
 */

declare( strict_types=1 );

namespace TaxPilot\Database;

/**
 * Creates and updates custom database tables via dbDelta.
 */
class Migrator {

	/**
	 * Current DB schema version.
	 */
	private const DB_VERSION = '1.0.0';

	/**
	 * Create or update all custom tables.
	 */
	public static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Rates table.
		$rates_table = $wpdb->prefix . 'taxpilot_rates';
		$sql_rates   = "CREATE TABLE {$rates_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			country_code varchar(2) NOT NULL DEFAULT '',
			state varchar(100) NOT NULL DEFAULT '',
			city varchar(100) NOT NULL DEFAULT '',
			postcode varchar(20) NOT NULL DEFAULT '',
			rate decimal(8,4) NOT NULL DEFAULT 0.0000,
			rate_name varchar(200) NOT NULL DEFAULT '',
			rate_type varchar(50) NOT NULL DEFAULT 'standard',
			tax_class varchar(100) NOT NULL DEFAULT '',
			priority int(11) NOT NULL DEFAULT 1,
			compound tinyint(1) NOT NULL DEFAULT 0,
			shipping tinyint(1) NOT NULL DEFAULT 1,
			source varchar(50) NOT NULL DEFAULT 'static',
			woo_tax_rate_id bigint(20) unsigned DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY country_code (country_code),
			KEY tax_class (tax_class),
			KEY source (source)
		) {$charset_collate};";

		// Logs table.
		$logs_table = $wpdb->prefix . 'taxpilot_logs';
		$sql_logs   = "CREATE TABLE {$logs_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			action varchar(100) NOT NULL DEFAULT '',
			details longtext NOT NULL,
			level varchar(20) NOT NULL DEFAULT 'info',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY action (action),
			KEY level (level),
			KEY created_at (created_at)
		) {$charset_collate};";

		// Alerts table.
		$alerts_table = $wpdb->prefix . 'taxpilot_alerts';
		$sql_alerts   = "CREATE TABLE {$alerts_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			type varchar(50) NOT NULL DEFAULT 'rate_change',
			title varchar(255) NOT NULL DEFAULT '',
			message longtext NOT NULL,
			severity varchar(20) NOT NULL DEFAULT 'info',
			is_read tinyint(1) NOT NULL DEFAULT 0,
			metadata longtext DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY type (type),
			KEY severity (severity),
			KEY is_read (is_read),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql_rates );
		dbDelta( $sql_logs );
		dbDelta( $sql_alerts );

		update_option( 'taxpilot_db_version', self::DB_VERSION );
	}

	/**
	 * Check if tables need updating.
	 */
	public static function needs_update(): bool {
		return get_option( 'taxpilot_db_version', '' ) !== self::DB_VERSION;
	}
}
