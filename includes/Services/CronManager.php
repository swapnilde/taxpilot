<?php
/**
 * Cron job manager.
 *
 * @package TaxPilot\Services
 */

declare( strict_types=1 );

namespace TaxPilot\Services;

defined( 'ABSPATH' ) || exit;

use TaxPilot\Database\LogsTable;

/**
 * Manages scheduled cron jobs for rate monitoring.
 */
class CronManager {

	/**
	 * Register cron hooks.
	 */
	public function register(): void {
		add_action( 'taxpilot_daily_rate_check', [ $this, 'handle_daily_rate_check' ] );
		add_action( 'taxpilot_weekly_report', [ $this, 'handle_weekly_report' ] );
	}

	/**
	 * Handle daily rate check — detect changes and create alerts.
	 */
	public function handle_daily_rate_check(): void {
		$settings  = get_option( 'taxpilot_settings', [] );
		$countries = $settings['target_countries'] ?? [];

		if ( empty( $countries ) ) {
			return;
		}

		// Skip if manual refresh is configured.
		if ( 'manual' === ( $settings['refresh_interval'] ?? 'daily' ) ) {
			return;
		}

		$service = new TaxRateService();
		$changes = $service->detect_changes( $countries );

		if ( ! empty( $changes ) ) {
			$alert_service = new AlertService();
			$alert_service->create_rate_change_alert( $changes );

			LogsTable::insert(
				'cron_rate_changes_detected',
				wp_json_encode(
					[
						'changes' => count( $changes ),
						'details' => $changes,
					]
				),
				'warning'
			);
		} else {
			LogsTable::insert(
				'cron_rate_check_clean',
				wp_json_encode( [ 'countries_checked' => count( $countries ) ] )
			);
		}
	}

	/**
	 * Handle weekly report — cleanup old data.
	 */
	public function handle_weekly_report(): void {
		// Cleanup old logs (90 days).
		$deleted_logs = LogsTable::cleanup( 90 );

		// Cleanup old alerts (180 days).
		$deleted_alerts = \TaxPilot\Database\AlertsTable::cleanup( 180 );

		LogsTable::insert(
			'cron_weekly_cleanup',
			wp_json_encode(
				[
					'deleted_logs'   => $deleted_logs,
					'deleted_alerts' => $deleted_alerts,
				]
			)
		);
	}
}
