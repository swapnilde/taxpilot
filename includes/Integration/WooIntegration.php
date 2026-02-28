<?php
/**
 * Deep WooCommerce tax integration.
 *
 * Hooks into WooCommerce's tax lifecycle — tax classes, rate matching,
 * shipping tax, VAT exemption, order meta, admin notices, and reporting.
 *
 * @package TaxPilot\Integration
 */

declare( strict_types=1 );

namespace TaxPilot\Integration;

defined( 'ABSPATH' ) || exit;

use TaxPilot\Database\RatesTable;
use TaxPilot\Database\AlertsTable;
use TaxPilot\Services\VIESValidator;
use TaxPilot\Services\AddressValidator;

/**
 * Registers all WooCommerce integration hooks.
 */
class WooIntegration {

	/**
	 * Tax classes managed by TaxPilot.
	 *
	 * @var array
	 */
	private const MANAGED_TAX_CLASSES = [
		'digital-goods' => 'Digital Goods',
		'services'      => 'Services',
	];

	/**
	 * Tax classes that are always registered regardless of product type selection.
	 *
	 * @var array
	 */
	private const ALWAYS_TAX_CLASSES = [
		'Reduced rate',
		'Zero rate',
	];

	/**
	 * Register all WooCommerce hooks.
	 */
	public function register(): void {
		// --- Tax Class Management ---
		add_filter( 'woocommerce_tax_classes', [ $this, 'register_tax_classes' ] );

		// --- Rate Matching / Checkout Tax ---
		add_filter( 'woocommerce_matched_tax_rates', [ $this, 'filter_matched_rates' ], 10, 6 );

		// --- Shipping Tax ---
		add_filter( 'woocommerce_shipping_tax_class', [ $this, 'shipping_tax_class' ] );

		// --- EU VAT Exemption (B2B) ---
		add_filter( 'woocommerce_customer_is_vat_exempt', [ $this, 'check_vat_exemption' ], 10, 2 );
		add_action( 'woocommerce_checkout_update_order_review', [ $this, 'update_vat_exemption' ] );

		// --- VAT Number Field at Checkout ---
		add_filter( 'woocommerce_billing_fields', [ $this, 'add_vat_number_field' ] );

		// --- Order Meta (Audit Trail + VAT number) ---
		add_action( 'woocommerce_checkout_order_created', [ $this, 'stamp_order_meta' ] );

		// --- Admin Notices ---
		add_action( 'admin_notices', [ $this, 'render_admin_notices' ] );

		// --- WooCommerce Tax Settings Auto-Configuration ---
		add_action( 'woocommerce_tax_settings', [ $this, 'add_taxpilot_settings_note' ] );

		// --- Tax Display on Order Admin ---
		add_action( 'woocommerce_admin_order_data_after_billing_address', [ $this, 'show_tax_meta_on_order' ] );

		// --- Digital Goods: Charge tax based on customer location ---
		add_filter( 'woocommerce_product_tax_class', [ $this, 'digital_product_tax_class' ], 10, 2 );

		// --- Ensure tax is enabled ---
		add_action( 'admin_init', [ $this, 'maybe_enable_taxes' ] );

		// --- Tax Report Data ---
		add_filter( 'woocommerce_admin_reports', [ $this, 'add_tax_report_tab' ] );

		// --- Order List Admin Columns (Legacy & HPOS) ---
		add_filter( 'manage_edit-shop_order_columns', [ $this, 'add_order_tax_column' ] );
		add_action( 'manage_shop_order_posts_custom_column', [ $this, 'render_order_tax_column' ], 10, 2 );
		add_filter( 'manage_woocommerce_page_wc-orders_columns', [ $this, 'add_order_tax_column' ] );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', [ $this, 'render_order_tax_column_hpos' ], 10, 2 );

		// --- Smart Address Validation ---
		add_action( 'woocommerce_after_checkout_validation', [ $this, 'validate_checkout_address' ], 10, 2 );
	}

	/*
	 * ──────────────────────────────────
	 *  TAX CLASS MANAGEMENT
	 * ──────────────────────────────────
	 */

