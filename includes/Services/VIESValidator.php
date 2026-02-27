<?php
/**
 * VIES VAT number validator.
 *
 * @package TaxPilot\Services
 */

declare( strict_types=1 );

namespace TaxPilot\Services;

use TaxPilot\Database\LogsTable;

/**
 * Validates EU VAT numbers via the VIES API (EU Commission, free).
 */
class VIESValidator {

	/**
	 * VIES REST API endpoint.
	 */
	private const API_URL = 'https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number';

	/**
	 * Validate a VAT number.
	 *
	 * @param string $vat_number Full VAT number including country prefix (e.g., DE123456789).
	 * @return array { valid: bool, name: string, address: string, error: string|null }
	 */
	public static function validate( string $vat_number ): array {
		$vat_number = preg_replace( '/\s+/', '', $vat_number );

		if ( strlen( $vat_number ) < 4 ) {
			return [
				'valid'   => false,
				'name'    => '',
				'address' => '',
				'error'   => __( 'VAT number is too short.', 'taxpilot' ),
			];
		}

		$country_code = strtoupper( substr( $vat_number, 0, 2 ) );
		$number       = substr( $vat_number, 2 );

		$response = wp_remote_post(
			self::API_URL,
			[
				'body'    => wp_json_encode(
					[
						'countryCode' => $country_code,
						'vatNumber'   => $number,
					]
				),
				'headers' => [ 'Content-Type' => 'application/json' ],
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) {
			LogsTable::insert(
				'vies_error',
				wp_json_encode(
					[
						'vat'   => $vat_number,
						'error' => $response->get_error_message(),
					]
				),
				'error'
			);

			return [
				'valid'   => false,
				'name'    => '',
				'address' => '',
				'error'   => $response->get_error_message(),
			];
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! isset( $body['valid'] ) ) {
			return [
				'valid'   => false,
				'name'    => '',
				'address' => '',
				'error'   => __( 'Unexpected response from VIES.', 'taxpilot' ),
			];
		}

		return [
			'valid'   => (bool) $body['valid'],
			'name'    => $body['name'] ?? '',
			'address' => $body['address'] ?? '',
			'error'   => null,
		];
	}
}
