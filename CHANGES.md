# Greenwoodruff Fork - Änderungsdokumentation

Dieses Dokument beschreibt alle Anpassungen, die im Fork `Greenwoodruff/connector-woocommerce3` gegenüber dem Original-Connector vorgenommen wurden.

**Basis:** JTL WooCommerce Connector Version 2.4.1
**Fork erstellt:** Januar 2026

---

## Übersicht der Änderungen

| PR | Änderung | Commit |
|----|----------|--------|
| #1 | ACF-Synchronisation deaktiviert | `0f493e3` |
| #2 | Perfect WooCommerce Brands immer aktiv | `e26e96c` |
| #3 | Vendor/Autoload Fehlerbehandlung | `6594bfd` |
| #4 | PWB-Brand Sync Fix (Manufacturer Lookup) | `9a3ca54` |
| #5 | Individuelle Lieferzeit für Lagerware | `bd099bc` |

---

## PR #1: ACF-Synchronisation deaktiviert

**Commit:** `0f493e33c5cc86984724998a55a35dca7b13e036`
**Zweck:** Die ACF (Advanced Custom Fields) Synchronisation wurde deaktiviert, da sie nicht benötigt wird.

### Betroffene Datei
- `src/Controllers/ProductController.php`

### Änderungen im Detail

**Entfernter Import:**
```php
// ENTFERNT:
use JtlWooCommerceConnector\Controllers\Product\ProductAdvancedCustomFieldsController;
```

**Entfernter Code beim Pull (ca. Zeile 255):**
```php
// ENTFERNT:
if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_ADVANCED_CUSTOM_FIELDS)) {
    (new ProductAdvancedCustomFieldsController($this->db, $this->util))->pullData($productModel, $product);
}
```

**Entfernter Code beim Push (ca. Zeile 407):**
```php
// ENTFERNT:
if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_ADVANCED_CUSTOM_FIELDS)) {
    (new ProductAdvancedCustomFieldsController($this->db, $this->util))->pushData($model);
}
```

---

## PR #2: Perfect WooCommerce Brands immer aktiv

**Commit:** `e26e96c1a0e93dfc2ae27fdec7442d9e3fbaada9`
**Zweck:** Das Plugin geht nun davon aus, dass Perfect WooCommerce Brands immer installiert ist. Dadurch ist die `pwb-brand` Taxonomy immer verfügbar.

### Betroffene Dateien
1. `src/Integrations/Plugins/PerfectWooCommerceBrands/PerfectWooCommerceBrands.php`
2. `src/Utilities/SupportedPlugins.php`

### Änderungen im Detail

**PerfectWooCommerceBrands.php - Zeile 32:**
```php
// VORHER:
public function canBeUsed(): bool
{
    return SupportedPlugins::isPerfectWooCommerceBrandsActive();
}

// NACHHER:
public function canBeUsed(): bool
{
    return true;
}
```

**SupportedPlugins.php - Zeile 218:**
```php
// VORHER:
public static function isPerfectWooCommerceBrandsActive(): bool
{
    return (
        self::isActive(self::PLUGIN_PERFECT_WOO_BRANDS) ||
        self::isActive(self::PLUGIN_PERFECT_BRANDS_FOR_WOOCOMMERCE) ||
        self::isActive(self::PLUGIN_PERFECT_BRANDS_WOOCOMMERCE)
    );
}

// NACHHER:
public static function isPerfectWooCommerceBrandsActive(): bool
{
    return true;
}
```

---

## PR #3: Vendor/Autoload Fehlerbehandlung

**Commit:** `6594bfd2416e0d8b95e1d04f91d55214b2be5974`
**Zweck:** Verhindert einen Fatal Error, wenn das Plugin direkt von GitHub installiert wird und die `vendor/autoload.php` fehlt. Stattdessen wird eine hilfreiche Admin-Notice angezeigt.

### Betroffene Datei
- `woo-jtl-connector.php`

### Änderungen im Detail

**Am Anfang der Datei (nach den Includes):**
```php
// NEU HINZUGEFÜGT:
$jtlwcc_autoload_missing = false;
```

