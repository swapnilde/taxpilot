<?php
/**
 * Smart Address Validation Service.
 *
 * @package TaxPilot\Services
 */

declare( strict_types=1 );

namespace TaxPilot\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Validates shipping and billing addresses during checkout to ensure correct tax calculation.
 */
class AddressValidator {

	/**
	 * Validate an address array against OpenStreetMap Nominatim.
	 *
	 * @param array $address The address components (country, postcode, city).
	 * @return array Validation result `[ 'is_valid' => bool, 'message' => string ]`.
	 */
	public function validate_address( array $address ): array {
		$country  = $address['country'] ?? '';
		$postcode = trim( $address['postcode'] ?? '' );
		$city     = trim( $address['city'] ?? '' );

		if ( empty( $country ) || empty( $postcode ) || empty( $city ) ) {
			return [
				'is_valid' => true,
				'message'  => '',
			];
		}

		// Nominatim URL building.
		// We use structured queries to check if the postcode + city combination exists.
		$url = add_query_arg(
			[
				'country'        => strtolower( $country ),
				'postalcode'     => $postcode,
				'city'           => $city,
				'format'         => 'jsonv2',
				'addressdetails' => 1,
				'limit'          => 5,
			],
			'https://nominatim.openstreetmap.org/search'
		);

		// Nominatim requires a valid standard User-Agent.
		$args = [
			'timeout'    => 4,
			'user-agent' => 'TaxPilotForWooCommerce/1.0 (WordPress Plugin; swapnil@example.com)',
		];

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return [
				'is_valid' => true, // Fail open if API is down.
				'message'  => '',
			];
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			return [
				'is_valid' => true,
				'message'  => '',
			];
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// If Nominatim returns an empty array, it means it found zero geographic
		// features matching that exact City + Zipcode + Country combination.
		if ( empty( $data ) ) {
			return [
				'is_valid' => false,
				'message'  => sprintf(
					/* translators: 1: postal code, 2: entered city */
					__( 'TaxPilot Validation: We could not verify that zip code <strong>%1$s</strong> belongs to the city of <strong>%2$s</strong>. Please double-check your shipping/billing address to prevent incorrect tax calculations.', 'taxpilot' ),
					esc_html( $postcode ),
					esc_html( $city )
				),
			];
		}

		// If it found a match, the address is likely valid.
		return [
			'is_valid' => true,
			'message'  => '',
		];
	}
}
