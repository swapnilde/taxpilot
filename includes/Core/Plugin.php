<?php
/**
 * Plugin orchestrator.
 *
 * @package TaxZen\Core
 */

declare( strict_types=1 );

namespace TaxZen\Core;

defined( 'ABSPATH' ) || exit;

use TaxZen\Admin\AdminMenu;
use TaxZen\Admin\SettingsPage;
use TaxZen\API\WizardEndpoints;
use TaxZen\API\RatesEndpoints;
use TaxZen\API\ReportsEndpoints;
use TaxZen\Services\CronManager;
use TaxZen\Integration\WooIntegration;

/**
 * Main Plugin class — singleton orchestrator.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Whether the plugin has been initialized.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Get singleton instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {}

	/**
	 * Initialize the plugin — register all hooks and services.
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}
		$this->initialized = true;

		// Admin menu & pages.
		( new AdminMenu() )->register();

		// Enqueue admin assets.
		( new Assets() )->register();

		// WooCommerce settings tab.
		( new SettingsPage() )->register();

		// REST API endpoints.
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

		// Cron jobs.
		( new CronManager() )->register();

		// WooCommerce deep integration.
		( new WooIntegration() )->register();
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes(): void {
		( new WizardEndpoints() )->register_routes();
		( new RatesEndpoints() )->register_routes();
		( new ReportsEndpoints() )->register_routes();
	}
}