	/**
	 * Ensure TaxPilot-managed tax classes always appear.
	 *
	 * @param array $classes Existing tax classes.
	 * @return array Modified classes.
	 */
	public function register_tax_classes( array $classes ): array {
		$settings = get_option( 'taxpilot_settings', [] );
		$types    = $settings['product_types'] ?? [];

		// Always-on classes: Reduced rate, Zero rate.
		foreach ( self::ALWAYS_TAX_CLASSES as $label ) {
			if ( ! in_array( $label, $classes, true ) ) {
				$classes[] = $label;
			}
		}

		// Conditional classes based on wizard product type selections.
		foreach ( self::MANAGED_TAX_CLASSES as $slug => $label ) {
			$type_key = str_replace( '-', '_', str_replace( '-goods', '', $slug ) );
			if ( in_array( $type_key, $types, true ) || in_array( str_replace( '-', '_', $slug ), $types, true ) ) {
				if ( ! in_array( $label, $classes, true ) ) {
					$classes[] = $label;
				}
			}
		}

		return $classes;
	}

	/*
	 * ──────────────────────────────────
	 *  CHECKOUT RATE MATCHING
	 * ──────────────────────────────────
	 */

	/**
	 * Filter matched tax rates at checkout to ensure TaxPilot rates are applied.
	 *
	 * If WooCommerce already found rates via its standard lookup, we verify them.
	 * If no rates exist for a country, we fall back to our stored rates.
	 *
	 * @param array  $matched_tax_rates Rates WooCommerce matched.
	 * @param string $country           Customer country.
	 * @param string $state             Customer state.
	 * @param string $postcode          Customer postcode.
	 * @param string $city              Customer city.
	 * @param string $tax_class         Tax class being looked up.
	 * @return array Possibly modified rates.
	 */
	public function filter_matched_rates( $matched_tax_rates, $country, $state, $postcode, $city, $tax_class ): array {
		// If WooCommerce already found rates, trust them — they came from our sync.
		if ( ! empty( $matched_tax_rates ) ) {
			return $matched_tax_rates;
		}

		// Fallback: look up our stored rates for this country/state.
		$our_rates = RatesTable::get_by_country( $country );

		if ( empty( $our_rates ) ) {
			return $matched_tax_rates;
		}

		foreach ( $our_rates as $rate ) {
			// Match state if specified.
			if ( ! empty( $rate['state'] ) && strtoupper( $rate['state'] ) !== strtoupper( $state ) ) {
				continue;
			}

			// Match tax class.
			$rate_class = $rate['tax_class'] ?? '';
			if ( $rate_class !== $tax_class ) {
				continue;
			}

			$rate_id = $rate['woo_tax_rate_id'] ?? $rate['id'];

			$matched_tax_rates[ $rate_id ] = [
				'rate'     => (float) $rate['rate'],
				'label'    => $rate['rate_name'] ?? 'Tax',
				'shipping' => $rate['shipping'] ?? 'yes',
				'compound' => $rate['compound'] ?? 'no',
			];
		}

		return $matched_tax_rates;
	}

	/*
	 * ──────────────────────────────────
	 *  SHIPPING TAX
	 * ──────────────────────────────────
	 */

	/**
	 * Use the standard tax class for shipping unless cart has digital-only items.
	 *
	 * @param string $shipping_class Current shipping tax class.
	 * @return string Tax class for shipping.
	 */
	public function shipping_tax_class( $shipping_class ): string {
		if ( ! WC()->cart ) {
			return $shipping_class;
		}

		$has_physical = false;

		foreach ( WC()->cart->get_cart() as $item ) {
			$product = $item['data'] ?? null;
			if ( $product && ! $product->is_virtual() && ! $product->is_downloadable() ) {
				$has_physical = true;
				break;
			}
		}

		// If all items are digital/virtual, don't tax shipping.
		if ( ! $has_physical ) {
			return 'zero-rate';
		}

		// Use standard rate for physical goods shipping.
		return '';
	}

