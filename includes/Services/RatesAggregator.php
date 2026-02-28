<?php
/**
 * Dynamic Rates Aggregator.
 *
 * @package TaxPilot\Services
 */

declare( strict_types=1 );

namespace TaxPilot\Services;

use TaxPilot\Database\LogsTable;

defined( 'ABSPATH' ) || exit;

/**
 * Connects to open-source JSON projects, reformats their raw data into TaxPilot's
 * schema, and saves the final result locally into uploads/taxpilot/dynamic-rates.json.
 */
class RatesAggregator {

	/**
	 * URL of the reliable open-source tax rate JSON file tracking 100+ countries.
	 * (e.g. valeriansaliou/node-sales-tax master dataset).
	 */
	private const RAW_REMOTE_SOURCE = 'https://raw.githubusercontent.com/valeriansaliou/node-sales-tax/master/res/sales_tax_rates.json';

	/**
	 * Trigger the aggregation process.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function sync(): bool {
		$response = wp_remote_get(
			self::RAW_REMOTE_SOURCE,
			[
				'timeout' => 30,
			]
		);

		if ( is_wp_error( $response ) ) {
			LogsTable::insert( 'aggregator_error', 'Failed to fetch raw rates: ' . $response->get_error_message(), 'error' );
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( 200 !== $code || empty( $body ) ) {
			LogsTable::insert( 'aggregator_error', 'Invalid HTTP response from raw source: ' . $code, 'error' );
			return false;
		}

		$raw_data = json_decode( $body, true );
		if ( ! is_array( $raw_data ) ) {
			LogsTable::insert( 'aggregator_error', 'Failed to decode raw JSON.', 'error' );
			return false;
		}

		$formatted_data = $this->transform( $raw_data );

		if ( empty( $formatted_data ) ) {
			LogsTable::insert( 'aggregator_error', 'Transformation resulted in empty dataset.', 'error' );
			return false;
		}

		// Save securely via WP_Filesystem.
		return $this->save_locally( $formatted_data );
	}

	/**
	 * Transform the external schema into TaxPilot's internal schema.
	 *
	 * @param array $raw Incoming external JSON schema.
	 * @return array TaxPilot's internal static-rates schema.
	 */
	private function transform( array $raw ): array {
		$schema = [];

		// Include WordPress locale API for country names.
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';
		$wp_countries = wp_get_available_translations();
		// In a real environment, you'd use WC()->countries->get_countries().
		// We'll rely on WC class if available.
		$wc_countries = class_exists( 'WC_Countries' ) ? ( new \WC_Countries() )->get_countries() : [];

		foreach ( $raw as $country_code => $data ) {
			if ( ! isset( $data['rate'] ) ) {
				continue;
			}

			// Resolve a friendly country name.
			$country_name = $wc_countries[ $country_code ] ?? $country_code;

			$formatted = [
				'name'          => $country_name,
				'tax_name'      => strtoupper( $data['type'] ?? 'VAT' ),
				// Multiply flat decimal by 100 to get a strict percentage numeric.
				'standard_rate' => $data['rate'] * 100,
			];

			// Handle sub-states (like US, CA provinces).
			if ( isset( $data['states'] ) && is_array( $data['states'] ) ) {
				$formatted['states'] = [];
				foreach ( $data['states'] as $state_code => $state_data ) {
					$formatted['states'][ $state_code ] = [
						'name'             => $state_code,
						'rate'             => $state_data['rate'] * 100,
						'shipping_taxable' => true,
					];
				}
			}

			$schema[ $country_code ] = $formatted;
		}

		return $schema;
	}

	/**
	 * Writes the standardized payload securely into the wp-content/uploads/taxpilot dir.
	 *
	 * @param array $payload The finalized internal schema.
	 * @return bool
	 */
	private function save_locally( array $payload ): bool {
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$upload_dir = wp_upload_dir();
		$plugin_dir = $upload_dir['basedir'] . '/taxpilot';

		if ( ! $wp_filesystem->is_dir( $plugin_dir ) ) {
			$wp_filesystem->mkdir( $plugin_dir, FS_CHMOD_DIR );
		}

		$file_path = $plugin_dir . '/dynamic-rates.json';

		// Overwrite the specific dynamic-rates payload.
		$result = $wp_filesystem->put_contents(
			$file_path,
			wp_json_encode( $payload, JSON_PRETTY_PRINT ),
			FS_CHMOD_FILE
		);

		if ( $result ) {
			LogsTable::insert( 'aggregator_success', 'Successfully pulled, transformed, and cached remote rates.', 'info' );
		} else {
			LogsTable::insert( 'aggregator_error', 'WP_Filesystem failed to write dynamic-rates.json.', 'error' );
		}

		return (bool) $result;
	}
}
