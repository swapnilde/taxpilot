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