	/*
	 * ──────────────────────────────────
	 *  EU VAT EXEMPTION (B2B)
	 * ──────────────────────────────────
	 */

	/**
	 * Check if customer is VAT-exempt via VIES validation.
	 *
	 * @param bool   $is_exempt Current exemption status.
	 * @param object $customer  WC Customer object (unused, required by filter signature).
	 * @return bool
	 */
	public function check_vat_exemption( $is_exempt, $customer ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by filter signature.
		if ( $is_exempt ) {
			return true;
		}

		// Check if there's a validated VAT number in the session.
		$vat_valid = WC()->session ? WC()->session->get( 'taxpilot_vat_exempt' ) : false;

		return (bool) $vat_valid;
	}

	/**
	 * Validate VAT number when checkout is updated.
	 *
	 * @param string $posted_data Serialized form data.
	 */
	public function update_vat_exemption( $posted_data ): void {
		parse_str( $posted_data, $data );

		$vat_number = sanitize_text_field( $data['billing_vat_number'] ?? '' );

		if ( empty( $vat_number ) ) {
			if ( WC()->session ) {
				WC()->session->set( 'taxpilot_vat_exempt', false );
			}
			if ( WC()->customer ) {
				WC()->customer->set_is_vat_exempt( false );
			}
			return;
		}

		// Check EU countries only.
		$billing_country = sanitize_text_field( $data['billing_country'] ?? '' );
		$eu_countries    = WC()->countries->get_european_union_countries();

		if ( ! in_array( $billing_country, $eu_countries, true ) ) {
			WC()->session->set( 'taxpilot_vat_exempt', false );
			return;
		}

		// Validate via VIES.
		$result = VIESValidator::validate( $vat_number );

		$is_valid = $result['valid'] ?? false;
		WC()->session->set( 'taxpilot_vat_exempt', $is_valid );

		if ( WC()->customer ) {
			WC()->customer->set_is_vat_exempt( $is_valid );
		}
	}

	/**
	 * Add VAT number field to checkout billing fields.
	 *
	 * @param array $fields Billing fields.
	 * @return array Modified fields.
	 */
	public function add_vat_number_field( array $fields ): array {
		$settings = get_option( 'taxpilot_settings', [] );
		$targets  = $settings['target_countries'] ?? [];

		// Only show if selling to EU countries.
		$eu_countries = function_exists( 'WC' ) && WC()->countries
			? WC()->countries->get_european_union_countries()
			: [];

		$has_eu = ! empty( array_intersect( $targets, $eu_countries ) );

		if ( ! $has_eu ) {
			return $fields;
		}

		$fields['billing_vat_number'] = [
			'type'        => 'text',
			'label'       => __( 'EU VAT Number', 'taxpilot' ),
			'placeholder' => __( 'e.g. DE123456789', 'taxpilot' ),
			'required'    => false,
			'class'       => [ 'form-row-wide' ],
			'priority'    => 35,
			'description' => __( 'Enter your VAT number for B2B tax exemption.', 'taxpilot' ),
		];

		return $fields;
	}

	/*
	 * ──────────────────────────────────
	 *  ORDER META (AUDIT TRAIL)
	 * ──────────────────────────────────
	 */

