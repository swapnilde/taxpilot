<?php
/**
 * Plugin Name:       Taxzen for WooCommerce
 * Plugin URI:        https://swapnild.com/taxzen-for-woocommerce
 * Description:       Smart tax configuration wizard for WooCommerce — auto-detect rates, one-click setup, compliance monitoring & alerts.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      8.2
 * Requires Plugins:  woocommerce
 * Author:            Swapnil Deshpande
 * Author URI:        https://swapnild.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       taxzen-for-woocommerce
 * Domain Path:       /languages
 * WC requires at least: 8.0
 * WC tested up to:   9.5
 *
 * @package TaxZen
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'TAXZEN_VERSION', '1.0.0' );
define( 'TAXZEN_FILE', __FILE__ );
define( 'TAXZEN_PATH', plugin_dir_path( __FILE__ ) );
define( 'TAXZEN_URL', plugin_dir_url( __FILE__ ) );
define( 'TAXZEN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader: PSR-4 via Composer.
 */
if ( file_exists( TAXZEN_PATH . 'vendor/autoload.php' ) ) {
	require_once TAXZEN_PATH . 'vendor/autoload.php';
}

/**
 * Check WooCommerce dependency before bootstrapping.
 */
function taxzen_wc_check_dependencies(): bool {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'taxzen_wc_missing_notice' );
		return false;
	}
	return true;
}

/**
 * Admin notice when WooCommerce is not active.
 */
function taxzen_wc_missing_notice(): void {
	?>
	<div class="notice notice-error">
		<p>
			<strong><?php esc_html_e( 'Taxzen for WooCommerce', 'taxzen-for-woocommerce' ); ?></strong>:
			<?php esc_html_e( 'This plugin requires WooCommerce to be installed and active.', 'taxzen-for-woocommerce' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Activation hook.
 */
register_activation_hook( __FILE__, [ \TaxZen\Core\Activator::class, 'activate' ] );

/**
 * Deactivation hook.
 */
register_deactivation_hook( __FILE__, [ \TaxZen\Core\Deactivator::class, 'deactivate' ] );

/**
 * HPOS compatibility declaration.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

/**
 * Bootstrap the plugin after all plugins are loaded.
 */
add_action(
	'plugins_loaded',
	function () {
		if ( ! taxzen_wc_check_dependencies() ) {
			return;
		}
		\TaxZen\Core\Plugin::instance()->init();
	}
);
