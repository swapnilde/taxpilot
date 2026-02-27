# TaxPilot for WooCommerce — Developer Documentation

Welcome to the **TaxPilot for WooCommerce** developer documentation! This guide provides a comprehensive overview of the plugin's architecture, codebase structure, REST APIs, database schemas, and extensibility hooks.

Whether you are contributing to the core plugin, building custom add-ons, or integrating TaxPilot with external services, this document contains everything you need to understand how the plugin operates under the hood.

---

## 1. Architecture Overview

TaxPilot is structured as a modern WordPress/WooCommerce plugin using **Composer** for PSR-4 autoloading on the backend and **npm/Webpack** (via `@wordpress/scripts`) for React-based interfaces on the frontend.

### Key Architectural Concepts

- **Strict OOP Backend:** The PHP codebase relies on strict typing (`declare(strict_types=1);`), namespaces, and singleton-based orchestrators. There are very few global functions.
- **Headless UI:** The Setup Wizard and Dashboard are built as **React SPA (Single Page Applications)**. They do not rely on traditional WordPress form submissions. Instead, they communicate exclusively via the WordPress REST API.
- **Isolated Rate Storage:** TaxPilot uses a custom database table (`wp_taxpilot_rates`) to store its comprehensive global rate database. It only pushes specific rates into WooCommerce's standard `wp_woocommerce_tax_rates` table when explicitly directed by the user during the Wizard application phase.
- **HPOS Compatible:** The plugin interacts with WooCommerce orders strictly through the CRUD API (`$order->get_meta()`, `$order->update_meta_data()`), ensuring full compatibility with High-Performance Order Storage (HPOS).

---

## 2. Directory Structure

```text
taxpilot/
├── .github/workflows/   # CI/CD pipelines (Lint, Build, Test)
├── assets/
│   └── css/             # Traditional global admin CSS (e.g., admin menu icons)
├── build/               # Compiled React assets (JS/CSS/PHP dependency files)
├── data/                # Static JSON payload containing 100+ global tax rates
├── includes/            # PHP Backend (PSR-4 namespace `TaxPilot\`)
│   ├── Admin/           # Admin pages, menus, and UI wrappers
│   ├── API/             # REST API endpoint controllers
│   ├── Core/            # Plugin orchestration (Activator, Deactivator, Plugin class)
│   ├── Database/        # Custom table managers (Rates, Alerts)
│   ├── Integration/     # Core WooCommerce calculation hooks and rate matching
│   └── Services/        # Background tasks (CronManager, VIESValidator, API Clients)
├── languages/           # Translation files (.pot, .po, .mo, .json)
├── src/                 # React Frontend Source Code
│   ├── common/          # Shared JS constants, utilities, and API wrappers
│   ├── dashboard/       # React SPA for the main TaxPilot Dashboard
│   └── wizard/          # React SPA for the Setup Wizard
├── taxpilot.php         # Plugin entry file (Bootstrapper)
├── package.json         # NPM dependencies and `@wordpress/scripts` configurations
├── composer.json        # PHP dependencies and PSR-4 mappings
└── DEVELOPER.md         # This documentation file
```

---

## 3. The PHP Backend (`includes/`)

The backend defines the logic for installing the database, exposing the REST API, validating VAT, and deeply hooking into WooCommerce tax calculations.

### 3.1 Core Namespace (`TaxPilot\Core`)

- **`Plugin.php`**: The singleton orchestrator. The `init()` method is fired on `plugins_loaded` and registers all other classes (REST API, Admin, Integrations).
- **`Activator.php`**: Fired upon plugin activation. Responsible for creating the custom database tables `wp_taxpilot_rates` and `wp_taxpilot_alerts`. It handles database versioning and runs `dbDelta()`.
- **`Deactivator.php`**: Fired upon deactivation. Currently flushes rewrite rules and clears cron events, but intentionally preserves user data.
- **`Assets.php`**: Handles `wp_enqueue_script` and `wp_enqueue_style` to load the compiled React builds for the wizard and dashboard. Localizes the `taxPilotData` JavaScript object (containing nonces and REST API roots).

### 3.2 Database Tier (`TaxPilot\Database`)

- **`RatesTable.php`**: Static wrapper for creating and querying the custom `$wpdb->prefix . 'taxpilot_rates'` table.
- **`AlertsTable.php`**: Static wrapper for reading/writing notifications (like changed tax rates) in `$wpdb->prefix . 'taxpilot_alerts'`.

### 3.3 Extensible Services (`TaxPilot\Services`)

- **`VIESValidator.php`**: Performs live, headless HTTP checks against the official EU VIES SOAP web service to validate B2B VAT registration numbers. Returns a cached validation state.
- **`CronManager.php`**: Registers `taxpilot_daily_cron`. Pulls fresh rates from the configured rate source (static payload or VATSense API) and compares them to applied rates to trigger alerts.

### 3.4 Deep WooCommerce Integration (`TaxPilot\Integration\WooIntegration.php`)

This class contains the most critical functionality of the plugin—calculating and assigning checkout taxes.

