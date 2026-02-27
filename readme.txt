=== TaxPilot for WooCommerce ===
Contributors: swapnilde
Donate link: https://paypal.me/SwapnilDeshpandeIN
Tags: woocommerce, tax, vat, sales-tax, gst
Requires at least: 6.7
Tested up to: 6.9
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Smart tax configuration wizard for WooCommerce — auto-detect rates, one-click setup, compliance monitoring & alerts.

== Description ==

**Stop spending hours wrestling with WooCommerce tax tables.** TaxPilot for WooCommerce replaces the tedious, error-prone process of manually configuring tax rates with a guided 5-step wizard that gets your store tax-compliant in minutes.

Whether you sell physical goods, digital products, or services — domestically or across borders — TaxPilot handles the complexity so you can focus on growing your business.

= 🧙 5-Step Setup Wizard =

Get from zero to fully-configured taxes in under 5 minutes:

1. **Store Setup** — Auto-detects your WooCommerce store country and currency (or set manually)
2. **Product Types** — Choose what you sell: Physical Goods, Digital Products, Services — each gets the correct tax class
3. **Target Countries** — Select the countries where you sell, with smart defaults for EU, NAFTA, and APAC regions
4. **Rate Preview** — Review every rate before it touches your store, with country-by-country breakdown
5. **One-Click Apply** — Push all rates to WooCommerce tax tables with a single click and two-step confirmation

Re-run the wizard anytime. TaxPilot cleans up old rates automatically — no duplicates, no orphaned entries.

= 🌍 Global Tax Rate Coverage =

TaxPilot ships with a comprehensive built-in tax rate database covering 100+ countries:

* **EU VAT** — All 27 member states with standard, reduced, and super-reduced rates
* **US Sales Tax** — State-level rates
* **UK VAT** — Standard and reduced rates
* **Canadian GST/HST/PST** — Federal and provincial rates
* **Australian GST** — Standard 10% rate
* **Japanese Consumption Tax** — Standard and reduced food rates
* **Swiss VAT** — Standard, accommodation, and reduced rates
* **And many more** — Norway, New Zealand, India, Brazil, South Korea, and more

Need real-time rates? Connect the optional VATSense API for live rate lookups and automatic updates.

= 🏷️ Smart Tax Class Management =

TaxPilot automatically creates and manages WooCommerce tax classes based on your product mix:

* **Standard Rate** — Physical goods (default WooCommerce class)
* **Reduced Rate** — Products eligible for reduced taxation
* **Digital Goods** — Virtual and downloadable products, auto-assigned via product attributes
* **Services** — Service-based products with distinct tax treatment
* **Zero Rate** — Tax-exempt items

Digital products are automatically classified — any WooCommerce product marked as virtual or downloadable gets the Digital Goods tax class applied at checkout.

= 🔗 Deep WooCommerce Integration =

TaxPilot doesn't just dump rates into a table. It deeply integrates with WooCommerce's tax lifecycle:

* **Checkout Rate Matching** — Falls back to TaxPilot rates when WooCommerce's standard lookup misses
* **Shipping Tax Intelligence** — Applies standard tax to shipping for physical goods, skips it for digital-only carts
* **Order Audit Trail** — Stamps every order with TaxPilot metadata: rate version, source, applied date
* **Tax Settings Sync** — Automatically enables WooCommerce tax calculation, sets display preferences, and configures price settings
* **Admin Notices** — Alerts on TaxPilot dashboard pages when attention is needed

= 🇪🇺 EU VAT / B2B Exemption =

Built-in support for EU cross-border selling:

* **VIES Validation** — Real-time EU VAT number verification at checkout
* **B2B Tax Exemption** — Automatically exempts B2B customers with valid VAT numbers
* **VAT Number Checkout Field** — Adds a VAT number field to the billing form for EU target countries
* **OSS/IOSS Ready** — Supports One-Stop Shop reporting requirements

= 📊 Dashboard & Monitoring =

The TaxPilot dashboard gives you a real-time overview of your tax configuration:

* **Rate Statistics** — Total rates, countries covered, last update timestamp
* **Recent Activity Log** — See when rates were applied, wizard was re-run, or alerts triggered
* **Alert Center** — Critical, warning, and info alerts with unread count badge
* **Quick Actions** — Refresh rates, re-run wizard, export data, and view WooCommerce tax settings

= 🚨 Rate Change Alerts =

Stay compliant with automated monitoring:

* **Daily Rate Checks** — Cron-based monitoring compares current rates against your applied rates
* **Email Notifications** — Get alerted when tax laws change in your target countries
* **Alert Severity Levels** — Critical (immediate action needed), Warning (review recommended), Info (FYI)
* **Alert History** — Full log of all alerts with read/unread status

= 📁 Compliance Exports =

Export your tax data for records and reporting:

* **CSV Export** — Full rate table export with country, rate, class, source, and timestamps
* **PDF Reports** — Formatted compliance reports for your records
* **Activity Logs** — Complete audit trail of all TaxPilot operations

= ⚙️ Settings & Configuration =

Fine-tune TaxPilot from the settings page:

* **Tax Rate Source** — Choose between the bundled static rate database or live VATSense API
* **API Key Management** — Securely store your VATSense API key
* **Refresh Interval** — Daily, weekly, or manual rate refresh schedule
* **Alert Preferences** — Enable/disable email alerts and set the recipient address

= 🛡️ Built for Reliability =

