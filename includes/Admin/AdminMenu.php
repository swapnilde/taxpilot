<?php
/**
 * Admin menu registration.
 *
 * @package TaxPilot\Admin
 */

declare( strict_types=1 );

namespace TaxPilot\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the TaxPilot admin menu and submenus.
 */
class AdminMenu {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu_pages' ] );
	}

	/**
	 * Add admin menu pages.
	 */
	public function add_menu_pages(): void {
		// Top-level menu.
		add_menu_page(
			__( 'TaxPilot', 'taxpilot' ),
			__( 'TaxPilot', 'taxpilot' ),
			'manage_woocommerce',
			'taxpilot',
			[ $this, 'render_dashboard' ],
			'dashicons-calculator',
			56 // After WooCommerce.
		);

		// Dashboard submenu (same as parent).
		add_submenu_page(
			'taxpilot',
			__( 'Dashboard', 'taxpilot' ),
			__( 'Dashboard', 'taxpilot' ),
			'manage_woocommerce',
			'taxpilot',
			[ $this, 'render_dashboard' ]
		);

		// Wizard submenu.
		add_submenu_page(
			'taxpilot',
			__( 'Setup Wizard', 'taxpilot' ),
			__( 'Setup Wizard', 'taxpilot' ),
			'manage_woocommerce',
			'taxpilot-wizard',
			[ $this, 'render_wizard' ]
		);

		// Settings submenu.
		add_submenu_page(
			'taxpilot',
			__( 'Settings', 'taxpilot' ),
			__( 'Settings', 'taxpilot' ),
			'manage_woocommerce',
			'taxpilot-settings',
			[ $this, 'render_settings' ]
		);
	}

	/**
	 * Render the dashboard page.
	 */
	public function render_dashboard(): void {
		echo '<div class="taxpilot-wrap">';
		echo '<div class="taxpilot-header">';
		echo '<h1>' . esc_html__( 'TaxPilot Dashboard', 'taxpilot' ) . '</h1>';
		echo '<span class="taxpilot-version">v' . esc_html( TAXPILOT_VERSION ) . '</span>';
		echo '</div>';
		echo '<div id="taxpilot-dashboard-root"></div>';
		echo '</div>';
	}

	/**
	 * Render the wizard page.
	 */
	public function render_wizard(): void {
		echo '<div class="taxpilot-wrap">';
		echo '<div class="taxpilot-header">';
		echo '<h1>' . esc_html__( 'Tax Setup Wizard', 'taxpilot' ) . '</h1>';
		echo '<span class="taxpilot-version">v' . esc_html( TAXPILOT_VERSION ) . '</span>';
		echo '</div>';
		echo '<div id="taxpilot-wizard-root"></div>';
		echo '</div>';
	}

	/**
	 * Render the settings page (basic PHP-based, premium settings).
	 */
	public function render_settings(): void {
		// Handle form submission.
		if ( isset( $_POST['taxpilot_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['taxpilot_settings_nonce'] ) ), 'taxpilot_save_settings' ) ) {
			$this->save_settings();
		}

		$settings = get_option( 'taxpilot_settings', [] );

		echo '<div class="taxpilot-wrap">';
		echo '<div class="taxpilot-header">';
		echo '<h1>' . esc_html__( 'TaxPilot Settings', 'taxpilot' ) . '</h1>';
		echo '<span class="taxpilot-version">v' . esc_html( TAXPILOT_VERSION ) . '</span>';
		echo '</div>';

		echo '<div class="taxpilot-card">';
		echo '<form method="post" action="">';
		wp_nonce_field( 'taxpilot_save_settings', 'taxpilot_settings_nonce' );

		// API Provider.
		echo '<div class="taxpilot-field">';
		echo '<label for="api_provider">' . esc_html__( 'Tax Rate Source', 'taxpilot' ) . '</label>';
		echo '<select id="api_provider" name="api_provider" class="regular-text">';
		echo '<option value="static"' . selected( $settings['api_provider'] ?? 'static', 'static', false ) . '>' . esc_html__( 'Static Bundle', 'taxpilot' ) . '</option>';
		echo '<option value="vatsense"' . selected( $settings['api_provider'] ?? '', 'vatsense', false ) . '>' . esc_html__( 'VATSense API', 'taxpilot' ) . '</option>';
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Choose the source for tax rate data.', 'taxpilot' ) . '</p>';
		echo '</div>';

		// API Key.
		echo '<div class="taxpilot-field">';
		echo '<label for="api_key">' . esc_html__( 'API Key', 'taxpilot' ) . '</label>';
		echo '<input type="password" id="api_key" name="api_key" value="' . esc_attr( $settings['api_key'] ?? '' ) . '" class="regular-text" />';
		echo '<p class="description">' . esc_html__( 'Required for VATSense API. Your key is stored encrypted.', 'taxpilot' ) . '</p>';
		echo '</div>';

		// Alert Email.
		echo '<div class="taxpilot-field">';
		echo '<label for="alert_email">' . esc_html__( 'Alert Email', 'taxpilot' ) . '</label>';
		echo '<input type="email" id="alert_email" name="alert_email" value="' . esc_attr( $settings['alert_email'] ?? '' ) . '" class="regular-text" />';
		echo '<p class="description">' . esc_html__( 'Email address for tax rate change alerts.', 'taxpilot' ) . '</p>';
		echo '</div>';

		// Alerts Enabled.
		echo '<div class="taxpilot-field">';
		echo '<label>';
		echo '<input type="checkbox" name="alerts_enabled" value="1"' . checked( $settings['alerts_enabled'] ?? true, true, false ) . ' /> ';
		echo esc_html__( 'Enable email alerts for tax rate changes', 'taxpilot' );
		echo '</label>';
		echo '</div>';

		// Refresh Interval.
		echo '<div class="taxpilot-field">';
		echo '<label for="refresh_interval">' . esc_html__( 'Rate Refresh Interval', 'taxpilot' ) . '</label>';
		echo '<select id="refresh_interval" name="refresh_interval" class="regular-text">';
		echo '<option value="daily"' . selected( $settings['refresh_interval'] ?? 'daily', 'daily', false ) . '>' . esc_html__( 'Daily', 'taxpilot' ) . '</option>';
		echo '<option value="weekly"' . selected( $settings['refresh_interval'] ?? '', 'weekly', false ) . '>' . esc_html__( 'Weekly', 'taxpilot' ) . '</option>';
		echo '<option value="manual"' . selected( $settings['refresh_interval'] ?? '', 'manual', false ) . '>' . esc_html__( 'Manual Only', 'taxpilot' ) . '</option>';
		echo '</select>';
		echo '</div>';

		submit_button( __( 'Save Settings', 'taxpilot' ) );

		echo '</form>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Save settings from POST data.
	 */
	private function save_settings(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( ! isset( $_POST['taxpilot_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['taxpilot_settings_nonce'] ) ), 'taxpilot_save_settings' ) ) {
			return;
		}

		$settings = get_option( 'taxpilot_settings', [] );

		$settings['api_provider']     = sanitize_text_field( wp_unslash( $_POST['api_provider'] ?? 'static' ) );
		$settings['api_key']          = sanitize_text_field( wp_unslash( $_POST['api_key'] ?? '' ) );
		$settings['alert_email']      = sanitize_email( wp_unslash( $_POST['alert_email'] ?? '' ) );
		$settings['alerts_enabled']   = isset( $_POST['alerts_enabled'] );
		$settings['refresh_interval'] = sanitize_text_field( wp_unslash( $_POST['refresh_interval'] ?? 'daily' ) );

		update_option( 'taxpilot_settings', $settings );

		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'taxpilot' ) . '</p></div>';
			}
		);
	}
}