- Acts on the **`woocommerce_matched_tax_rates`** hook. If standard WooCommerce rate lookup yields nothing, TaxPilot intercepts and queries its own `RatesTable` memory, forcing WooCommerce to apply the rate.
- Modifies **`woocommerce_shipping_tax_class`** to dynamically calculate if shipping should be taxed based on physical vs. digital cart contents.
- Injects a `_billing_vat_number` meta field into WooCommerce checkout and triggers **`VIESValidator::validate()`**. If true, dynamically exempts the WooCommerce session via `WC()->customer->set_is_vat_exempt(true)`.

---

## 4. REST API Endpoints

TaxPilot establishes a completely private REST API nested under the namespace `taxpilot/v1`. These are primarily consumed by the React Dashboard and Wizard.

### Authentication

All requests require the `X-WP-Nonce` header. The user must have the `manage_woocommerce` capability.

### 4.1 Wizard Endpoints (`includes/API/WizardEndpoints.php`)

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/taxpilot/v1/wizard/state` | Returns the current completion state of the wizard. Auto-resumes partially finished setups. |
| `POST` | `/taxpilot/v1/wizard/store-setup` | Saves chosen base country and currency. Auto-detects defaults from WC core settings. |
| `POST` | `/taxpilot/v1/wizard/product-types` | Persists array of `['physical', 'digital', 'services']`. |
| `POST` | `/taxpilot/v1/wizard/target-countries` | Persists an array of 2-letter country ISO codes the store sells to. |
| `POST` | `/taxpilot/v1/wizard/apply` | Reads all selections, constructs local tax classes, and bulk-inserts rates into WC core tables via `WC_Tax::_insert_tax_rate()`. Cleans up old rates to prevent duplicate row creation. |

### 4.2 Rate Endpoints (`includes/API/RatesEndpoints.php`)

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/taxpilot/v1/rates/preview` | Returns a JSON preview subset of rates specifically filtered for the user's `wizard/target-countries` selection. This drives the React UI rate table. |

### 4.3 Report & Alert Endpoints (`includes/API/ReportsEndpoints.php`)

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/taxpilot/v1/reports/alerts` | Fetches active alerts from the DB, allowing the React dashboard to display warnings/notices of changing tax laws. |
| `GET` | `/taxpilot/v1/reports/export-csv` | Generates a downloadable string containing a raw CSV dump of all applied tax rates and compliance logs. |

---

## 5. React Frontend (`src/`)

Both `src/wizard` and `src/dashboard` are built as separate Webpack entry points utilizing `@wordpress/element` (WordPress's wrapper around React 18/17).

### 5.1 Common Architecture

- The React root mounts to statically defined HTML elements (`#taxpilot-wizard-root`, `#taxpilot-dashboard-root`) rendered via `AdminMenu.php`.
- **API Fetching:** The `src/common/api.js` file maintains wrappers over `@wordpress/api-fetch` to centralize generic headers and error handling.
- **WP Components:** Uses `@wordpress/components` (SelectControl, Snackbar, Button) to guarantee the visual UI matches the native WordPress Gutenberg/Block-Editor aesthetic.

### 5.2 Wizard (`src/wizard`)

- **`App.jsx`**: The master state container. Manages the `currentStep` index (`0` through `4`). Calls `apiGet('wizard/state')` on mount to resume previous state.
- **`Stepper.jsx`**: Visually displays the five workflow dots at the top of the interface.
- **Step Views**: Each file in `src/wizard/steps/` (`StoreSetup.jsx`, `TargetCountries.jsx`, etc.) renders a specific form slice. Navigation blocks until specific preconditions are met.

### 5.3 Crash Protection (Error Boundaries)

During development, if Webpack encounters bugs such as Temporal Dead Zones (like importing uninitialized functions) during production builds, the React tree can unmount silently on older WordPress platforms utilizing React 17. Ensure `createRoot` handles a fallback standard `render` check.

---

## 6. Developing & Building Locally

If making modifications to the React code (`src/`), you **must** recompile the frontend logic. Standard Webpack processes manage minification.

```bash
# Optional: Ensure dependencies are fresh
npm install

# Start the continuous Webpack file watcher
npm run start 

# OR, build optimized payload for production commit
npm run build
```

PHP backend linting:

```bash
# Verify coding standards via WordPress PHPCS
composer run lint 
# Or via package.json script
npm run lint:php
```

---

## 7. Extending TaxPilot (Hooks)

TaxPilot includes custom filter/action hooks allowing third-party integration mapping without modifying the core files.

- `apply_filters('taxpilot_api_rate_source', $source)`: Modify standard behavior to resolve live rate fetching to an alternative SaaS provider (defaults to string 'vatsense').
- `apply_filters('taxpilot_managed_tax_classes', $classes)`: Intercept the array of WooCommerce tax classes automatically generated by the wizard (e.g. `['Digital Goods', 'Services']`) before they are written.
- `do_action('taxpilot_wizard_completed', $settings_state)`: Fired immediately after the user confirms rate application at the end of the Setup Wizard. Hook into this to perform side effects if a custom implementation requires real-time knowledge of rate updates.

---

*Thank you for diving into the inner workings of TaxPilot. Built for performance, accuracy, and absolute architectural clarity.*
