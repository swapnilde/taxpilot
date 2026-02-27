<?php
/**
 * Alerts table model.
 *
 * @package TaxPilot\Database
 */

declare( strict_types=1 );

namespace TaxPilot\Database;

// Custom database tables require direct queries.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching

/**
 * CRUD model for the taxpilot_alerts table.
 */
class AlertsTable {

	/**
	 * Get the table name.
	 */
	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'taxpilot_alerts';
	}

	/**
	 * Create an alert.
	 *
	 * @param string $type     Alert type (rate_change, threshold, error).
	 * @param string $title    Short title.
	 * @param string $message  Full message.
	 * @param string $severity Severity (info, warning, critical).
	 * @param array  $metadata Optional metadata.
	 * @return int|false Inserted row ID or false on failure.
	 */
	public static function insert( string $type, string $title, string $message, string $severity = 'info', array $metadata = [] ): int|false {
		global $wpdb;

		$result = $wpdb->insert(
			self::table_name(),
			[
				'type'     => sanitize_text_field( $type ),
				'title'    => sanitize_text_field( $title ),
				'message'  => $message,
				'severity' => sanitize_text_field( $severity ),
				'is_read'  => 0,
				'metadata' => ! empty( $metadata ) ? wp_json_encode( $metadata ) : null,
			],
			[ '%s', '%s', '%s', '%s', '%d', '%s' ]
		);

		return false !== $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Get recent alerts.
	 *
	 * @param int  $limit   Number of entries.
	 * @param bool $unread_only Only return unread alerts.
	 * @return array
	 */
	public static function get_recent( int $limit = 20, bool $unread_only = false ): array {
		global $wpdb;
		$table = self::table_name();

		if ( $unread_only ) {
			return $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$table} WHERE is_read = 0 ORDER BY created_at DESC LIMIT %d", $limit ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				ARRAY_A
			) ?: [];
		}

		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d", $limit ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		) ?: [];
	}

	/**
	 * Get unread count.
	 *
	 * @return int
	 */
	public static function unread_count(): int {
		global $wpdb;
		$table = self::table_name();
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_read = 0" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Mark an alert as read.
	 *
	 * @param int $id Alert ID.
	 * @return bool
	 */
	public static function mark_read( int $id ): bool {
		global $wpdb;
		return false !== $wpdb->update(
			self::table_name(),
			[ 'is_read' => 1 ],
			[ 'id' => $id ],
			[ '%d' ],
			[ '%d' ]
		);
	}

	/**
	 * Mark all alerts as read.
	 *
	 * @return int Number of updated rows.
	 */
	public static function mark_all_read(): int {
		global $wpdb;
		$table = self::table_name();
		return (int) $wpdb->query( "UPDATE {$table} SET is_read = 1 WHERE is_read = 0" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Delete an alert.
	 *
	 * @param int $id Alert ID.
	 * @return bool
	 */
	public static function delete( int $id ): bool {
		global $wpdb;
		return false !== $wpdb->delete(
			self::table_name(),
			[ 'id' => $id ],
			[ '%d' ]
		);
	}

	/**
	 * Cleanup old alerts.
	 *
	 * @param int $days Number of days to keep.
	 * @return int Number of deleted rows.
	 */
	public static function cleanup( int $days = 180 ): int {
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
