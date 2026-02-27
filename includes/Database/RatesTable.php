<?php
/**
 * Rates table model.
 *
 * @package TaxPilot\Database
 */

declare( strict_types=1 );

namespace TaxPilot\Database;

// Custom database tables require direct queries.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching

/**
 * CRUD model for the taxpilot_rates table.
 */
class RatesTable {

	/**
	 * Get the table name.
	 */
	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'taxpilot_rates';
	}

	/**
	 * Insert a rate.
	 *
	 * @param array $data Rate data.
	 * @return int|false Inserted row ID or false on failure.
	 */
	public static function insert( array $data ): int|false {
		global $wpdb;

		$defaults = [
			'country_code'    => '',
			'state'           => '',
			'city'            => '',
			'postcode'        => '',
			'rate'            => 0.0,
			'rate_name'       => '',
			'rate_type'       => 'standard',
			'tax_class'       => '',
			'priority'        => 1,
			'compound'        => 0,
			'shipping'        => 1,
			'source'          => 'static',
			'woo_tax_rate_id' => null,
		];

		$data = wp_parse_args( $data, $defaults );

		$result = $wpdb->insert(
			self::table_name(),
			$data,
			[
				'%s',
				'%s',
				'%s',
				'%s',
				'%f',
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
				'%d',
				'%s',
				'%d',
			]
		);

		return false !== $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Get rates by country code.
	 *
	 * @param string $country_code Two-letter ISO country code.
	 * @return array
	 */
	public static function get_by_country( string $country_code ): array {
		global $wpdb;
		$table = self::table_name();

		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE country_code = %s ORDER BY priority ASC", $country_code ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		) ?: [];
	}

	/**
	 * Get all rates.
	 *
	 * @param array $args Optional query args (limit, offset, tax_class, source).
	 * @return array
	 */
	public static function get_all( array $args = [] ): array {
		global $wpdb;
		$table = self::table_name();

		$where  = '1=1';
		$params = [];

		if ( ! empty( $args['tax_class'] ) ) {
			$where   .= ' AND tax_class = %s';
			$params[] = $args['tax_class'];
		}
		if ( ! empty( $args['source'] ) ) {
			$where   .= ' AND source = %s';
			$params[] = $args['source'];
		}

		$limit  = isset( $args['limit'] ) ? absint( $args['limit'] ) : 100;
		$offset = isset( $args['offset'] ) ? absint( $args['offset'] ) : 0;

		$sql      = "SELECT * FROM {$table} WHERE {$where} ORDER BY country_code ASC, priority ASC LIMIT %d OFFSET %d"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$params[] = $limit;
		$params[] = $offset;

		// phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
		return $wpdb->get_results(
			$wpdb->prepare( $sql, ...$params ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		) ?: [];
	}

	/**
	 * Count total rates.
	 *
	 * @return int
	 */
	public static function count(): int {
		global $wpdb;
		$table = self::table_name();
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Update a rate.
	 *
	 * @param int   $id   Rate ID.
	 * @param array $data Data to update.
	 * @return bool
	 */
	public static function update( int $id, array $data ): bool {
		global $wpdb;
		return false !== $wpdb->update(
			self::table_name(),
			$data,
			[ 'id' => $id ],
			null,
			[ '%d' ]
		);
	}

	/**
	 * Delete a rate.
	 *
	 * @param int $id Rate ID.
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
	 * Delete all rates for a given country.
	 *
	 * @param string $country_code Two-letter ISO country code.
	 * @return int Number of rows deleted.
	 */
	public static function delete_by_country( string $country_code ): int {
		global $wpdb;
		return (int) $wpdb->delete(
			self::table_name(),
			[ 'country_code' => $country_code ],
			[ '%s' ]
		);
	}

	/**
	 * Truncate all rates.
	 *
	 * @return void
	 */
	public static function truncate(): void {
		global $wpdb;
		$table = self::table_name();
		$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