	/**
	 * Stamp TaxPilot metadata on new orders.
	 *
	 * @param \WC_Order $order The order object.
	 */
	public function stamp_order_meta( $order ): void {
		$order->update_meta_data( '_taxpilot_version', TAXPILOT_VERSION );
		$order->update_meta_data( '_taxpilot_rates_source', get_option( 'taxpilot_settings', [] )['api_provider'] ?? 'static' );
		$order->update_meta_data( '_taxpilot_rates_updated', get_option( 'taxpilot_rates_last_updated', '' ) );

		// Save VAT number if provided.
		$vat_number = isset( $_POST['billing_vat_number'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			? sanitize_text_field( wp_unslash( $_POST['billing_vat_number'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			: '';

		if ( $vat_number ) {
			$order->update_meta_data( '_billing_vat_number', $vat_number );
			$order->update_meta_data( '_taxpilot_vat_exempt', 'yes' );
		} elseif ( WC()->session && WC()->session->get( 'taxpilot_vat_exempt' ) ) {
			// Fallback for Block Checkout where $_POST may not contain the field.
			$order->update_meta_data( '_taxpilot_vat_exempt', 'yes' );
		}

		$order->save();
	}

	/**
	 * Display TaxPilot tax info on order admin page.
	 *
	 * @param \WC_Order $order The order.
	 */
	public function show_tax_meta_on_order( $order ): void {
		$version    = $order->get_meta( '_taxpilot_version' );
		$source     = $order->get_meta( '_taxpilot_rates_source' );
		$vat_number = $order->get_meta( '_billing_vat_number' );
		$vat_exempt = $order->get_meta( '_taxpilot_vat_exempt' );

		if ( ! $version ) {
			return;
		}

		echo '<div class="taxpilot-order-meta" style="margin-top:12px;padding:10px;background:#f0f0f1;border-radius:6px;">';
		echo '<h4 style="margin:0 0 8px 0;color:#4f46e5;">🧙 TaxPilot</h4>';
		echo '<p style="margin:2px 0;font-size:13px;"><strong>' . esc_html__( 'Tax Source:', 'taxpilot' ) . '</strong> ' . esc_html( ucfirst( $source ?: 'N/A' ) ) . '</p>';

		if ( $vat_number ) {
			echo '<p style="margin:2px 0;font-size:13px;"><strong>' . esc_html__( 'VAT Number:', 'taxpilot' ) . '</strong> ' . esc_html( $vat_number ) . '</p>';
		}

		if ( 'yes' === $vat_exempt ) {
			echo '<p style="margin:2px 0;font-size:13px;color:#059669;"><strong>✓ ' . esc_html__( 'VAT Exempt (B2B)', 'taxpilot' ) . '</strong></p>';
		}

		echo '</div>';
	}

	/**
	 * Add custom column to WooCommerce Orders list.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_order_tax_column( array $columns ): array {
		$columns['taxpilot_source'] = __( 'Tax Source', 'taxpilot' );
		return $columns;
	}

	/**
	 * Render the custom column for Legacy Orders.
	 *
	 * @param string $column_name Column name.
	 * @param int    $post_id     Post ID.
	 */
	public function render_order_tax_column( string $column_name, int $post_id ): void {
		if ( 'taxpilot_source' === $column_name ) {
			$order = wc_get_order( $post_id );
			if ( $order ) {
				$this->output_tax_column_content( $order );
			}
		}
	}

	/**
	 * Render the custom column for HPOS Orders.
	 *
	 * @param string    $column_name Column name.
	 * @param \WC_Order $order       The order object.
	 */
	public function render_order_tax_column_hpos( string $column_name, $order ): void {
		if ( 'taxpilot_source' === $column_name ) {
			$this->output_tax_column_content( $order );
		}
	}

	/**
	 * Main output logic for the Tax Source column.
	 *
	 * @param \WC_Order $order The order object.
	 */
	private function output_tax_column_content( $order ): void {
		$version = $order->get_meta( '_taxpilot_version' );
		$source  = $order->get_meta( '_taxpilot_rates_source' );

		if ( $version && $source ) {
			echo '<mark class="order-status status-completed tips" data-tip="' . esc_attr__( 'Processed by TaxPilot', 'taxpilot' ) . '">';
			echo '<span>🧙 ' . esc_html( ucfirst( $source ) ) . '</span>';
			echo '</mark>';
		} else {
			echo '<span class="na">&ndash;</span>';
		}
	}

	/**
	 * Register a custom TaxPilot tab in WooCommerce Reports.
	 *
	 * @param array $reports Existing reports.
	 * @return array Modified reports.
	 */
	public function add_tax_report_tab( array $reports ): array {
		if ( isset( $reports['taxes'] ) ) {
			$reports['taxes']['reports']['taxpilot'] = [
				'title'       => __( 'TaxPilot Usage', 'taxpilot' ),
				'description' => __( 'Overview of orders processed with TaxPilot.', 'taxpilot' ),
				'hide_title'  => true,
				'callback'    => [ $this, 'render_tax_report_page' ],
			];
		}
		return $reports;
	}

	/**
	 * Render the TaxPilot report page content within WooCommerce Reports.
	 */
	public function render_tax_report_page(): void {
		echo '<div id="poststuff" class="woocommerce-reports-wide">';
		echo '<div class="postbox">';
		echo '<h3 class="hndle"><span>' . esc_html__( 'TaxPilot Usage Report', 'taxpilot' ) . '</span></h3>';
		echo '<div class="inside">';
		echo '<p>' . esc_html__( 'This report shows the impact of TaxPilot on your store\'s tax collection.', 'taxpilot' ) . '</p>';
		echo '<p><em>' . esc_html__( 'Summary metrics will populate here as new orders are processed using TaxPilot rates.', 'taxpilot' ) . '</em></p>';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=taxpilot' ) ) . '" class="button button-primary">' . esc_html__( 'View Full TaxPilot Dashboard', 'taxpilot' ) . '</a>';
		echo '</div></div></div>';
	}

	/*
	 * ──────────────────────────────────
	 *  DIGITAL GOODS TAX CLASS
	 * ──────────────────────────────────
	 */

	/**
	 * Auto-assign digital goods tax class to virtual/downloadable products.
	 *
	 * @param string      $tax_class Current tax class.
	 * @param \WC_Product $product   The product.
	 * @return string Modified tax class.
	 */
	public function digital_product_tax_class( $tax_class, $product ): string {
		// Only auto-assign if product doesn't already have a custom tax class.
		if ( ! empty( $tax_class ) ) {
			return $tax_class;
		}

		// Check if digital goods class was set up via the wizard.
		$settings = get_option( 'taxpilot_settings', [] );
		$types    = $settings['product_types'] ?? [];

		if ( ! in_array( 'digital', $types, true ) ) {
			return $tax_class;
		}

		// Auto-assign to virtual or downloadable products.
		if ( $product->is_virtual() || $product->is_downloadable() ) {
			return 'digital-goods';
		}

		return $tax_class;
	}

	/*
	 * ──────────────────────────────────
	 *  AUTO-ENABLE TAX
	 * ──────────────────────────────────
	 */

	/**
	 * Ensure WooCommerce tax calculation is enabled when TaxPilot has completed setup.
	 */
	public function maybe_enable_taxes(): void {
		$settings = get_option( 'taxpilot_settings', [] );

		if ( empty( $settings['wizard_completed'] ) ) {
			return;
		}

		// Only run once.
		if ( get_option( 'taxpilot_woo_configured' ) ) {
			return;
		}

		self::configure_woo_settings();
		update_option( 'taxpilot_woo_configured', true );
	}

	/**
	 * Configure WooCommerce tax settings for optimal TaxPilot integration.
	 */
	public static function configure_woo_settings(): void {
		// Enable tax calculation.
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// Tax based on customer shipping address.
		update_option( 'woocommerce_tax_based_on', 'shipping' );

		// Display prices excluding tax in shop (common standard).
		update_option( 'woocommerce_tax_display_shop', 'excl' );

		// Display prices excluding tax in cart/checkout.
		update_option( 'woocommerce_tax_display_cart', 'excl' );

		// Show itemized tax totals.
		update_option( 'woocommerce_tax_total_display', 'itemized' );

		// Enable tax rounding at line level.
		update_option( 'woocommerce_tax_round_at_subtotal', 'no' );

		// Apply tax to shipping.
		update_option( 'woocommerce_shipping_tax_class', '' );

		// Show prices inc. tax to logged-in users only if in base country.
		update_option( 'woocommerce_prices_include_tax', 'no' );
	}

	/*
	 * ──────────────────────────────────
	 *  ADMIN NOTICES
	 * ──────────────────────────────────
	 */

	/**
	 * Show admin notices for TaxPilot-related alerts.
	 */
	public function render_admin_notices(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$settings = get_option( 'taxpilot_settings', [] );

		// Notice: wizard not completed.
		if ( empty( $settings['wizard_completed'] ) && 'plugins' === $screen->id ) {
			printf(
				'<div class="notice notice-info is-dismissible"><p>%s <a href="%s">%s</a></p></div>',
				esc_html__( '🧙 TaxPilot for WooCommerce is active! Run the setup wizard to configure your tax rates.', 'taxpilot' ),
				esc_url( admin_url( 'admin.php?page=taxpilot-wizard' ) ),
				esc_html__( 'Start Wizard →', 'taxpilot' )
			);
		}

		// Notice: tax calculation not enabled in WooCommerce.
		if ( 'yes' !== get_option( 'woocommerce_calc_taxes' ) && str_contains( $screen->id, 'taxpilot' ) ) {
			printf(
				'<div class="notice notice-warning is-dismissible"><p>%s <a href="%s">%s</a></p></div>',
				esc_html__( '⚠️ WooCommerce tax calculation is disabled. TaxPilot rates won\'t apply at checkout.', 'taxpilot' ),
				esc_url( admin_url( 'admin.php?page=wc-settings&tab=tax' ) ),
				esc_html__( 'Enable Taxes →', 'taxpilot' )
			);
		}

		// Notice: unread critical alerts.
		$unread = AlertsTable::unread_count();
		if ( $unread > 0 && str_contains( $screen->id, 'taxpilot' ) ) {
			printf(
				'<div class="notice notice-error is-dismissible"><p>%s <a href="%s">%s</a></p></div>',
				/* translators: %d: unread alert count */
				sprintf( esc_html__( '🚨 You have %d unread TaxPilot alerts that may require attention.', 'taxpilot' ), (int) $unread ),
				esc_url( admin_url( 'admin.php?page=taxpilot' ) ),
				esc_html__( 'View Alerts →', 'taxpilot' )
			);
		}
	}

	/**
	 * Add a note to WooCommerce Tax settings indicating TaxPilot is managing rates.
	 *
	 * @param array $settings WooCommerce tax settings.
	 * @return array Modified settings.
	 */
	public function add_taxpilot_settings_note( $settings ): array {
		$wizard_settings = get_option( 'taxpilot_settings', [] );

		if ( ! empty( $wizard_settings['wizard_completed'] ) ) {
			array_unshift(
				$settings,
				[
					'type' => 'info',
					'text' => sprintf(
						/* translators: %s: TaxPilot dashboard link */
						__( '🧙 Tax rates are managed by TaxPilot for WooCommerce. <a href="%s">Open TaxPilot Dashboard</a> to manage your rates.', 'taxpilot' ),
						admin_url( 'admin.php?page=taxpilot' )
					),
				]
			);
		}

		return $settings;
	}

	/*
	 * ──────────────────────────────────
	 *  SMART ADDRESS VALIDATION
	 * ──────────────────────────────────
	 */

	/**
	 * Intercept checkout and validate the shipping/billing address.
	 *
	 * @param array     $data   The $_POST checkout data fields.
	 * @param \WP_Error $errors The WooCommerce checkout error object.
	 */
	public function validate_checkout_address( array $data, \WP_Error $errors ): void {
		// Only run our validation if there aren't already critical base errors (like missing fields).
		// We don't want to overwhelm the user with messages for an empty form.
		if ( $errors->get_error_codes() ) {
			return;
		}

		$is_shipping = isset( $data['ship_to_different_address'] ) && $data['ship_to_different_address'];
		$prefix      = $is_shipping ? 'shipping_' : 'billing_';

		$address_fields = [
			'country'  => $data[ $prefix . 'country' ] ?? '',
			'postcode' => $data[ $prefix . 'postcode' ] ?? '',
			'city'     => $data[ $prefix . 'city' ] ?? '',
		];

		// Instantiate our new Address Validator.
		$validator = new AddressValidator();
		$result    = $validator->validate_address( $address_fields );

		// If validation failed, add our custom message to the WooCommerce error notices, halting the order.
		if ( false === $result['is_valid'] && ! empty( $result['message'] ) ) {
			$errors->add( 'taxpilot_address_validation_failed', $result['message'] );
		}
	}
}