* **Duplicate Prevention** — Three-layer deduplication ensures no duplicate rates in WooCommerce
* **Smart Updates** — Existing rates are updated in place, not re-created
* **Safe Re-runs** — Re-running the wizard cleans up old rates before applying new ones
* **Confirmation Step** — Two-click confirmation prevents accidental rate overwrites
* **Activity Logging** — Every operation is logged with timestamps for debugging

== Installation ==

= Minimum Requirements =

* WordPress 6.7 or greater
* PHP 8.2 or greater
* WooCommerce 8.0 or greater

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to **Plugins → Add New**
3. Search for **TaxPilot for WooCommerce**
4. Click **Install Now**, then **Activate**
5. Navigate to **TaxPilot** in the admin menu to start the setup wizard

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Navigate to **Plugins → Add New → Upload Plugin**
4. Choose the ZIP file and click **Install Now**
5. Activate the plugin
6. Navigate to **TaxPilot** in the admin menu to start the setup wizard

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =

Yes. WooCommerce 8.0 or later must be installed and activated. TaxPilot for WooCommerce extends WooCommerce's tax system — it doesn't replace it.

= Where do the tax rates come from? =

TaxPilot ships with a comprehensive built-in tax rate database covering 100+ countries. Rates are maintained and updated with each plugin release. For real-time rates, you can optionally connect a VATSense API key.

= How many countries are supported? =

The bundled database covers 100+ countries including all EU member states, US (state-level), UK, Canada, Australia, Japan, Switzerland, India, Brazil, and many more.

= What tax classes does TaxPilot create? =

Based on your product types, TaxPilot creates: Standard, Reduced Rate, Digital Goods, Services, and Zero Rate. Digital Goods are auto-assigned to virtual/downloadable products.

= Can I re-run the wizard after initial setup? =

Absolutely. Click **Re-run Wizard** on the TaxPilot dashboard anytime. The wizard will start fresh, pre-fill your previous settings, and clean up old rates before applying new ones. No duplicates.

= Will it overwrite my existing WooCommerce tax rates? =

TaxPilot manages its own rates separately. The wizard has a two-step confirmation before applying rates. Manually added tax rates are preserved unless they share the same country/state/class combination.

= Does it handle EU VAT correctly? =

Yes. TaxPilot includes all 27 EU member state VAT rates (standard, reduced, and super-reduced), real-time VIES VAT number validation at checkout, and automatic B2B tax exemption for customers with valid VAT numbers.

= What about digital goods and the EU VAT rules? =

TaxPilot automatically assigns the Digital Goods tax class to virtual and downloadable products. Combined with per-country destination-based rates, this handles EU digital goods taxation requirements.

= Does it work with WooCommerce HPOS (High-Performance Order Storage)? =

Yes. TaxPilot for WooCommerce is fully compatible with HPOS and uses WooCommerce's order meta API for all order operations.

= How does the rate change monitoring work? =

TaxPilot runs a daily cron job that compares your applied rates against the current rate database. If any rates have changed, it creates an alert and optionally sends an email notification.

= Can I export my tax rates? =

Yes. You can export your complete rate table as a CSV file and generate formatted PDF compliance reports from the TaxPilot dashboard.

= Does it work with multisite? =

TaxPilot operates on a per-site basis. Each site in a multisite network can have its own tax configuration.

= What happens if I deactivate or delete the plugin? =

Deactivating the plugin does not remove any data. Deleting the plugin removes all TaxPilot custom database tables, options, and transients. Your WooCommerce tax rates remain intact.

== Screenshots ==

1. **Setup Wizard — Store Configuration** — Auto-detects your WooCommerce country and currency
2. **Setup Wizard — Product Types** — Choose physical goods, digital products, or services
3. **Setup Wizard — Target Countries** — Select selling destinations with region presets
4. **Setup Wizard — Rate Preview** — Review every rate before it's applied to your store
5. **Setup Wizard — Apply Rates** — One-click application with summary and confirmation
6. **Dashboard** — Real-time overview of rates, activity log, and alerts
7. **Settings** — Configure rate source, API key, refresh interval, and alert preferences
8. **WooCommerce Tax Settings** — Rates populated directly in WooCommerce tax tables

== Changelog ==

= 1.0.0 =

**Initial Release**

* 5-step setup wizard with smart defaults and auto-detection
* Built-in tax rate database covering 100+ countries
* Support for all WooCommerce tax classes: Standard, Reduced Rate, Digital Goods, Services, Zero Rate
* One-click rate application with duplicate prevention and two-step confirmation
* Deep WooCommerce integration ensures precise tax calculations and seamless checkout
* EU VIES VAT number validation with real-time B2B tax exemption
* Add a VAT number checkout field easily for EU target countries
* Auto-assignment of Digital Goods tax class to virtual/downloadable products
* Smart shipping tax handling correctly processes mixed physical and digital-only carts
* Intuitive dashboard with real-time rate statistics, activity logs, and an alert center
* Daily rate change monitoring keeps you compliant, complete with automated email notifications
* Downloadable CSV compliance exports and PDF reporting
* Safe "Re-run Wizard" mode automatically cleans up old rates to prevent billing duplicates
* Optimized for fast checkouts with WooCommerce High-Performance Order Storage (HPOS) support
* Fully translation-ready so you can use TaxPilot in your local language

== Upgrade Notice ==

= 1.0.0 =
Initial release of TaxPilot for WooCommerce — the smart tax configuration wizard for WooCommerce. Install and run the 5-step wizard to configure your store's tax rates in minutes.
