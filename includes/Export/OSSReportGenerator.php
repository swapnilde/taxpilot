<?php
/**
 * EU OSS Report Generator.
 *
 * @package TaxPilot\Export
 */

declare( strict_types=1 );

namespace TaxPilot\Export;

defined( 'ABSPATH' ) || exit;

/**
 * Handles generating data for EU OSS (One-Stop Shop) quarterly returns.
 */
class OSSReportGenerator {

	/**
	 * List of the 27 EU Member States.
	 *
	 * @var array
	 */
	private const EU_MEMBER_STATES = [
		'AT',
		'BE',
		'BG',
		'CY',
		'CZ',
		'DE',
		'DK',
		'EE',
		'ES',
		'FI',
		'FR',
		'GR',
		'HR',
		'HU',
		'IE',
		'IT',
		'LT',
		'LU',
		'LV',
		'MT',
		'NL',
		'PL',
		'PT',
		'RO',
		'SE',
		'SI',
		'SK',
	];

	/**
	 * Generate aggregated OSS data for a specific year and quarter.
	 *
	 * @param int $year    The year (e.g., 2024).
	 * @param int $quarter The quarter (1-4).
	 * @return array Aggregated OSS data.
	 */
	public function generate_oss_data( int $year, int $quarter ): array {
		$start_month = ( $quarter - 1 ) * 3 + 1;
		$end_month   = $start_month + 2;

		$start_date = sprintf( '%04d-%02d-01 00:00:00', $year, $start_month );
		$end_date   = gmdate( 'Y-m-t 23:59:59', strtotime( sprintf( '%04d-%02d-01', $year, $end_month ) ) );

		// Get all completed/processing orders within the quarter.
		$args = [
			'status'       => [ 'wc-completed', 'wc-processing' ],
			'limit'        => -1,
			'type'         => 'shop_order',
			'date_created' => $start_date . '...' . $end_date,
		];

		$orders = wc_get_orders( $args );
		$report = [];

		foreach ( $orders as $order ) {
			// Skip if not shipping to EU.
			$country = $order->get_shipping_country();
			if ( empty( $country ) ) {
				$country = $order->get_billing_country();
			}

			if ( ! in_array( $country, self::EU_MEMBER_STATES, true ) ) {
				continue;
			}

			// Skip if it is a B2B order (has a VAT number).
			$vat_number = $order->get_meta( '_billing_vat_number' );
			if ( ! empty( $vat_number ) ) {
				continue;
			}

			// Aggregate taxes.
			$taxes = $order->get_taxes();

			// If no taxes were charged, skip (could be zero-rated or exempt B2B we missed).
			if ( empty( $taxes ) ) {
				continue;
			}

			foreach ( $taxes as $tax_item ) {
				$tax_rate_id = $tax_item->get_rate_id();
				$tax_rate    = \WC_Tax::get_rate_percent_value( $tax_rate_id );

				// Standardize rate formatting (e.g., 19.00 -> 19).
				$rate_key = (string) round( (float) $tax_rate, 2 );

				$key = $country . '_' . $rate_key;

				if ( ! isset( $report[ $key ] ) ) {
					$report[ $key ] = [
						'country'       => $country,
						'tax_rate'      => $rate_key,
						'taxable_sales' => 0.0,
						'vat_collected' => 0.0,
					];
				}

				// The tax item contains the exact tax amount.
				$tax_amount = (float) $tax_item->get_tax_total() + (float) $tax_item->get_shipping_tax_total();

				// We must calculate the taxable basis (sales amount that generated this tax).
				// If rate is 0, we can't reverse-calculate, but we shouldn't have 0% in OSS usually.
				$taxable_sales = 0.0;
				if ( (float) $tax_rate > 0 ) {
					$taxable_sales = $tax_amount / ( (float) $tax_rate / 100 );
				}

				$report[ $key ]['taxable_sales'] += $taxable_sales;
				$report[ $key ]['vat_collected'] += $tax_amount;
			}
		}

		// Sort alphabetically by country, then rate.
		ksort( $report );

		return array_values( $report );
	}
}
