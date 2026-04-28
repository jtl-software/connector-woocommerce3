# Project Instructions

## What this project is

A WordPress plugin that synchronizes WooCommerce shop data (products, categories, customers, orders) with JTL-Wawi ERP via the `jtl/connector` core framework. The plugin exposes a `/jtlconnector` HTTP endpoint that JTL-Wawi calls to pull and push data.

Distribution: packaged as a ZIP for wordpress.org via SVN. PHP >= 8.1 required.

## Commands

```bash
# Install dependencies
composer install

# Run all tests
composer run tests

# Run a single test file
./vendor/bin/phpunit tests/src/Controllers/ImageTest.php

# Run a single test method
./vendor/bin/phpunit --filter testMethodName tests/src/Controllers/ImageTest.php

# Code style check
composer run phpcs

# Auto-fix code style
composer run phpcs:fix

# Static analysis (PHPStan level max)
composer run phpstan

# Run both phpcs + phpstan
composer run analyse

# Build release ZIP (requires phing)
./build.sh
```

CI uses the container `ghcr.io/jtl-software/connector-utils-ci-docker/php/cli:8.3`. Tests run against PHP 8.1, 8.2, and 8.3 in the matrix.

## Architecture

### Request flow

`/jtlconnector` endpoint → `includes/JtlConnector.php::capture_request()` → creates `FileConfig` from `config/config.json` → instantiates `src/Connector.php` → delegates to `jtl/connector` framework `Application::run()`.

### Connector.php

Implements `ConnectorInterface`, `UseChecksumInterface`, and `HandleRequestInterface` from the core framework. Wires up the DI container (PHP-DI), registers controllers, and dispatches Symfony events (`HandlePullEvent`, `HandlePushEvent`, `HandleDeleteEvent`, etc.).

### Controllers

`src/Controllers/` contains one controller per JTL entity (Category, Customer, Product, CustomerOrder, Image, Manufacturer, Payment, ...). Controllers follow a pull/push/delete pattern defined by the core framework.

Product has a dedicated subdirectory `src/Controllers/Product/` with sub-controllers for variations, attributes, prices, stock levels, and third-party plugin specifics (Germanized, GermanMarket, B2BMarket, etc.). Orders have a similar `src/Controllers/Order/` subdirectory.

All controllers extend `AbstractController` / `AbstractBaseController`.

### Plugin integrations

`src/Integrations/PluginsManager.php` is the registry for third-party WordPress plugin integrations. Supported integrations: WPML (13+ components), Germanized, GermanMarket, PerfectWooCommerceBrands, RankMathSeo, YoastSeo, AdvancedCustomFields, B2BMarket.

Each integration hooks into the main controller pipeline via the Symfony EventDispatcher.

### SQL layer

Database access goes through `src/Utilities/Db.php` (a thin wrapper around WordPress `wpdb`). SQL queries for each entity are organized as PHP traits in `src/Utilities/Db/` (e.g., `ProductTrait`, `CategoryTrait`, `OrderTrait`). These traits are mixed into `Db.php` — add new queries there, not inline in controllers.

`src/Utilities/SqlHelper.php` provides shared query-building helpers.

### Key configuration

- `config/config.json` — runtime config loaded per request (token, logging level, etc.)
- `config/features.json` (optional, copy from `.example`) — feature flag toggles
- `build-config.yaml` — plugin version and minimum WC version (used by Phing)

### Custom database tables

11 custom tables are created on plugin activation (prefixed `jtl_connector_*`) for ID mapping (linking WC IDs to JTL IDs), checksum tracking, and category management. They are dropped by `uninstall.php`.

### Admin UI

`includes/JtlConnectorAdmin.php` handles the WooCommerce settings panel, AJAX endpoints for log management, and developer settings. It is largely self-contained.

## Testing

Tests live in `tests/src/` with namespace `JtlWooCommerceConnector\Tests\`. Bootstrap at `tests/bootstrap.php` loads WP_Mock to stub WordPress globals. PHPUnit config is `phpunit.xml` (strict: `forceCoversAnnotation`, `failOnRisky`, `failOnWarning`).

New tests must have `@covers` annotations. Use `AbstractTestCase` as the base class.

## CI/CD

Workflows in `.github/workflows/`:

| File | Trigger | Purpose |
|---|---|---|
| `check.yaml` | push master / PRs | phpcs + phpstan + phpunit (PHP 8.1/8.2/8.3) |
| `build-and-deploy.yaml` | tag push | Phing ZIP + SVN deploy to wordpress.org |
| `auto-draft-pr.yaml` | push to feature branches | Auto-creates draft PR |
| `update-changelog.yaml` | release published | Calls reusable workflow in `changelog-extractor` repo |
| `lint-actions.yaml` | workflow file changes | actionlint syntax validation |
