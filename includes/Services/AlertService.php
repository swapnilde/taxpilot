<?php
/**
 * Alert service.
 *
 * @package TaxPilot\Services
 */

declare( strict_types=1 );

namespace TaxPilot\Services;

use TaxPilot\Database\AlertsTable;

/**
 * Manages alert creation and email notifications.
 */
class AlertService {

	/**
	 * Create an alert for rate changes.
	 *
	 * @param array $changes Array of detected changes.
	 */
	public function create_rate_change_alert( array $changes ): void {
		$change_count = count( $changes );
		$countries    = array_unique( array_column( $changes, 'country' ) );

		$title = sprintf(
			/* translators: %d: number of rate changes detected */
			__( '%d tax rate change(s) detected', 'taxpilot' ),
			$change_count
		);
		$message = $this->format_changes_message( $changes );

		AlertsTable::insert(
			'rate_change',
			$title,
			$message,
			$change_count > 3 ? 'critical' : 'warning',
			[
				'changes'   => $changes,
				'countries' => $countries,
			]
		);

		// Send email if enabled.
		$this->maybe_send_email( $title, $message );
	}

	/**
	 * Create a general info alert.
	 *
	 * @param string $title   Alert title.
	 * @param string $message Alert message.
	 */
	public function create_info_alert( string $title, string $message ): void {
		AlertsTable::insert( 'info', $title, $message, 'info' );
	}

	/**
	 * Format changes into a readable message.
	 *
	 * @param array $changes Array of changes.
	 * @return string Formatted message.
	 */
	private function format_changes_message( array $changes ): string {
		$lines = [];

		foreach ( $changes as $change ) {
			$lines[] = sprintf(
				'%s%s: %s rate changed from %.2f%% to %.2f%%',
				$change['country'],
				! empty( $change['state'] ) ? ' (' . $change['state'] . ')' : '',
				$change['rate_name'] ?? $change['rate_type'],
				$change['old_rate'],
				$change['new_rate']
			);
		}

		return implode( "\n", $lines );
	}

	/**
	 * Send email alert if enabled.
	 *
	 * @param string $title   Email subject.
	 * @param string $message Email body.
	 */
	private function maybe_send_email( string $title, string $message ): void {
		$settings = get_option( 'taxpilot_settings', [] );

		if ( empty( $settings['alerts_enabled'] ) ) {
			return;
		}

		$email = $settings['alert_email'] ?? get_option( 'admin_email' );

		if ( empty( $email ) ) {
			return;
		}

		$subject = '[TaxPilot for WooCommerce] ' . $title;
		$body    = sprintf(
			/* translators: 1: alert message, 2: admin URL */
			__( "TaxPilot for WooCommerce has detected tax rate changes:\n\n%1\$s\n\nReview these changes in your dashboard:\n%2\$s", 'taxpilot' ),
			$message,
			admin_url( 'admin.php?page=taxpilot' )
		);

		wp_mail( $email, $subject, $body );
	}
}
