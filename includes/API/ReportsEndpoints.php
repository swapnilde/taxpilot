<?php
/**
 * Reports REST API endpoints.
 *
 * @package TaxPilot\API
 */

declare( strict_types=1 );

namespace TaxPilot\API;

use TaxPilot\Database\RatesTable;
use TaxPilot\Database\AlertsTable;
use TaxPilot\Export\CSVExporter;
use TaxPilot\Export\PDFExporter;
use TaxPilot\Export\OSSReportGenerator;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST endpoints for reports and exports.
 */
class ReportsEndpoints extends WP_REST_Controller {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'taxpilot/v1';

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		// Export CSV.
		register_rest_route(
			$this->namespace,
			'/reports/csv',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'export_csv' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		// Export PDF.
		register_rest_route(
			$this->namespace,
			'/reports/pdf',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'export_pdf' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		// Export OSS CSV.
		register_rest_route(
			$this->namespace,
			'/reports/oss/csv',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'export_oss_csv' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'year'    => [
						'type'     => 'integer',
						'required' => true,
					],
					'quarter' => [
						'type'     => 'integer',
						'required' => true,
					],
				],
			]
		);

		// Get alerts.
		register_rest_route(
			$this->namespace,
			'/alerts',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_alerts' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'limit'       => [
						'type'    => 'integer',
						'default' => 20,
					],
					'unread_only' => [
						'type'    => 'boolean',
						'default' => false,
					],
				],
			]
		);

		// Mark alert as read.
		register_rest_route(
			$this->namespace,
			'/alerts/(?P<id>\d+)/read',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'mark_alert_read' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		// Mark all alerts as read.
		register_rest_route(
			$this->namespace,
			'/alerts/read-all',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'mark_all_read' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		// Unread alert count.
		register_rest_route(
			$this->namespace,
			'/alerts/unread-count',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'unread_count' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);
	}

	/**
	 * Permission check.
	 */
	public function check_permissions(): bool {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Export rates as CSV.
	 */
	public function export_csv(): void {
		$exporter = new CSVExporter();
		$exporter->export();
		exit; // CSVExporter sends headers and echoes content directly.
	}

	/**
	 * Export rates as PDF.
	 */
	public function export_pdf(): void {
		$exporter = new PDFExporter();
		$exporter->export();
		exit; // PDFExporter sends headers and echoes content directly.
	}

	/**
	 * Export OSS Report as CSV.
	 *
	 * @param WP_REST_Request $request The REST request.
	 */
	public function export_oss_csv( WP_REST_Request $request ): void {
		$year    = (int) $request->get_param( 'year' );
		$quarter = (int) $request->get_param( 'quarter' );

		$generator = new OSSReportGenerator();
		$data      = $generator->generate_oss_data( $year, $quarter );

		$filename = sprintf( 'taxpilot-oss-report-Q%d-%d.csv', $quarter, $year );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			exit;
		}

		// UTF-8 BOM for Excel.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fwrite( $output, "\xEF\xBB\xBF" );

		// Headers.
		fputcsv( $output, [ 'Destination Country', 'Tax Rate (%)', 'Taxable Sales (EUR)', 'VAT Collected (EUR)' ] );

		foreach ( $data as $row ) {
			fputcsv(
				$output,
				[
					$row['country'],
					$row['tax_rate'],
					number_format( $row['taxable_sales'], 2, '.', '' ),
					number_format( $row['vat_collected'], 2, '.', '' ),
				]
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $output );
		exit;
	}

	/**
	 * Get alerts.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function get_alerts( WP_REST_Request $request ): WP_REST_Response {
		$limit       = (int) $request->get_param( 'limit' );
		$unread_only = (bool) $request->get_param( 'unread_only' );

		$alerts = AlertsTable::get_recent( $limit, $unread_only );

		return new WP_REST_Response(
			[
				'alerts'       => $alerts,
				'unread_count' => AlertsTable::unread_count(),
			],
			200
		);
	}

	/**
	 * Mark an alert as read.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function mark_alert_read( WP_REST_Request $request ): WP_REST_Response {
		$id = (int) $request->get_param( 'id' );
		AlertsTable::mark_read( $id );

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * Mark all alerts as read.
	 */
	public function mark_all_read(): WP_REST_Response {
		$count = AlertsTable::mark_all_read();

		return new WP_REST_Response(
			[
				'success' => true,
				'marked'  => $count,
			],
			200
		);
	}

	/**
	 * Get unread alert count.
	 */
	public function unread_count(): WP_REST_Response {
		return new WP_REST_Response( [ 'count' => AlertsTable::unread_count() ], 200 );
	}
}