**Beim Laden des Autoloaders:**
```php
// VORHER:
} else {
    $loader = require(JTLWCC_CONNECTOR_DIR . '/vendor/autoload.php');
    ...
}

// NACHHER:
} elseif (file_exists(JTLWCC_CONNECTOR_DIR . '/vendor/autoload.php')) {
    $loader = require(JTLWCC_CONNECTOR_DIR . '/vendor/autoload.php');
    $loader->add('', JTLWCC_CONNECTOR_DIR . '/plugins');
    if (is_dir(JTLWCC_EXT_CONNECTOR_PLUGIN_DIR)) {
        $loader->add('', JTLWCC_EXT_CONNECTOR_PLUGIN_DIR);
    }
} else {
    $jtlwcc_autoload_missing = true;
}
```

**Nach dem try-catch Block:**
```php
// NEU HINZUGEFÜGT:
if ($jtlwcc_autoload_missing) {
    add_action('admin_notices', 'jtlwcc_vendor_missing_notice');
    return;
}

/**
 * Show admin notice when vendor/autoload.php is missing.
 *
 * @return void
 */
function jtlwcc_vendor_missing_notice(): void
{
    echo '<div class="error"><h3>JTL-Connector</h3>';
    echo '<p><strong>Fehler:</strong> Die Datei <code>vendor/autoload.php</code> fehlt.</p>';
    echo '<p>Wenn Sie das Plugin direkt von GitHub installiert haben, müssen Sie zuerst ';
    echo '<code>composer install</code> im Plugin-Verzeichnis ausführen.</p>';
    echo '<p>Alternativ können Sie die offizielle Version von ';
    echo '<a href="https://www.jtl-software.de" target="_blank">JTL-Software</a> herunterladen.</p>';
    echo '</div>';
}
```

---

## PR #4: PWB-Brand Sync Fix (Manufacturer Lookup)

**Commit:** `9a3ca5495d4d82565b54137e4437494a61c36c16`
**Zweck:** Behebt ein Problem, bei dem der Hersteller nicht synchronisiert wurde, obwohl er bereits in der Link-Tabelle vorhanden war. Die Endpoint-ID wird nun aus der `jtl_connector_link_manufacturer` Tabelle nachgeschlagen.

### Betroffene Datei
- `src/Controllers/Product/ProductManufacturerController.php`

### Änderungen im Detail

**In der `pushData()` Methode (nach Zeile 23):**
```php
// NEU HINZUGEFÜGT - nach Abrufen der manufacturerId:
// If endpoint ID is empty, try to look it up from the link table using host ID
if ($manufacturerId === '') {
    $hostId = $product->getManufacturerId()->getHost();
    if ($hostId > 0) {
        $manufacturerId = $this->getManufacturerEndpointId($hostId);
    }
}
```

**Neue private Methode hinzugefügt:**
```php
/**
 * Look up the manufacturer endpoint ID from the link table using the host ID.
 *
 * @param int $hostId
 * @return string
 */
private function getManufacturerEndpointId(int $hostId): string
{
    global $wpdb;
    $tableName = $wpdb->prefix . 'jtl_connector_link_manufacturer';

    $endpointId = $this->db->queryOne(
        "SELECT endpoint_id FROM {$tableName} WHERE host_id = {$hostId}"
    );

    return $endpointId !== null ? (string)$endpointId : '';
}
```

---

## PR #5: Individuelle Lieferzeit für Lagerware

**Commit:** `bd099bcc414acd3d8c9dd80cb9f1337c8e47619b`
**Zweck:** Fügt eine neue Option hinzu, mit der eine individuelle Lieferzeit für Produkte mit Lagerbestand (stock > 0) definiert werden kann, z.B. "im Camplorer Lager" oder "sofort lieferbar".

### Betroffene Dateien
1. `src/Utilities/Config.php`
2. `includes/JtlConnectorAdmin.php`
3. `src/Controllers/Product/ProductDeliveryTimeController.php`

### Änderungen im Detail

**Config.php - Neue Konstante (Zeile 44):**
```php
// NEU HINZUGEFÜGT:
OPTIONS_IN_STOCK_DELIVERY_TIME = 'jtlconnector_in_stock_delivery_time',
```

**Config.php - Default-Wert (Zeile 66):**
```php
// NEU HINZUGEFÜGT:
Config::OPTIONS_IN_STOCK_DELIVERY_TIME => '',
```

**Config.php - Typ-Definition (Zeile 109):**
```php
// NEU HINZUGEFÜGT:
Config::OPTIONS_IN_STOCK_DELIVERY_TIME => 'string',
```

