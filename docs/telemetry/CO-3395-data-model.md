# Connector Telemetry Data Model

Generic data model for raw telemetry data from SaaS connectors (Shopify, Shopware 6, WooCommerce, PrestaShop).

## Overview

```
Context (denormalisiert auf jedem Objekt)
│
├── Transaction
│   └── TransactionItem[]
│
├── SyncRun
│   └── SyncItem[] ──→ Transaction
│
└── ApiRequest ──→ SyncRun? / SyncItem?
```

---

## Context (denormalisiert)

Wird auf jedes Datenobjekt direkt geschrieben (keine separate Tabelle, keine Joins).

| Attribut            | Typ    | Beschreibung                          |
|---------------------|--------|---------------------------------------|
| tenant_id           | string | Eindeutige Kunden-/Mandanten-ID      |
| tenant_name         | string | Name des Kunden                       |
| connector_type      | enum   | `shopify`, `shopware6`, `woocommerce`, `prestashop` |
| connector_version   | string | Version des Connectors (z.B. 2.4.1)  |
| environment         | enum   | `production`, `staging`, `development`|
| wawi_type           | string | ERP-/WaWi-System des Kunden (optional)|
| wawi_version        | string | Version der WaWi (optional)           |

---

## 1. Transaction (Order-Ebene)

Eine Transaktion entspricht einer Order aus dem Shopsystem.

| Attribut            | Typ       | Beschreibung                          |
|---------------------|-----------|---------------------------------------|
| transaction_id      | string    | Eindeutige ID                         |
| order_id            | string    | Order-ID im Quellsystem               |
| order_number        | string    | Bestellnummer (menschenlesbar)        |
| source_system       | enum      | `shopify`, `shopware6`, `woocommerce`, `prestashop` |
| status              | enum      | `pending`, `completed`, `cancelled`, `error` |
| total_amount        | decimal   | Gesamtbetrag                          |
| currency            | string    | Währung (ISO 4217, z.B. EUR)          |
| item_count          | int       | Anzahl Positionen                     |
| total_quantity      | int       | Gesamtmenge aller Artikel             |
| created_at          | timestamp | Erstellzeitpunkt der Order            |
| synced_at           | timestamp | Zeitpunkt der letzten Synchronisation |
| Context-Felder      | ...       | siehe Context oben                    |

---

## 2. TransactionItem (Positions-Ebene)

Einzelne Positionen innerhalb einer Transaktion.

| Attribut            | Typ       | Beschreibung                          |
|---------------------|-----------|---------------------------------------|
| item_id             | string    | Eindeutige ID                         |
| transaction_id      | string    | FK → Transaction                      |
| sku                 | string    | Artikelnummer                         |
| product_name        | string    | Artikelbezeichnung                    |
| quantity            | int       | Menge                                 |
| unit_price          | decimal   | Einzelpreis                           |
| total_price         | decimal   | Positionsbetrag (quantity * unit_price)|
| status              | enum      | `synced`, `failed`, `skipped`         |
| error_message       | string    | Fehlermeldung (optional)              |

---

## 3. SyncRun (Sync-Lauf)

Ein Synchronisationslauf, ausgeloest durch die WaWi. Verarbeitet einen Batch von Objekten.

| Attribut            | Typ       | Beschreibung                          |
|---------------------|-----------|---------------------------------------|
| sync_run_id         | string    | Eindeutige ID                         |
| triggered_by        | enum      | `wawi_scheduled`, `wawi_manual`, `webhook` |
| direction           | enum      | `inbound`, `outbound`                 |
| object_type         | enum      | `order`, `product`, `customer`, `inventory`, ... |
| scope               | enum      | `full`, `delta`                       |
| timestamp_start     | timestamp | Start des Sync-Laufs                  |
| timestamp_end       | timestamp | Ende des Sync-Laufs                   |
| duration_ms         | long      | Laufzeit in Millisekunden             |
| records_total       | int       | Anzahl Objekte im Batch               |
| records_succeeded   | int       | Davon erfolgreich                     |
| records_failed      | int       | Davon fehlgeschlagen                  |
| result              | enum      | `success`, `partial`, `failed`        |
| Context-Felder      | ...       | siehe Context oben                    |

---

## 4. SyncItem (Einzelobjekt im Batch)

Ein einzelnes Objekt innerhalb eines SyncRuns.

