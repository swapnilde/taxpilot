<?php
/**
 * Static rates provider.
 *
 * @package TaxPilot\Services
 */

declare( strict_types=1 );

namespace TaxPilot\Services;

/**
 * Provides tax rates from the bundled static JSON file.
 */
class StaticRatesProvider implements RateProviderInterface {

	/**
	 * Cached rates data.
	 *
	 * @var array|null
	 */
	private ?array $data = null;

	/**
	 * Get rates for a country.
	 *
	 * @param string $country_code Two-letter ISO country code.
	 * @param string $type         Rate type.
	 * @return array
	 */
	public function get_rates( string $country_code, string $type = 'all' ): array {
		$data         = $this->load_data();
		$country_code = strtoupper( $country_code );

		if ( ! isset( $data[ $country_code ] ) ) {
			return [];
		}

		$country_data = $data[ $country_code ];
		$rates        = [];

		// Get the product types selected in the wizard to know which classes to populate.
		$settings      = get_option( 'taxpilot_settings', [] );
		$product_types = $settings['product_types'] ?? [];
		$tax_name      = $country_data['tax_name'] ?? 'Tax';
		$country_name  = $country_data['name'];

		// ── Standard rate (tax_class = '' → "Standard rates" tab) ──
		if ( isset( $country_data['standard_rate'] ) ) {
			$rates[] = [
				'country_code' => $country_code,
				'state'        => '',
				'rate'         => (float) $country_data['standard_rate'],
				'rate_name'    => $country_name . ' ' . $tax_name,
				'rate_type'    => 'standard',
				'tax_class'    => '',
				'priority'     => 1,
				'compound'     => 0,
				'shipping'     => 1,
				'source'       => 'static',
			];
		}

		// ── Reduced rate (tax_class = 'reduced-rate' → "Reduced rate" tab) ──
		if ( isset( $country_data['reduced_rate'] ) && 'standard_only' !== $type ) {
			$rates[] = [
				'country_code' => $country_code,
				'state'        => '',
				'rate'         => (float) $country_data['reduced_rate'],
				'rate_name'    => $country_name . ' Reduced ' . $tax_name,
				'rate_type'    => 'reduced',
				'tax_class'    => 'reduced-rate',
				'priority'     => 1,
				'compound'     => 0,
				'shipping'     => 1,
				'source'       => 'static',
			];
		}

		// ── Digital Goods (tax_class = 'digital-goods' → "Digital Goods" tab) ──
		// EU digital goods typically use the standard rate (place of supply = customer).
		// Non-EU countries may use reduced rates for digital goods if available.
		if ( in_array( 'digital', $product_types, true ) && isset( $country_data['standard_rate'] ) ) {
			$digital_rate = $country_data['digital_rate']
				?? $country_data['standard_rate'];

			$rates[] = [
				'country_code' => $country_code,
				'state'        => '',
				'rate'         => (float) $digital_rate,
				'rate_name'    => $country_name . ' Digital ' . $tax_name,
				'rate_type'    => 'digital',
				'tax_class'    => 'digital-goods',
				'priority'     => 1,
				'compound'     => 0,
				'shipping'     => 0,  // Digital goods have no shipping tax.
				'source'       => 'static',
			];
		}

		// ── Services (tax_class = 'services' → "Services" tab) ──
		// Services typically use the standard rate.
		if ( in_array( 'services', $product_types, true ) && isset( $country_data['standard_rate'] ) ) {
			$services_rate = $country_data['services_rate']
				?? $country_data['standard_rate'];

			$rates[] = [
				'country_code' => $country_code,
				'state'        => '',
				'rate'         => (float) $services_rate,
				'rate_name'    => $country_name . ' Services ' . $tax_name,
				'rate_type'    => 'services',
				'tax_class'    => 'services',
				'priority'     => 1,
				'compound'     => 0,
				'shipping'     => 0,  // Services don't ship.
				'source'       => 'static',
			];
		}

		// ── Zero Rate (tax_class = 'zero-rate' → "Zero rate" tab) ──
		// Always add a 0% entry for tax-exempt scenarios (exports, B2B, exempt products).
		$rates[] = [
			'country_code' => $country_code,
			'state'        => '',
			'rate'         => 0.0,
			'rate_name'    => $country_name . ' Zero Rate',
			'rate_type'    => 'zero',
			'tax_class'    => 'zero-rate',
			'priority'     => 1,
			'compound'     => 0,
			'shipping'     => 0,
			'source'       => 'static',
		];

		// ── US/CA state-level rates ──
		if ( isset( $country_data['states'] ) && is_array( $country_data['states'] ) ) {
			foreach ( $country_data['states'] as $state_code => $state_data ) {
				$state_rate = (float) $state_data['rate'];
				$state_name = $state_data['name'];
				$shipping   = (int) ( $state_data['shipping_taxable'] ?? true );

				// Standard rate for this state.
				$rates[] = [
					'country_code' => $country_code,
					'state'        => strtoupper( $state_code ),
					'rate'         => $state_rate,
					'rate_name'    => $state_name . ' Sales Tax',
					'rate_type'    => 'standard',
					'tax_class'    => '',
					'priority'     => 1,
					'compound'     => 0,
					'shipping'     => $shipping,
					'source'       => 'static',
				];

				// Digital goods rate for this state (same rate, no shipping tax).
				if ( in_array( 'digital', $product_types, true ) ) {
					$rates[] = [
						'country_code' => $country_code,
						'state'        => strtoupper( $state_code ),
						'rate'         => $state_rate,
						'rate_name'    => $state_name . ' Digital Tax',
						'rate_type'    => 'digital',
						'tax_class'    => 'digital-goods',
						'priority'     => 1,
						'compound'     => 0,
						'shipping'     => 0,
						'source'       => 'static',
					];
				}

				// Services rate for this state.
				if ( in_array( 'services', $product_types, true ) ) {
					$rates[] = [
						'country_code' => $country_code,
						'state'        => strtoupper( $state_code ),
						'rate'         => $state_rate,
						'rate_name'    => $state_name . ' Services Tax',
						'rate_type'    => 'services',
						'tax_class'    => 'services',
						'priority'     => 1,
						'compound'     => 0,
						'shipping'     => 0,
						'source'       => 'static',
					];
				}

				// Zero rate for this state.
				$rates[] = [
					'country_code' => $country_code,
					'state'        => strtoupper( $state_code ),
					'rate'         => 0.0,
					'rate_name'    => $state_name . ' Zero Rate',
					'rate_type'    => 'zero',
					'tax_class'    => 'zero-rate',
					'priority'     => 1,
					'compound'     => 0,
					'shipping'     => 0,
					'source'       => 'static',
				];
			}
		}

		return $rates;
	}

	/**
	 * Get provider name.
	 */
	public function get_name(): string {
		return 'static';
	}

	/**
	 * Load the rates JSON file.
	 * Checks the dynamic uploads folder first, then falls back to the bundled static asset.
	 *
	 * @return array
	 */
	private function load_data(): array {
		if ( null !== $this->data ) {
			return $this->data;
		}

		$upload_dir   = wp_upload_dir();
		$dynamic_file = $upload_dir['basedir'] . '/taxpilot/dynamic-rates.json';
		$static_file  = TAXPILOT_PATH . 'data/static-rates.json';

		$file_to_load = '';

		if ( file_exists( $dynamic_file ) ) {
			$file_to_load = $dynamic_file;
		} elseif ( file_exists( $static_file ) ) {
			$file_to_load = $static_file;
		} else {
			$this->data = [];
			return $this->data;
		}

		$json       = file_get_contents( $file_to_load ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$this->data = json_decode( $json, true ) ?: [];

		return $this->data;
	}
}
