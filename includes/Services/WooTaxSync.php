<?php
/**
 * WooCommerce Tax Sync service.
 *
 * @package TaxPilot\Services
 */

declare( strict_types=1 );

namespace TaxPilot\Services;

// This service bulk-syncs with WC tables directly for performance.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter

use TaxPilot\Database\RatesTable;
use TaxPilot\Database\LogsTable;

/**
 * Syncs TaxPilot rates into WooCommerce tax tables.
 */
class WooTaxSync {

	/**
	 * Apply rates to WooCommerce tax tables.
	 *
	 * Always clears old TaxPilot-managed rates first to prevent duplicates,
	 * then inserts fresh rates from the provider.
	 *
	 * @param array $rates Array of rate data from TaxRateService.
	 * @return array Result with 'applied' count and 'errors' array.
	 */
	public function apply_rates( array $rates ): array {
		$applied = 0;
		$errors  = [];

		// Always clear old TaxPilot rates before re-applying to prevent duplicates.
		$this->clear_taxpilot_rates();

		// Also clean up any orphaned duplicates in WC tax tables.
		$this->delete_duplicate_wc_rates();

		foreach ( $rates as $rate_data ) {
			try {
				// Insert into WooCommerce tax table.
				$woo_rate_id = $this->insert_woo_rate( $rate_data );

				if ( $woo_rate_id ) {
					// Also store in our custom table with the WC rate ID reference.
					$rate_data['woo_tax_rate_id'] = $woo_rate_id;
					RatesTable::insert( $rate_data );
					++$applied;
				}
			} catch ( \Throwable $e ) {
				$errors[] = [
					'country' => $rate_data['country_code'] ?? 'unknown',
					'error'   => $e->getMessage(),
				];
			}
		}

		// Update last-applied timestamp.
		update_option( 'taxpilot_rates_last_updated', current_time( 'mysql' ) );

		LogsTable::insert(
			'rates_applied',
			wp_json_encode(
				[
					'applied' => $applied,
					'errors'  => count( $errors ),
				]
			)
		);

		return [
			'applied' => $applied,
			'errors'  => $errors,
		];
	}

	/**
	 * Create WooCommerce tax classes from product types.
	 *
	 * @param array $types Product types (physical, digital, services).
	 * @return array Created class names.
	 */
	public function create_tax_classes( array $types ): array {
		$class_map = [
			'physical' => 'Standard',
			'digital'  => 'Digital Goods',
			'services' => 'Services',
		];

		$created = [];
		foreach ( $types as $type ) {
			if ( isset( $class_map[ $type ] ) ) {
				$result = \WC_Tax::create_tax_class( $class_map[ $type ] );
				if ( ! is_wp_error( $result ) ) {
					$created[] = $class_map[ $type ];
				}
			}
		}

		// Always create Zero Rate class for tax-exempt products.
		$result = \WC_Tax::create_tax_class( 'Zero Rate' );
		if ( ! is_wp_error( $result ) ) {
			$created[] = 'Zero Rate';
		}

		return $created;
	}