| Attribut            | Typ       | Beschreibung                          |
|---------------------|-----------|---------------------------------------|
| sync_item_id        | string    | Eindeutige ID                         |
| sync_run_id         | string    | FK → SyncRun                          |
| transaction_id      | string    | FK → Transaction (optional)           |
| object_ref          | string    | Referenz im Quellsystem (z.B. Shopify Order #1234) |
| result              | enum      | `success`, `failed`, `skipped`        |
| error_code          | string    | Fehlercode (optional)                 |
| error_message       | string    | Fehlermeldung (optional)              |
| duration_ms         | long      | Verarbeitungsdauer                    |

---

## 5. ApiRequest (Technische Operational Data)

Einzelner API-Aufruf gegen ein Zielsystem.

| Attribut               | Typ       | Beschreibung                          |
|------------------------|-----------|---------------------------------------|
| request_id             | string    | Eindeutige ID                         |
| sync_run_id            | string    | FK → SyncRun (optional)               |
| sync_item_id           | string    | FK → SyncItem (optional)              |
| timestamp              | timestamp | Zeitpunkt des Requests                |
| target_system          | enum      | `shopify`, `shopware6`, `woocommerce`, `prestashop`, `erp`, `wawi` |
| http_method            | enum      | `GET`, `POST`, `PUT`, `DELETE`        |
| endpoint               | string    | API-Endpoint (z.B. /orders)           |
| http_status            | int       | HTTP Status Code                      |
| duration_ms            | long      | Antwortzeit in Millisekunden          |
| request_size_bytes     | long      | Request-Groesse (optional)            |
| response_size_bytes    | long      | Response-Groesse (optional)           |
| is_error               | bool      | Fehler ja/nein                        |
| error_category         | enum      | `timeout`, `rate_limit`, `auth`, `server_error`, `client_error` |
| error_message          | string    | Fehlermeldung (optional)              |
| is_retry               | bool      | Ist dies ein Retry                    |
| retry_attempt          | int       | 0 = erster Versuch, 1 = erster Retry  |
| retry_of_request_id    | string    | FK → ApiRequest (optional)            |
| is_rate_limited        | bool      | Rate Limit getroffen                  |
| rate_limit_wait_ms     | long      | Wartezeit bei Rate Limit (optional)   |
| Context-Felder         | ...       | siehe Context oben                    |

---

## Beziehungen

```
Transaction 1 ──→ N TransactionItem
SyncRun     1 ──→ N SyncItem
SyncItem    N ──→ 1 Transaction        (optional)
ApiRequest  N ──→ 1 SyncRun            (optional)
ApiRequest  N ──→ 1 SyncItem           (optional)
ApiRequest  N ──→ 1 ApiRequest          (Retry-Kette)
```

## Design-Entscheidungen

| Entscheidung | Auswahl | Begründung |
|---|---|---|
| Context | Denormalisiert | Write-heavy Telemetrie-Daten; Joins auf grossen Mengen vermeiden; Storage ist guenstig |
| Transaction-Items | Eigene Ebene (Ansatz 1) | Acceptance Criteria fordern item-level data; ermoeglicht Analysen pro SKU |
| Sync-Modell | Zwei Ebenen (SyncRun + SyncItem) | WaWi loest Batch-Syncs aus; SyncRun = Batch, SyncItem = Einzelobjekt |
| Technische Daten | Ein Objekt (ApiRequest) | Fehler, Retries, Rate Limits sind Eigenschaften eines Requests, keine separaten Entitaeten |
| Connector-Agnostik | Generische Enums | Shopify, Shopware6, WooCommerce und PrestaShop nutzen dasselbe Modell; Erweiterung durch neue Enum-Werte |
| Storage | ClickHouse (MergeTree) | Columnar Engine optimiert fuer denormalisierte Event-Daten; schnelle Aggregationen; TTL fuer Retention; LowCardinality fuer Enums |

## Storage: ClickHouse

ClickHouse ist die gewaehlte Storage-Lösung. Das denormalisierte Datenmodell passt direkt auf ClickHouse's columnar Engine.

### Tabellen-Engines und Partitionierung

| Tabelle         | Engine                  | Partitionierung     | Order By                              |
|-----------------|-------------------------|---------------------|---------------------------------------|
| transactions    | MergeTree               | toYYYYMM(created_at)| (tenant_id, source_system, created_at)|
| transaction_items| MergeTree              | toYYYYMM(created_at)| (transaction_id, item_id)             |
| sync_runs       | MergeTree               | toYYYYMM(timestamp_start)| (tenant_id, connector_type, timestamp_start) |
| sync_items      | MergeTree               | toYYYYMM(timestamp_start)| (sync_run_id, sync_item_id)          |
| api_requests    | MergeTree               | toYYYYMM(timestamp) | (tenant_id, target_system, timestamp) |

### ClickHouse-spezifische Typ-Mappings

| Modell-Typ | ClickHouse-Typ                        |
|------------|---------------------------------------|
| string     | String                                |
| string (ID)| UUID                                 |
| enum       | LowCardinality(String)                |
| int        | UInt32                                |
| long       | UInt64                                |
| decimal    | Decimal(18, 4)                        |
| bool       | UInt8 (0/1)                           |
| timestamp  | DateTime64(3) (Millisekunden)         |

### TTL / Retention

```sql
-- Beispiel: Rohdaten 12 Monate behalten
ALTER TABLE api_requests MODIFY TTL timestamp + INTERVAL 12 MONTH;
ALTER TABLE sync_runs MODIFY TTL timestamp_start + INTERVAL 12 MONTH;

-- Transaktionsdaten laenger behalten (z.B. 24 Monate)
ALTER TABLE transactions MODIFY TTL created_at + INTERVAL 24 MONTH;
```

### Hinweise

- **LowCardinality(String)** fuer alle Enum-Felder — spart Speicher und beschleunigt Queries bei wenigen distinkten Werten.
- **Partitionierung nach Monat** — ermoeglicht effizientes Loeschen alter Daten und schnelle Zeitraum-Queries.
- **Order By mit tenant_id** — optimiert Queries die nach Kunde filtern (haeufigster Filter).
- **Kein Nullable wo vermeidbar** — ClickHouse performt besser mit Default-Werten (`''` fuer optionale Strings, `0` fuer optionale Zahlen) statt Nullable.

---

## Erweiterbarkeit

- Neue Connector-Typen: Neuer Wert in `connector_type`
- Neue Objekttypen: Neuer Wert in `object_type` (z.B. `shipment`, `refund`)
- Neue Event-Typen: Neue Entitaet nach gleichem Muster (Context denormalisiert, optionale FK)
- Zusaetzliche Attribute: Felder ergaenzen oder generisches `metadata`-Feld (Key-Value) einfuehren
