# Contributing to TaxPilot for WooCommerce

Thank you for your interest in contributing! Here's how to get started.

## Development Setup

### Prerequisites

- WordPress 6.7+
- PHP 8.2+
- WooCommerce 8.0+
- Node.js 18+
- Composer 2+

### Installation

```bash
# Clone the repository
git clone https://github.com/taxpilot/taxpilot.git
cd taxpilot

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Build frontend assets
npm run build
```

### Development Commands

| Command | Description |
|---|---|
| `npm run start` | Start webpack dev server (watch mode) |
| `npm run build` | Build production assets |
| `npm run lint:js` | Run ESLint on JS/JSX files |
| `npm run lint:css` | Run Stylelint on CSS files |
| `npm run lint:php` | Run PHPCS on PHP files |
| `npm run format` | Auto-format JS/JSX files with Prettier |
| `npm run check:textdomain` | Verify text domains in PHP files |
| `npm run check:readme` | Validate readme.txt and version sync |
| `npm run check:all` | Run all linters and checks |
| `npm run i18n` | Generate POT and JSON translation files |
| `npm run readme:md` | Generate README.md from readme.txt |
| `composer lint` | Run PHPCS (summary) |
| `composer lint:fix` | Auto-fix PHPCS issues |

## Coding Standards

This project follows WordPress Coding Standards:

- **PHP** — [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) enforced via PHPCS
- **JavaScript** — [@wordpress/eslint-plugin](https://www.npmjs.com/package/@wordpress/eslint-plugin) enforced via ESLint
- **CSS** — [@wordpress/stylelint-config](https://www.npmjs.com/package/@wordpress/stylelint-config) enforced via Stylelint

Run `npm run check:all` before submitting a pull request to ensure all checks pass.

## Pull Request Process

1. Fork the repository and create your branch from `main`
2. Write clear, descriptive commit messages
3. Ensure all linters pass: `npm run check:all`
4. Build assets: `npm run build`
5. Update `CHANGELOG.md` if adding features or fixing bugs
6. Update `readme.txt` if changing user-facing functionality
7. Run `npm run check:readme` to verify version sync
8. Submit your pull request with a clear description

## Branching Strategy

- `main` — Stable release branch
- `develop` — Active development branch
- `feature/*` — Feature branches (merge into `develop`)
- `fix/*` — Bug fix branches (merge into `develop`)
- `release/*` — Release preparation branches

## Reporting Issues

- Use [GitHub Issues](https://github.com/taxpilot/taxpilot/issues) to report bugs
- Include WordPress version, WooCommerce version, PHP version, and steps to reproduce
- Check existing issues before creating a new one

## License

By contributing, you agree that your contributions will be licensed under the [GPLv2 or later](LICENSE).
