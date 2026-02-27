<?php
/**
 * CSV compliance report exporter.
 *
 * @package TaxPilot\Export
 */

declare( strict_types=1 );

namespace TaxPilot\Export;

use TaxPilot\Database\RatesTable;

/**
 * Exports tax rates as a CSV compliance report.
 */
class CSVExporter {

	/**
	 * Generate and output a CSV file.
	 */
	public function export(): void {
		$rates = RatesTable::get_all( [ 'limit' => 10000 ] );

		$filename = 'taxpilot-compliance-report-' . gmdate( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// Header row.
		fputcsv(
			$output,
			[
				'Country Code',
				'State',
				'City',
				'Postcode',
				'Rate (%)',
				'Rate Name',
				'Rate Type',
				'Tax Class',
				'Priority',
				'Compound',
				'Shipping Taxable',
				'Source',
				'Last Updated',
			]
		);

		// Data rows.
		foreach ( $rates as $rate ) {
			fputcsv(
				$output,
				[
					$rate['country_code'],
					$rate['state'],
					$rate['city'],
					$rate['postcode'],
					$rate['rate'],
					$rate['rate_name'],
					$rate['rate_type'],
					$rate['tax_class'] ?: 'Standard',
					$rate['priority'],
					$rate['compound'] ? 'Yes' : 'No',
					$rate['shipping'] ? 'Yes' : 'No',
					$rate['source'],
					$rate['updated_at'],
				]
			);
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- writing to php://output
	}
}
