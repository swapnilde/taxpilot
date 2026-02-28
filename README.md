# TaxPilot for WooCommerce

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![WP](https://img.shields.io/badge/WordPress-6.7%2B-21759b)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb4)
![License](https://img.shields.io/badge/license-GPLv2%20or%20later-green)

> Smart tax configuration wizard for WooCommerce — auto-detect rates, one-click setup, compliance monitoring & alerts.

## Description
**Stop spending hours wrestling with WooCommerce tax tables.** TaxPilot for WooCommerce replaces the tedious, error-prone process of manually configuring tax rates with a guided 5-step wizard that gets your store tax-compliant in minutes.

Whether you sell physical goods, digital products, or services — domestically or across borders — TaxPilot handles the complexity so you can focus on growing your business.

### 🧙 5-Step Setup Wizard
Get from zero to fully-configured taxes in under 5 minutes:

1. **Store Setup** — Auto-detects your WooCommerce store country and currency (or set manually)
2. **Product Types** — Choose what you sell: Physical Goods, Digital Products, Services — each gets the correct tax class
3. **Target Countries** — Select the countries where you sell, with smart defaults for EU, NAFTA, and APAC regions
4. **Rate Preview** — Review every rate before it touches your store, with country-by-country breakdown
5. **One-Click Apply** — Push all rates to WooCommerce tax tables with a single click and two-step confirmation

Re-run the wizard anytime. TaxPilot cleans up old rates automatically — no duplicates, no orphaned entries.

### 🌍 Global Tax Rate Coverage
### ✨ Core Features
* **5-Step Setup Wizard:** Configure your store's tax rates for any country in minutes.
* **Smart Defaults:** Auto-detects your shop's base country and currency.
* **Global Rate Database:** Built-in support for standard, reduced, and zero tax rates across 100+ countries.
* **Deep WooCommerce Integration:** Automatically applies rates at checkout, manages shipping tax, and stamps orders with a custom "Tax Source" badge.
* **B2B EU EU Exemption:** Automatically validates EU VIES VAT numbers at checkout to exempt legitimate B2B buyers.
* **Digital Goods Auto-Classification:** Automatically assigns virtual/downloadable products to the correct tax class.
* **Dashboard & Alerts:** A beautiful interface offering real-time rate statistics, activity logs, and email notifications when global tax laws change.
* **WooCommerce Analytics Built-in:** Includes a dedicated "TaxPilot Usage" tracking tab seamlessly embedded inside the native WooCommerce Reports screen.
* **PDF & CSV Exports:** Downloadable tax compliance reports for your records.
* **Built for Reliability:** Three-layer duplicate prevention ensures existing rates are updated safely without cluttering your database.

## Installation
### Minimum Requirements
* WordPress 6.7 or greater
* PHP 8.2 or greater
* WooCommerce 8.0 or greater

### Automatic Installation
1. Log in to your WordPress admin panel
2. Navigate to **Plugins → Add New**
3. Search for **TaxPilot for WooCommerce**
4. Click **Install Now**, then **Activate**
5. Navigate to **TaxPilot** in the admin menu to start the setup wizard

### Manual Installation
1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Navigate to **Plugins → Add New → Upload Plugin**
4. Choose the ZIP file and click **Install Now**
5. Activate the plugin
6. Navigate to **TaxPilot** in the admin menu to start the setup wizard

## Frequently Asked Questions
### Does this plugin require WooCommerce?
Yes. WooCommerce 8.0 or later must be installed and activated. TaxPilot for WooCommerce extends WooCommerce's tax system — it doesn't replace it.

### Where do the tax rates come from?
TaxPilot ships with a comprehensive built-in tax rate database covering 100+ countries. Rates are maintained and updated with each plugin release. For real-time rates, you can optionally connect a VATSense API key.

### How many countries are supported?
The bundled database covers 100+ countries including all EU member states, US (state-level), UK, Canada, Australia, Japan, Switzerland, India, Brazil, and many more.

### What tax classes does TaxPilot create?
Based on your product types, TaxPilot creates: Standard, Reduced Rate, Digital Goods, Services, and Zero Rate. Digital Goods are auto-assigned to virtual/downloadable products.

### Can I re-run the wizard after initial setup?
Absolutely. Click **Re-run Wizard** on the TaxPilot dashboard anytime. The wizard will start fresh, pre-fill your previous settings, and clean up old rates before applying new ones. No duplicates.

### Will it overwrite my existing WooCommerce tax rates?
TaxPilot manages its own rates separately. The wizard has a two-step confirmation before applying rates. Manually added tax rates are preserved unless they share the same country/state/class combination.

### Does it handle EU VAT correctly?
Yes. TaxPilot includes all 27 EU member state VAT rates (standard, reduced, and super-reduced), real-time VIES VAT number validation at checkout, and automatic B2B tax exemption for customers with valid VAT numbers.

### What about digital goods and the EU VAT rules?
TaxPilot automatically assigns the Digital Goods tax class to virtual and downloadable products. Combined with per-country destination-based rates, this handles EU digital goods taxation requirements.

### Does it work with WooCommerce HPOS (High-Performance Order Storage)?
Yes. TaxPilot for WooCommerce is fully compatible with HPOS and uses WooCommerce's order meta API for all order operations.

### How does the rate change monitoring work?
TaxPilot runs a daily cron job that compares your applied rates against the current rate database. If any rates have changed, it creates an alert and optionally sends an email notification.

### Can I export my tax rates?
Yes. You can export your complete rate table as a CSV file and generate formatted PDF compliance reports from the TaxPilot dashboard.

### Does it work with multisite?
TaxPilot operates on a per-site basis. Each site in a multisite network can have its own tax configuration.

### What happens if I deactivate or delete the plugin?
Deactivating the plugin does not remove any data. Deleting the plugin removes all TaxPilot custom database tables, options, and transients. Your WooCommerce tax rates remain intact.

## Screenshots
1. **Setup Wizard — Store Configuration** — Auto-detects your WooCommerce country and currency
2. **Setup Wizard — Product Types** — Choose physical goods, digital products, or services
3. **Setup Wizard — Target Countries** — Select selling destinations with region presets
4. **Setup Wizard — Rate Preview** — Review every rate before it's applied to your store
5. **Setup Wizard — Apply Rates** — One-click application with summary and confirmation
6. **Dashboard** — Real-time overview of rates, activity log, and alerts
7. **Settings** — Configure rate source, API key, refresh interval, and alert preferences
8. **WooCommerce Tax Settings** — Rates populated directly in WooCommerce tax tables

## Changelog
### 1.0.0
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
* Integrated directly into WooCommerce Analytics via a custom Reports tab
* Intuitive dashboard with real-time rate statistics, activity logs, and an alert center
* Daily rate change monitoring keeps you compliant, complete with automated email notifications
* Downloadable CSV compliance exports and PDF reporting
* Safe "Re-run Wizard" mode automatically cleans up old rates to prevent billing duplicates
* Optimized for fast checkouts with WooCommerce High-Performance Order Storage (HPOS) support
* Fully translation-ready so you can use TaxPilot in your local language

## Upgrade Notice
### 1.0.0

Initial release of TaxPilot for WooCommerce — the smart tax configuration wizard for WooCommerce. Install and run the 5-step wizard to configure your store's tax rates in minutes.