	/**
	 * Insert a single rate into WooCommerce's tax rate table.
	 *
	 * Checks for an existing rate with the same country+state+class+name
	 * and updates it instead of creating a duplicate.
	 *
	 * @param array $rate_data Rate data.
	 * @return int|false The WC tax rate ID or false on failure.
	 */
	private function insert_woo_rate( array $rate_data ): int|false {
		$country   = $rate_data['country_code'] ?? '';
		$state     = $rate_data['state'] ?? '';
		$tax_class = $rate_data['tax_class'] ?? '';
		$rate_name = $rate_data['rate_name'] ?? 'Tax';

		$tax_rate = [
			'tax_rate_country'  => $country,
			'tax_rate_state'    => $state,
			'tax_rate'          => $rate_data['rate'] ?? 0,
			'tax_rate_name'     => $rate_name,
			'tax_rate_priority' => $rate_data['priority'] ?? 1,
			'tax_rate_compound' => $rate_data['compound'] ?? 0,
			'tax_rate_shipping' => $rate_data['shipping'] ?? 1,
			'tax_rate_order'    => 0,
			'tax_rate_class'    => $tax_class,
		];

		// Check if this exact rate already exists (by country+state+class+name).
		$existing_id = $this->find_existing_rate( $country, $state, $tax_class, $rate_name );

		if ( $existing_id ) {
			\WC_Tax::_update_tax_rate( $existing_id, $tax_rate );
			$rate_id = $existing_id;
		} else {
			$rate_id = \WC_Tax::_insert_tax_rate( $tax_rate );
		}

		// Add postcode/city locations if specified (important for US/CA).
		if ( $rate_id && ! empty( $rate_data['postcode'] ) ) {
			$postcodes = is_array( $rate_data['postcode'] )
				? $rate_data['postcode']
				: array_map( 'trim', explode( ';', $rate_data['postcode'] ) );
			\WC_Tax::_update_tax_rate_postcodes( $rate_id, $postcodes );
		}

		if ( $rate_id && ! empty( $rate_data['city'] ) ) {
			$cities = is_array( $rate_data['city'] )
				? $rate_data['city']
				: array_map( 'trim', explode( ';', $rate_data['city'] ) );
			\WC_Tax::_update_tax_rate_cities( $rate_id, $cities );
		}

		return $rate_id ?: false;
	}

	/**
	 * Find an existing WC tax rate by country, state, class, and name.
	 *
	 * Adding rate_name to the match prevents false positives when multiple
	 * rate types share the same country+state+class.
	 *
	 * @param string $country   Country code.
	 * @param string $state     State code.
	 * @param string $tax_class Tax class slug.
	 * @param string $rate_name Rate name for disambiguation.
	 * @return int|false Rate ID or false.
	 */
	private function find_existing_rate( string $country, string $state, string $tax_class, string $rate_name = '' ): int|false {
		global $wpdb;

		if ( $rate_name ) {
			$rate_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT tax_rate_id FROM {$wpdb->prefix}woocommerce_tax_rates
					WHERE tax_rate_country = %s AND tax_rate_state = %s AND tax_rate_class = %s AND tax_rate_name = %s
					LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$country,
					$state,
					$tax_class,
					$rate_name
				)
			);

			if ( $rate_id ) {
				return (int) $rate_id;
			}
		}

		// Fallback: match without name.
		$rate_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT tax_rate_id FROM {$wpdb->prefix}woocommerce_tax_rates
				WHERE tax_rate_country = %s AND tax_rate_state = %s AND tax_rate_class = %s
				LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$country,
				$state,
				$tax_class
			)
		);

		return $rate_id ? (int) $rate_id : false;
	}

	/**
	 * Clear all WooCommerce rates that were created by TaxPilot.
	 */
	private function clear_taxpilot_rates(): void {
		$our_rates = RatesTable::get_all( [ 'limit' => 10000 ] );

		foreach ( $our_rates as $rate ) {
			if ( ! empty( $rate['woo_tax_rate_id'] ) ) {
				\WC_Tax::_delete_tax_rate( (int) $rate['woo_tax_rate_id'] );
			}
		}

		RatesTable::truncate();
	}

	/**
	 * Delete duplicate WC tax rates, keeping only the first one per
	 * country+state+class+name combination.
	 *
	 * Cleans up duplicates from previous runs that weren't properly tracked.
	 */
	private function delete_duplicate_wc_rates(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'woocommerce_tax_rates';

		// Find duplicate groups and keep the lowest tax_rate_id per group.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is safe, from $wpdb->prefix.
		$duplicates = $wpdb->get_results(
			"SELECT tax_rate_id FROM {$table}
			WHERE tax_rate_id NOT IN (
				SELECT keep_id FROM (
					SELECT MIN(tax_rate_id) AS keep_id FROM {$table}
					GROUP BY tax_rate_country, tax_rate_state, tax_rate_class, tax_rate_name
				) AS keeper
			)",
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		foreach ( $duplicates as $dup ) {
			\WC_Tax::_delete_tax_rate( (int) $dup['tax_rate_id'] );
		}

		if ( ! empty( $duplicates ) ) {
			LogsTable::insert(
				'duplicate_rates_cleaned',
				wp_json_encode( [ 'removed' => count( $duplicates ) ] ),
				'info'
			);
		}
	}
}