**JtlConnectorAdmin.php - Neues Eingabefeld (nach Zeile 1315):**
```php
// NEU HINZUGEFÜGT:
//Add in-stock delivery time textinput field
$fields[] = [
    'title'     => __('Delivery time for in-stock products', JTLWCC_TEXT_DOMAIN),
    'type'      => 'jtl_text_input',
    'id'        => Config::OPTIONS_IN_STOCK_DELIVERY_TIME,
    'value'     => Config::get(Config::OPTIONS_IN_STOCK_DELIVERY_TIME),
    'helpBlock' => __(
        "Define a custom delivery time text for products that are in stock (stock > 0)." . PHP_EOL .
        "Example: 'im Camplorer Lager' or 'sofort lieferbar'." . PHP_EOL .
        "Leave empty to use the calculated delivery time.",
        JTLWCC_TEXT_DOMAIN
    ),
];
```

**ProductDeliveryTimeController.php - Logik für individuelle Lieferzeit:**
```php
// NEU HINZUGEFÜGT (nach Zeile 32):
//Check if product is in stock and custom in-stock delivery time is configured
/** @var string $inStockDeliveryTime */
$inStockDeliveryTime = Config::get(Config::OPTIONS_IN_STOCK_DELIVERY_TIME, '');
$useInStockDeliveryTime = $product->getStockLevel() > 0 && !empty(\trim($inStockDeliveryTime));

// GEÄNDERT (Zeile 66) - Offset nur anwenden wenn NICHT in-stock:
if ($offset !== 0 && !$useInStockDeliveryTime) {
    // ... bestehender Code
}

// GEÄNDERT (Zeile 76) - Zero-Check nur wenn NICHT in-stock:
if (
    $time === 0
    && Config::get(Config::OPTIONS_DISABLED_ZERO_DELIVERY_TIME)
    && Config::get(Config::OPTIONS_USE_DELIVERYTIME_CALC) === 'delivery_time_calc'
    && !$useInStockDeliveryTime
) {
    return;
}

// GEÄNDERT (Zeile 91) - Lieferzeit-String Erstellung:
//Build Term string - use custom in-stock delivery time if configured and product is in stock
if ($useInStockDeliveryTime) {
    $deliveryTimeString = \trim($inStockDeliveryTime);
} else {
    $deliveryTimeString = \trim(
        \sprintf(
            '%s %s %s',
            $prefixDeliveryTime,
            $time,
            $suffixDeliveryTime
        )
    );
}

// GEÄNDERT (Zeile 103) - delivery_status nur wenn NICHT in-stock:
if (
    !$useInStockDeliveryTime
    && (Config::get(Config::OPTIONS_USE_DELIVERYTIME_CALC) === 'delivery_status')
    // ... rest der Bedingung
) {
```

---

## Wiederherstellung der Änderungen

Falls der Fork verloren geht, können die Änderungen wie folgt wiederhergestellt werden:

### Schritt 1: Original-Connector klonen
```bash
git clone https://github.com/jtl-software/connector-woocommerce3.git
cd connector-woocommerce3
```

### Schritt 2: Änderungen manuell anwenden
Die oben dokumentierten Code-Änderungen können manuell in die entsprechenden Dateien eingefügt werden.

### Zusammenfassung der zu ändernden Dateien:
1. `src/Controllers/ProductController.php` - ACF-Aufrufe entfernen
2. `src/Integrations/Plugins/PerfectWooCommerceBrands/PerfectWooCommerceBrands.php` - `return true;`
3. `src/Utilities/SupportedPlugins.php` - `isPerfectWooCommerceBrandsActive()` auf `return true;`
4. `woo-jtl-connector.php` - Autoload-Fehlerbehandlung hinzufügen
5. `src/Controllers/Product/ProductManufacturerController.php` - Manufacturer-Lookup Methode
6. `src/Utilities/Config.php` - Neue Option hinzufügen
7. `includes/JtlConnectorAdmin.php` - Admin-Eingabefeld hinzufügen
8. `src/Controllers/Product/ProductDeliveryTimeController.php` - In-Stock-Lieferzeit-Logik

---

## Kontakt

Bei Fragen zu diesen Änderungen kann die Git-Historie des Forks konsultiert werden:
```bash
git log --oneline
```

Jeder Commit enthält eine ausführliche Beschreibung der vorgenommen Änderungen.
