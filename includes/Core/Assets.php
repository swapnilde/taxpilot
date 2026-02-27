<?php
/**
 * Admin asset enqueuing.
 *
 * @package TaxPilot\Core
 */

declare( strict_types=1 );

namespace TaxPilot\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Handles enqueueing admin scripts and styles.
 */
class Assets {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	/**
	 * Enqueue admin scripts and styles on relevant pages only.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		// Only load on TaxPilot admin pages.
		if ( ! $this->is_taxpilot_page( $hook_suffix ) ) {
			return;
		}

		// Admin CSS.
		wp_enqueue_style(
			'taxpilot-admin',
			TAXPILOT_URL . 'assets/css/admin.css',
			[],
			TAXPILOT_VERSION
		);

		// Determine which React app to load.
		if ( $this->is_wizard_page( $hook_suffix ) ) {
			$this->enqueue_react_app( 'wizard' );
		} elseif ( $this->is_dashboard_page( $hook_suffix ) ) {
			$this->enqueue_react_app( 'dashboard' );
		}
	}

	/**
	 * Enqueue a React entry point.
	 *
	 * @param string $entry The entry point name (wizard|dashboard).
	 */
	private function enqueue_react_app( string $entry ): void {
		$asset_file = TAXPILOT_PATH . "build/{$entry}.asset.php";

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			"taxpilot-{$entry}",
			TAXPILOT_URL . "build/{$entry}.js",
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			"taxpilot-{$entry}",
			TAXPILOT_URL . "build/{$entry}.css",
			[ 'wp-components' ],
			$asset['version']
		);

		// Localize with REST info and initial settings.
		wp_localize_script(
			"taxpilot-{$entry}",
			'taxPilotData',
			[
				'restUrl'  => rest_url( 'taxpilot/v1/' ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'adminUrl' => admin_url(),
				'settings' => get_option( 'taxpilot_settings', [] ),
			]
		);
	}

	/**
	 * Check if current page is a TaxPilot admin page.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	private function is_taxpilot_page( string $hook_suffix ): bool {
		return str_contains( $hook_suffix, 'taxpilot' );
	}

	/**
	 * Check if current page is the wizard page.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	private function is_wizard_page( string $hook_suffix ): bool {
		return str_contains( $hook_suffix, 'taxpilot-wizard' );
	}

	/**
	 * Check if current page is the dashboard page.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	private function is_dashboard_page( string $hook_suffix ): bool {
		return str_contains( $hook_suffix, 'taxpilot' ) && ! str_contains( $hook_suffix, 'taxpilot-wizard' ) && ! str_contains( $hook_suffix, 'taxpilot-settings' );
	}
}
