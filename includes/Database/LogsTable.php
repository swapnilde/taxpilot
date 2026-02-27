<?php
/**
 * Logs table model.
 *
 * @package TaxPilot\Database
 */

declare( strict_types=1 );

namespace TaxPilot\Database;

// Custom database tables require direct queries.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching

/**
 * CRUD model for the taxpilot_logs table.
 */
class LogsTable {

	/**
	 * Get the table name.
	 */
	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'taxpilot_logs';
	}

	/**
	 * Insert a log entry.
	 *
	 * @param string $action  The action that was performed.
	 * @param string $details Additional details (JSON or text).
	 * @param string $level   Log level (info, warning, error).
	 * @return int|false Inserted row ID or false on failure.
	 */
	public static function insert( string $action, string $details = '', string $level = 'info' ): int|false {
		global $wpdb;

		$result = $wpdb->insert(
			self::table_name(),
			[
				'action'  => sanitize_text_field( $action ),
				'details' => $details,
				'level'   => sanitize_text_field( $level ),
			],
			[ '%s', '%s', '%s' ]
		);

		return false !== $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Get recent log entries.
	 *
	 * @param int    $limit  Number of entries to return.
	 * @param string $level  Optional: filter by level.
	 * @return array
	 */
	public static function get_recent( int $limit = 50, string $level = '' ): array {
		global $wpdb;
		$table = self::table_name();

		if ( '' !== $level ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE level = %s ORDER BY created_at DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$level,
					$limit
				),
				ARRAY_A
			) ?: [];
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$limit
			),
			ARRAY_A
		) ?: [];
	}

	/**
	 * Delete logs older than a given number of days.
	 *
	 * @param int $days Number of days to keep.
	 * @return int Number of deleted rows.
	 */
	public static function cleanup( int $days = 90 ): int {
		global $wpdb;
		$table = self::table_name();

		return (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$days
			)
		);
	}
}
