<?php
/**
 * Tax rate service — main facade.
 *
 * @package TaxPilot\Services
 */

declare( strict_types=1 );

namespace TaxPilot\Services;

use TaxPilot\Database\RatesTable;
use TaxPilot\Database\LogsTable;

/**
 * Facade service for tax rate operations.
 * Handles provider selection, caching, and rate management.
 */
class TaxRateService {

	/**
	 * Cache TTL in seconds (24 hours).
	 */
	private const CACHE_TTL = DAY_IN_SECONDS;

	/**
	 * Get the configured rate provider.
	 *
	 * @return RateProviderInterface
	 */
	public function get_provider(): RateProviderInterface {
		$settings = get_option( 'taxpilot_settings', [] );
		$provider = $settings['api_provider'] ?? 'static';

		return match ( $provider ) {
			'vatsense' => new VATSenseProvider(),
			default    => new StaticRatesProvider(),
		};
	}

	/**
	 * Get rates for multiple countries (with caching).
	 *
	 * @param array $countries Array of country codes.
	 * @return array All rates.
	 */
	public function get_rates_for_countries( array $countries ): array {
		$all_rates = [];

		foreach ( $countries as $country_code ) {
			$country_code = strtoupper( $country_code );
			$cache_key    = 'taxpilot_rates_' . $country_code;

			// Try cache first.
			$cached = get_transient( $cache_key );
			if ( false !== $cached ) {
				$all_rates = array_merge( $all_rates, $cached );
				continue;
			}

			// Fetch from provider.
			$rates = $this->fetch_rates_for_country( $country_code );

			// Cache the result.
			set_transient( $cache_key, $rates, self::CACHE_TTL );

			$all_rates = array_merge( $all_rates, $rates );
		}

		return $all_rates;
	}

	/**
	 * Force refresh rates — clears cache and re-fetches.
	 *
	 * @param array $countries Array of country codes.
	 * @return array Fresh rates.
	 */
	public function refresh_rates( array $countries ): array {
		$all_rates = [];

		foreach ( $countries as $country_code ) {
			$country_code = strtoupper( $country_code );
			$cache_key    = 'taxpilot_rates_' . $country_code;

			// Clear cache.
			delete_transient( $cache_key );

			// Fetch fresh.
			$rates = $this->fetch_rates_for_country( $country_code );
			set_transient( $cache_key, $rates, self::CACHE_TTL );

			$all_rates = array_merge( $all_rates, $rates );
		}

		LogsTable::insert(
			'rates_refreshed',
			wp_json_encode(
				[
					'countries'   => $countries,
					'total_rates' => count( $all_rates ),
				]
			)
		);

		return $all_rates;
	}

	/**
	 * Detect rate changes compared to stored rates.
	 *
	 * @param array $countries Countries to check.
	 * @return array Array of changes: [ 'country' => ..., 'old_rate' => ..., 'new_rate' => ... ]
	 */
	public function detect_changes( array $countries ): array {
		$changes = [];

		foreach ( $countries as $country_code ) {
			$country_code = strtoupper( $country_code );

			// Get stored rates.
			$stored     = RatesTable::get_by_country( $country_code );
			$stored_map = [];
			foreach ( $stored as $rate ) {
				$key                = $rate['country_code'] . '_' . $rate['state'] . '_' . $rate['rate_type'];
				$stored_map[ $key ] = (float) $rate['rate'];
			}

			// Get fresh rates from provider.
			$fresh = $this->fetch_rates_for_country( $country_code );
			foreach ( $fresh as $rate ) {
				$key      = $rate['country_code'] . '_' . $rate['state'] . '_' . $rate['rate_type'];
				$new_rate = (float) $rate['rate'];

				if ( isset( $stored_map[ $key ] ) && abs( $stored_map[ $key ] - $new_rate ) > 0.001 ) {
					$changes[] = [
						'country'   => $country_code,
						'state'     => $rate['state'],
						'rate_type' => $rate['rate_type'],
						'old_rate'  => $stored_map[ $key ],
						'new_rate'  => $new_rate,
						'rate_name' => $rate['rate_name'],
					];
				}
			}
		}

		return $changes;
	}

	/**
	 * Fetch rates from the provider for a single country.
	 *
	 * @param string $country_code Two-letter ISO country code.
	 * @return array
	 */
	private function fetch_rates_for_country( string $country_code ): array {
		$provider = $this->get_provider();

		try {
			$rates = $provider->get_rates( $country_code );

			// If API provider returns empty, fall back to static.
			if ( empty( $rates ) && 'static' !== $provider->get_name() ) {
				$fallback = new StaticRatesProvider();
				$rates    = $fallback->get_rates( $country_code );
				LogsTable::insert(
					'provider_fallback',
					wp_json_encode(
						[
							'country' => $country_code,
							'from'    => $provider->get_name(),
							'to'      => 'static',
						]
					),
					'warning'
				);
			}

			return $rates;
		} catch ( \Throwable $e ) {
			LogsTable::insert(
				'provider_error',
				wp_json_encode(
					[
						'country' => $country_code,
						'error'   => $e->getMessage(),
					]
				),
				'error'
			);

			// Fall back to static on error.
			$fallback = new StaticRatesProvider();
			return $fallback->get_rates( $country_code );
		}
	}
}
