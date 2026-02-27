<?php
/**
 * VATSense API provider.
 *
 * @package TaxPilot\Services
 */

declare( strict_types=1 );

namespace TaxPilot\Services;

use TaxPilot\Database\LogsTable;

/**
 * Provides tax rates from the VATSense API.
 */
class VATSenseProvider implements RateProviderInterface {

	/**
	 * API base URL.
	 */
	private const API_BASE = 'https://api.vatsense.com/1.0';

	/**
	 * API key.
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$settings      = get_option( 'taxpilot_settings', [] );
		$this->api_key = $settings['api_key'] ?? '';
	}

	/**
	 * Get rates for a country from VATSense API.
	 *
	 * @param string $country_code Two-letter ISO country code.
	 * @param string $type         Rate type.
	 * @return array
	 */
	public function get_rates( string $country_code, string $type = 'all' ): array {
		if ( empty( $this->api_key ) ) {
			LogsTable::insert( 'vatsense_error', 'API key not configured.', 'error' );
			return [];
		}

		$response = wp_remote_get(
			self::API_BASE . '/rates?country_code=' . strtoupper( $country_code ),
			[
				'headers' => [
					'Authorization' => 'Basic ' . base64_encode( 'user:' . $this->api_key ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				],
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) {
			LogsTable::insert(
				'vatsense_error',
				wp_json_encode(
					[
						'country' => $country_code,
						'error'   => $response->get_error_message(),
					]
				),
				'error'
			);
			return [];
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( 200 !== $code || empty( $data['data'] ) ) {
			LogsTable::insert(
				'vatsense_error',
				wp_json_encode(
					[
						'country'   => $country_code,
						'http_code' => $code,
						'body'      => $body,
					]
				),
				'error'
			);
			return [];
		}

		return $this->parse_response( $country_code, $data['data'] );
	}

	/**
	 * Get provider name.
	 */
	public function get_name(): string {
		return 'vatsense';
	}

	/**
	 * Parse VATSense API response into our rate format.
	 *
	 * @param string $country_code Country code.
	 * @param array  $data         API response data.
	 * @return array
	 */
	private function parse_response( string $country_code, array $data ): array {
		$rates = [];

		// The API returns country-level rate info.
		if ( isset( $data['standard_rate'] ) ) {
			$rates[] = [
				'country_code' => strtoupper( $country_code ),
				'state'        => '',
				'rate'         => (float) $data['standard_rate'],
				'rate_name'    => ( $data['country_name'] ?? $country_code ) . ' ' . ( $data['local_name'] ?? 'VAT' ),
				'rate_type'    => 'standard',
				'tax_class'    => '',
				'priority'     => 1,
				'compound'     => 0,
				'shipping'     => 1,
				'source'       => 'vatsense',
			];
		}

		// Reduced rates.
		if ( ! empty( $data['reduced_rates'] ) && is_array( $data['reduced_rates'] ) ) {
			foreach ( $data['reduced_rates'] as $index => $reduced_rate ) {
				$rates[] = [
					'country_code' => strtoupper( $country_code ),
					'state'        => '',
					'rate'         => (float) $reduced_rate,
					'rate_name'    => ( $data['country_name'] ?? $country_code ) . ' Reduced Rate ' . ( $index + 1 ),
					'rate_type'    => 'reduced',
					'tax_class'    => 'reduced-rate',
					'priority'     => 1,
					'compound'     => 0,
					'shipping'     => 1,
					'source'       => 'vatsense',
				];
			}
		}

		return $rates;
	}
}
