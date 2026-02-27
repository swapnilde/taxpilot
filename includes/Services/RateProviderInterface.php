<?php
/**
 * Rate provider interface.
 *
 * @package TaxPilot\Services
 */

declare( strict_types=1 );

namespace TaxPilot\Services;

/**
 * Contract for tax rate data providers.
 */
interface RateProviderInterface {

	/**
	 * Get tax rates for a country.
	 *
	 * @param string $country_code Two-letter ISO country code.
	 * @param string $type         Rate type (standard, reduced, digital, services).
	 * @return array Array of rate data.
	 */
	public function get_rates( string $country_code, string $type = 'all' ): array;

	/**
	 * Get the provider name.
	 *
	 * @return string
	 */
	public function get_name(): string;
}
