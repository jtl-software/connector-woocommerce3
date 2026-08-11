# Ticket CO-3503: Integrate the WooCommerce Connector with the Telemetry Platform

- **Ticket**: CO-3503
- **Title**: Integrate the WooCommerce Connector with the Telemetry Platform
- **Author of plan**: ticket-analyzer agent
- **Date**: 2026-07-16
- **Status**: Draft — schema resolved (Open Question 1); Open Question 2 (CO-3526 transport) partially resolved 2026-08-10, see note below — transport target now known in principle, but concrete WooCommerce-facing contract still assumed, not confirmed

## 1. Description

As the Connector Team, we would like to integrate the WooCommerce Connector with the central telemetry platform, so that we can centrally collect and store transaction, synchronization, and technical request and error data from ongoing operations.

As part of the epic to introduce a central telemetry platform, the WooCommerce connector is to be technically enhanced so that relevant telemetry data is transmitted to the central telemetry server.

The goal of this story is to connect the WooCommerce connector to the telemetry infrastructure created in the epic so that the defined generic raw data from ongoing operations can be sent to the telemetry platform in a structured format. This includes, in particular, transaction data, synchronization events, and technical request, error, retry, and rate-limit information.

The story covers the technical integration of telemetry into the WooCommerce connector based on the previously defined event models. It does NOT include the business analysis of the data, KPI definitions, or BI visualization.

## 2. Goals & Acceptance Criteria

- The WooCommerce Connector can transmit telemetry data to the central telemetry server.
- The integration is based on the defined generic event models.
- Relevant transaction data from the WooCommerce Connector is transferred to the telemetry platform.
- Relevant synchronization data from the WooCommerce Connector is transferred to the telemetry platform.
- Relevant technical request, error, retry, and rate limit data from the WooCommerce connector is transferred to the telemetry platform.
- The transmission of telemetry data is technically traceable and verifiable.
- The integration is implemented in such a way that it does not unduly interfere with the ongoing operation of the WooCommerce connector.
- Personal data is not transmitted in plain text or is reduced to the technically necessary minimum.
- The technical implementation is documented.
- The integration can be used as a basis for subsequent technical evaluation in downstream epics.

## 3. Codebase & Jira Analysis

### 3.1 Jira context discovered

CO-3503 has **no `issuelinks`** and no directly linked epic field populated via the Jira `epic` relation — but it has a **parent** issue: **CO-3480** — "[Q3 2026] Top 1 — Plugin Metrics (Presta + WooCommerce → Data)". That epic in turn relates to:

- **CO-3392** — "Build a telemetry platform for SaaS connectors" (epic, In Arbeit). Scope: telemetry server + DB/data warehouse + generic raw data model, for **Shopify and Shopware 6** initially. Explicitly out of scope: KPIs, dashboards, Power BI, business evaluation.
- **CO-3395** — "Define a Generic Telemetry Data Model" (Done). **Resolved 2026-07-16**: the attachments `data-model.md` and `dummy-data.sql` were located locally and copied into this repo at `docs/telemetry/CO-3395-data-model.md` / `docs/telemetry/CO-3395-dummy-data.sql`. The real schema defines a denormalized `Context` block plus 5 entities — see Section 3.1a below for the concrete field-level mapping. **Open Question 1 (Section 3.4) is now closed.**
- **CO-3396** — "Modeling Transaction Events for Connector Telemetry" (Done). Corresponds to the real `Transaction` + `TransactionItem` entities in `data-model.md` (Section 3.1a).
- **CO-3397** — "Modeling Synchronization Events for Connector Telemetry" (Done). Corresponds to the real `SyncRun` + `SyncItem` entities in `data-model.md` (Section 3.1a).
- **CO-3398** — "Modeling Technical Request and Error Events for Connector Telemetry" (Done). Corresponds to the real `ApiRequest` entity in `data-model.md` (Section 3.1a).
- **CO-3526** — "API for getting telemetry data from WooCommerce / Presta" (**Code Review** as of 2026-08-10, comment by Magnus Pienkny; no assignee). Implementation lives in a separate repo, `connector-telemetry`. Per the 2026-08-10 status comment, the real architecture is **not** a single custom REST endpoint with a JSON envelope as originally assumed:
  - **OTLP gRPC/HTTP** (ports 4317/4318) ingest for logs/traces/metrics, fanned out by an OTel Collector to Loki/Tempo/Prometheus.
  - **ClickHouse HTTP interface** (port 8123) for the domain-specific entities (`Transaction`/`TransactionItem`/`SyncRun`/`SyncItem`/`ApiRequest`) — described as "fed directly by connectors".
  - An `ingest-proxy` (nginx) in front of all three ports does per-source-IP rate limiting (`limit_req`).
  - **Ingest ports are restricted to an IP allowlist** (`ALLOWED_INGEST_CIDRS`), not open to arbitrary internet clients.
  - No auth scheme is documented for either the OTLP or ClickHouse HTTP paths.

  **Architectural mismatch for this ticket**: an IP allowlist is workable for the SaaS connectors (Shopify/Shopware6 run on JTL-controlled infrastructure with fixed egress IPs), but the WooCommerce plugin runs on each customer's own WordPress hosting — arbitrary, unpredictable per-tenant IPs that can't realistically be enumerated in an allowlist. The same applies to PrestaShop (CO-3504).

  **Working assumption (confirmed with Magnus 2026-08-10, not yet verified against CO-3526/`connector-telemetry` itself)**: there is intended to be a JTL-hosted proxy/relay/gateway that self-hosted connectors (WooCommerce, PrestaShop) talk to over the public internet with normal HTTP(S) + some auth (API key, most likely, given `Config::OPTIONS_TOKEN`-style existing conventions), which then forwards into the IP-allowlisted OTLP/ClickHouse ingest on the connector's behalf. **This relay is not documented anywhere yet** — Steps 3-6 below should be designed against a generic "POST to a configured telemetry endpoint, JSON body, bearer/API-key auth" contract that would work equally against a purpose-built relay, and must be revisited once the relay's real shape (or its absence) is confirmed. Do not assume direct ClickHouse/OTLP protocol implementations in PHP are the intended path — that would be unusually heavy for a WordPress plugin and contradicts "fed directly by connectors" being written from the standpoint of JTL's own SaaS connectors that run in JTL's network.
- **CO-3504** — sibling story "Integrate the PrestaShop Connector with the Telemetry Platform" (same shape as CO-3503, Inbox status) — confirms this WooCommerce work should be architecturally mirrored for Presta later; keep the design portable.
- **CO-3527** — "Telemetry data from Connector Core" (Inbox) — a **separate, not-yet-scoped** story about instrumenting `jtl/connector` core itself. Its own AC says "investigation... is done", "list of what we want to receive is provided", "legal approval is done" — i.e. core-level telemetry is **not yet designed** and is explicitly a different story. CO-3503 must not silently take on core instrumentation.
- **DATA-87** — "Connector metrics: central storage of connector telemetry in the Data Platform" — the downstream storage/BI consumer, out of scope for this ticket by definition.

### 3.1a Concrete data model (from `docs/telemetry/CO-3395-data-model.md`)

Storage backend is ClickHouse (columnar, `MergeTree`, monthly partitions, `LowCardinality(String)` for enums). The connector only needs to produce JSON payloads matching this shape — ClickHouse ingestion/retention is out of scope for this ticket (owned by CO-3526/DATA-87).

**Context** (denormalized onto every event, not its own table):
`tenant_id`, `tenant_name`, `connector_type` (enum: `shopify`/`shopware6`/`woocommerce`/`prestashop`), `connector_version`, `environment` (enum: `production`/`staging`/`development`), `wawi_type` (optional), `wawi_version` (optional).

**1. Transaction** (one row per order): `transaction_id`, `order_id`, `order_number`, `source_system`, `status` (`pending`/`completed`/`cancelled`/`error`), `total_amount`, `currency` (ISO 4217), `item_count`, `total_quantity`, `created_at`, `synced_at`, + Context fields.

**2. TransactionItem** (one row per order line): `item_id`, `transaction_id` (FK), `sku`, `product_name`, `quantity`, `unit_price`, `total_price`, `status` (`synced`/`failed`/`skipped`), `error_message` (optional).

**3. SyncRun** (one row per sync batch triggered by Wawi): `sync_run_id`, `triggered_by` (`wawi_scheduled`/`wawi_manual`/`webhook`), `direction` (`inbound`/`outbound`), `object_type` (`order`/`product`/`customer`/`inventory`/...), `scope` (`full`/`delta`), `timestamp_start`, `timestamp_end`, `duration_ms`, `records_total`, `records_succeeded`, `records_failed`, `result` (`success`/`partial`/`failed`), + Context fields.

**4. SyncItem** (one row per object within a SyncRun): `sync_item_id`, `sync_run_id` (FK), `transaction_id` (FK, optional), `object_ref` (human-readable source reference), `result` (`success`/`failed`/`skipped`), `error_code` (optional), `error_message` (optional), `duration_ms`.

**5. ApiRequest** (one row per outbound HTTP call, e.g. WooCommerce ↔ Wawi): `request_id`, `sync_run_id` (FK, optional), `sync_item_id` (FK, optional), `timestamp`, `target_system` (`shopify`/`shopware6`/`woocommerce`/`prestashop`/`erp`/`wawi`), `http_method`, `endpoint`, `http_status`, `duration_ms`, `request_size_bytes`/`response_size_bytes` (optional), `is_error`, `error_category` (`timeout`/`rate_limit`/`auth`/`server_error`/`client_error`), `error_message` (optional), `is_retry`, `retry_attempt`, `retry_of_request_id` (FK, optional), `is_rate_limited`, `rate_limit_wait_ms` (optional), + Context fields.

Notes for implementation: no `Nullable` fields — the model uses defaults (`''` for strings, `0` for numbers) instead of null, so PHP DTOs should follow the same convention (avoid nullable properties; use empty-string/zero sentinels) to match the wire format ClickHouse/CO-3526 expects. All ID fields are UUIDs. `dummy-data.sql` (`docs/telemetry/CO-3395-dummy-data.sql`) gives a worked example: a delta sync of 3 orders, 2 succeeding and 1 hitting a Shopify rate limit then retrying successfully before failing at the Wawi-write step with a timeout — useful as a reference fixture for Test Step 2/3 below.

### 3.2 Sibling connector repos checked for a reference implementation

Per CO-3480's epic text, Shopify and Shopware 6 "today" have telemetry integration. Both sibling repos are checked out locally:

- `/Users/Magnus.Pienkny/Public/connector-shopify`
- `/Users/Magnus.Pienkny/Public/connector-shopware6-saas`

`grep -ril "telemetry"` across both (excluding `vendor/`) returned **zero matches**. There is currently **no telemetry code to mirror** in either sibling repo checkout. Either the integration lives in a branch/commit not present in these local checkouts, or "today" in the epic text is aspirational/inaccurate. This must be treated as **not yet available as a reference pattern** — do not assume a specific class shape exists elsewhere; this WooCommerce integration will likely be a first-of-its-kind implementation for the PHP-based connectors (as opposed to the SaaS/Node-based Shopify/Shopware6 connectors, which may use an entirely different tech stack and thus a different telemetry client anyway).

### 3.3 WooCommerce connector codebase — hook points and constraints

- **Request entry point**: `/Users/Magnus.Pienkny/Public/connector-woocommerce3/includes/JtlConnector.php::capture_request()` (lines 23-61). Builds `FileConfig` from `config/config.json`, instantiates `Connector` and core `Application`, then calls `$application->run($connector)`. This is the single WordPress-side entry point for every JTL RPC call — the natural place to start/stop a request-level telemetry envelope (correlation ID, start timestamp) if we need something earlier than the core `Application` lifecycle.
- **Generic per-request/per-action hook (best fit for "technical request" events, CO-3398)**: `vendor/jtl/connector/src/Application/Application.php` dispatches, for every controller action:
  - `Event::createHandleEventName($controller, $action, Event::BEFORE)` (line ~653)
  - `Event::createHandleEventName($controller, $action, Event::AFTER)` (line ~664, plus a core/plugin variant at line ~669-671)
  This is dispatched by the **core** `Application` class regardless of controller, so a single Symfony `EventDispatcher` listener registered in `Connector::initialize()` (see below) can capture: controller name, action, start/end time → runtime, and (from the `Response`/thrown exception, if accessible via the event) success/error status — satisfying most of CO-3398's fields without touching every controller.
- **Business-level hook points already in `src/Connector.php`** (`/Users/Magnus.Pienkny/Public/connector-woocommerce3/src/Connector.php`):
  - `initialize(ConfigInterface $config, Container $container, EventDispatcher $dispatcher)` (lines 70-121) is where all current listeners are wired (e.g. the `Controller::CONNECTOR` / `Action::FINISH` / `Event::AFTER` listener at lines 107-120 that does post-sync bookkeeping like `countCategories()`, `countProductTags()`, `syncMasterProducts()`). A telemetry listener/service should be registered here, following the same pattern (static closures wired against `$dispatcher`, `Db`, `Util`).
  - `handle(Application $application, Request $request)` (lines 137-170) is the per-request dispatch to `HandlePullEvent` / `HandlePushEvent` / `HandleDeleteEvent` / `HandleStatsEvent` via `handleCallByPlugin()` (lines 189-212). These are WooCommerce-connector-specific custom events (not core) already fired for every pull/push/delete/statistic call — **this is the natural hook for synchronization events (CO-3397)**, since it already knows controller, action/direction (pull vs push vs delete), and the `QueryFilter[]`/entities involved. A telemetry listener on `HandlePullEvent::EVENT_NAME`, `HandlePushEvent::EVENT_NAME`, `HandleDeleteEvent::EVENT_NAME`, `HandleStatsEvent::EVENT_NAME` can record object type + direction + (post-dispatch) record counts.
  - Transaction data (CO-3396) is order/cart-specific and is **not naturally available** at the generic request-hook level — it requires listening at the `CustomerOrderController` (pull) level specifically, since `CustomerOrder` push is not supported (`features.json.example` shows `"CustomerOrder": {"pull": true, "push": false, "delete": false}`). See `/Users/Magnus.Pienkny/Public/connector-woocommerce3/src/Controllers/CustomerOrderController.php` and `src/Controllers/Order/` subdirectory (per project instructions) for the actual entity assembly point where order totals, currency, items, etc. are already computed and could be captured without re-deriving them.
- **No existing logging-to-remote infrastructure to piggyback on**: `vendor/jtl/connector/src/Logger/LoggerService.php` is a **Monolog file-logger only** (channels: `checksum`, `error`, `global`, `linker`, `rpc`, `session`; handler is `RotatingFileHandler`/`ChunkedHandler`, writing to `config/../var/log`). There is no HTTP/remote Monolog handler configured anywhere in `jtl/connector` core or in this connector. A telemetry sender is a **new, separate concern** — do not attempt to reuse `LoggerService` as the transport; at most, log the *fact* that telemetry was sent/failed to the existing `error`/`global` channels for local traceability (satisfies the "technically traceable and verifiable" AC).
- **No HTTP client dependency exists** in `composer.json` (this repo) or `vendor/jtl/connector/composer.json` (core) — neither has Guzzle, PSR-18, or any HTTP client library. Since this is a WordPress plugin, the idiomatic and dependency-free choice is WordPress's built-in `wp_remote_post()` (ideally with a non-blocking `'blocking' => false` request, or dispatched via `wp_schedule_single_event()` / WP-Cron for true async) rather than adding a new Composer dependency — this directly serves the "does not unduly interfere with ongoing operation" AC. This must be confirmed against whatever transport CO-3526's Azure API expects (JSON over HTTPS is the safe assumption, but auth/headers are unconfirmed — see 3.4).
- **Config/feature-flag conventions**:
  - `src/Utilities/Config.php` defines `wp_options`-backed keys as `public const string OPTIONS_* = 'jtlconnector_*'` (e.g. `OPTIONS_DEVELOPER_LOGGING = ConfigSchema::DEBUG` at line 40), a `DEFAULT_OPTIONS` array with fallback values, and a type-cast map (`'bool'`, `'string'`, etc.) used when reading options back. A telemetry on/off toggle (e.g. `OPTIONS_TELEMETRY_ENABLED`) and an endpoint override (if needed for staging vs. prod Azure endpoints) should follow this exact pattern, and be surfaced in `includes/JtlConnectorAdmin.php` next to the existing "Dev-Logs" settings UI (see lines ~1598-1621 for the existing radio-field pattern used for `OPTIONS_DEVELOPER_LOGGING`).
  - `config/config.json` is **not checked into git** (only `config/features.json.example` and `config/.htaccess` exist in the working tree) — it is created/managed per-deployment. `config/features.json` is auto-copied from `.example` on first request if missing (`JtlConnector.php` lines 54-56). This means any new telemetry config keys should default to a safe value (telemetry **disabled** or a placeholder endpoint) via `ConfigSchema`/`Config::DEFAULT_OPTIONS`, not rely on manual `config.json` edits being deployed everywhere immediately.
- **Multi-tenancy**: this connector is single-tenant per WordPress installation (unlike PrestaShop's system/customer DB split noted in prior plans) — so there is no cross-tenant DB isolation risk here, but the "shop instance / shop ID" and "customer/client" telemetry fields (per CO-3396/97/98) must be derived from local, non-PII WordPress site data (e.g. a hashed site URL or the existing JTL customer/token identifier from `Config::OPTIONS_TOKEN`), not raw PII.

### 3.4 Risks & Open Questions (must be resolved before implementation starts)

1. ~~**BLOCKER — concrete schema unread.**~~ **RESOLVED 2026-07-16.** Real schema and worked example now live at `docs/telemetry/CO-3395-data-model.md` / `docs/telemetry/CO-3395-dummy-data.sql` and are mapped in Section 3.1a. Remaining nuance: the schema is defined in terms of ClickHouse ingestion tables, not a wire/API payload spec — Open Question 2 (transport contract) must still confirm whether CO-3526's HTTP API expects one JSON object per row/entity (matching these tables 1:1) or a batched/enveloped shape.
2. **BLOCKER (updated 2026-08-10) — CO-3526 moved to Code Review, but its real architecture (OTLP + direct ClickHouse HTTP ingest, IP-allowlisted, see Section 3.1) does not by itself give WooCommerce a usable transport contract** — WooCommerce installs run on arbitrary, unpredictable per-tenant customer hosting IPs, which can't be enumerated in an allowlist the way JTL-controlled SaaS connector infrastructure can. **Working assumption, confirmed verbally with Magnus Pienkny 2026-08-10**: a JTL-hosted proxy/relay sits between self-hosted connectors and the IP-allowlisted ingest; design Step 3's client against a generic authenticated HTTP+JSON contract that would work against such a relay. **This relay is not documented in CO-3526 or elsewhere yet** — the concrete URL, auth scheme, and request/response shape remain unconfirmed, and this assumption must be revisited once the relay (or an alternative decision) is confirmed. No assignee currently on CO-3526 to follow up with; escalate through Magnus/the telemetry team.
3. **No reference implementation exists yet** in the Shopify/Shopware6 sibling repos checked locally, despite the epic (CO-3480) implying they're already integrated. If a reference implementation exists in a different branch/repo state, it should be located and consulted before designing WooCommerce's approach from scratch, to keep the "generic event model" consistent across connectors as required by AC 2.
4. **PII minimization — largely resolved by the schema itself.** The real `data-model.md` (Section 3.1a) has **no customer-name/email/address field anywhere** in `Transaction`/`TransactionItem`/`SyncRun`/`SyncItem`/`ApiRequest`/`Context` — only `tenant_id`/`tenant_name` (shop/install-level, not end-customer) and opaque IDs (`order_id`, `sku`, etc.). This means the model was designed to exclude end-customer PII by construction, not via hashing. Remaining residual risk: free-text fields like `product_name`, `order_number`, or `error_message` could incidentally contain PII (e.g. a personalized engraving product name, or a WaWi error message that echoes a customer email) — Step 6 should sanity-check these fields against realistic fixtures rather than assume the schema alone guarantees PII-safety.
5. **Failure-mode policy undefined.** What happens if the telemetry endpoint is unreachable/slow/rate-limited? Given "must not unduly interfere with ongoing operation," the design should assume **fire-and-forget, non-blocking, best-effort delivery** (no retries that block the JTL sync request/response cycle; queue-and-drop or WP-Cron-based async retry at most). This needs explicit sign-off since it affects whether "verifiable transmission" (AC) can realistically mean "confirmed delivery" or only "confirmed attempt logged locally."
6. **Versioning/back-compat of the event model.** Since CO-3395-98 are already "Fertig" (done) and presumably frozen, this connector must implement against whatever version is current at implementation time; if the schema evolves later, a version field in the envelope (if the data model defines one) should be honored so the connector doesn't need to be interpreted differently server-side.
7. **Performance**: Product and CustomerOrder pulls/pushes can be high-volume (bulk sync). Emitting one HTTP call per entity would be excessive. Likely need batching/aggregation (e.g. one synchronization-event per pull/push call summarizing N processed records, per CO-3397's model) rather than one event per entity — must be confirmed against the actual data model once available.

## 4. Implementation Steps

> Open Question 1 (real schema) is resolved — see Section 3.1a. **Do not start Steps 3-6 until Open Question 2 (CO-3526 transport contract: URL, auth, payload envelope) is resolved with Martin Kuen** — the DTO field names/types can now be finalized (Step 2), but the client/wiring steps still depend on the actual HTTP contract. Steps 1-2 can proceed now.

### Step 1: Add telemetry configuration keys and feature flag
- **Files**: `src/Utilities/Config.php`, `includes/JtlConnectorAdmin.php`
- **What**: Add `OPTIONS_TELEMETRY_ENABLED` (bool, default `false` until rollout is approved) and `OPTIONS_TELEMETRY_ENDPOINT` (string, default = production Azure URL once known) constants to `Config.php`, following the existing `OPTIONS_*` naming/const pattern (see line ~23 `OPTIONS_TOKEN`). Add corresponding entries to `Config::DEFAULT_OPTIONS` and the type-cast map. Add a settings UI toggle in `JtlConnectorAdmin.php` mirroring the existing "Dev-Logs" radio field block (~lines 1598-1621) so shop owners can see/control telemetry participation (supports the "does not unduly interfere" and general transparency expectations, and gives an operational kill-switch).
- **Why**: Establishes an explicit, reversible on/off switch before any network calls are wired up, consistent with existing config conventions.
- **Done when**: `Config::get(Config::OPTIONS_TELEMETRY_ENABLED, false)` resolves correctly with the option unset (defaults to disabled); settings page renders the new toggle without errors.

### Step 2: Define local telemetry event model classes (PHP DTOs)
- **Files**: new `src/Telemetry/Event/` directory: `Context.php` (or a shared trait), `TransactionEvent.php`, `TransactionItemEvent.php`, `SyncRunEvent.php`, `SyncItemEvent.php`, `ApiRequestEvent.php`
- **What**: Create `readonly` PHP classes (PHP 8.1+ `readonly` properties, `declare(strict_types=1)`) mapping 1:1 to the 5 entities + Context block defined in `docs/telemetry/CO-3395-data-model.md` (Section 3.1a of this plan). Follow the model's own conventions: no nullable properties — use `''`/`0` defaults for optional fields (e.g. `TransactionItem::$errorMessage`, `ApiRequest::$errorMessage`) to match the ClickHouse/wire format exactly. Enum-typed fields (`status`, `direction`, `result`, `error_category`, etc.) should be native PHP `enum` types where the model lists a closed value set, with a `->value` string matching the schema's string exactly.
- **Why**: Gives strong typing and a single, testable place that encodes "the integration is based on the defined generic event models" (AC).
- **Done when**: Classes exist for all 5 entities + Context, are constructible with all fields, (de)serialize to the exact field names/types in `CO-3395-data-model.md`, and a fixture built from `docs/telemetry/CO-3395-dummy-data.sql` round-trips through `toArray()`/`jsonSerialize()` unchanged. Exact request/response envelope (batched vs. one-call-per-row) still pending Open Question 2.

### Step 3: Build a telemetry client/transport service
- **Files**: new `src/Telemetry/TelemetryClient.php` (or `TelemetryDispatcher.php`)
- **What**: Implement a small service wrapping `wp_remote_post()` (non-blocking: `'blocking' => false`, short timeout) to POST a serialized event to `Config::get(Config::OPTIONS_TELEMETRY_ENDPOINT)`, guarded by `Config::get(Config::OPTIONS_TELEMETRY_ENABLED)`. On failure (non-2xx, WP_Error, timeout), log via the existing `LoggerService` (`error` or a new `telemetry` channel — see Step 3a) rather than throwing, so a telemetry outage never breaks a JTL sync request.
- **Why**: Isolates network I/O and failure handling in one place; satisfies "does not unduly interfere with ongoing operation" and "technically traceable and verifiable" (via structured local logging of send attempts/failures).
- **Done when**: Calling the client with a sample event, against a mocked/stubbed HTTP layer, either succeeds silently or logs a structured failure — and never throws an uncaught exception.

### Step 3a: Add a dedicated `telemetry` log channel (optional but recommended)
- **Files**: `src/Connector.php` (wherever `LoggerService::CHANNEL_*` channels are consumed) — note `LoggerService::CHANNEL_*` constants are defined in **core** (`vendor/jtl/connector/src/Logger/LoggerService.php`, lines 32-37); adding a new channel constant requires either a core PR (coordinate with jtl/connector maintainers, similar to the credentials-client coordination pattern) or, more pragmatically, calling `$loggerService->get('telemetry')` directly with a string literal (the `get()` method accepts any string, auto-creating the channel — see lines ~196-211 of `LoggerService.php`) without needing a core change.
- **What**: Use `$this->loggerService->get('telemetry')` from within `TelemetryClient` to record send attempts/successes/failures with correlation IDs.
- **Why**: Makes telemetry transmission "technically traceable and verifiable" per AC without waiting on an upstream core change.
- **Done when**: Telemetry send attempts appear in `var/log/telemetry.log` when developer logging / debug level permits.

### Step 4: Wire synchronization event emission into `Connector.php`
- **Files**: `src/Connector.php` (`initialize()` method, lines 70-121; `handleCallByPlugin()`, lines 189-212)
- **What**: Register additional listeners in `initialize()` on `HandlePullEvent::EVENT_NAME`, `HandlePushEvent::EVENT_NAME`, `HandleDeleteEvent::EVENT_NAME`, `HandleStatsEvent::EVENT_NAME` (all fired from `handleCallByPlugin()`) that build a `SyncRunEvent` DTO (one per call: `triggered_by`, `direction` inferred from which event fired, `object_type` from the controller, `scope`, `timestamp_start`/`timestamp_end`/`duration_ms`, `records_total`/`records_succeeded`/`records_failed`/`result` from `$event->getResult()` or `$requestParams`) plus one `SyncItemEvent` per processed entity (`object_ref`, `result`, `error_code`/`error_message`, `duration_ms`), and pass both to `TelemetryClient`. Follow the existing closure-based listener style already used at lines 90-104 and 107-120.
- **Why**: This is the lowest-friction, already-centralized point that sees every pull/push/delete/statistic call for every entity type, satisfying "relevant synchronization data ... is transferred" without instrumenting every individual controller.
- **Done when**: Triggering a pull/push/delete/statistic call in a local/test environment results in exactly one `SyncRunEvent` plus one `SyncItemEvent` per entity processed, with correct direction, object-type, and result-count fields matching the `docs/telemetry/CO-3395-dummy-data.sql` shape.

### Step 5: Wire technical request/error event emission at the generic request lifecycle
- **Files**: `src/Connector.php` (`initialize()`), possibly a new `src/Telemetry/RequestTelemetryListener.php`
- **What**: Register listeners on the core `Event::createHandleEventName($controller, $action, Event::BEFORE)` / `...AFTER` events (dispatched by `vendor/jtl/connector/src/Application/Application.php`, confirmed at lines ~653/664/669-671) to measure runtime (BEFORE timestamp → AFTER timestamp), capture `target_system`/`http_method`/`endpoint`-equivalent framework context, and detect error status (`is_error`, `error_category`, `http_status` if available — via a try/catch around dispatch if the core event does not directly expose exceptions; verify this against `Application.php`'s exact dispatch/exception-handling flow before finalizing) and retry/rate-limit fields (`is_retry`, `retry_attempt`, `retry_of_request_id`, `is_rate_limited`, `rate_limit_wait_ms`) where detectable at this layer. Emit an `ApiRequestEvent` per call via `TelemetryClient`.
- **Why**: Centralizes technical/error/runtime telemetry (CO-3398) at the framework level instead of duplicating instrumentation in every controller.
- **Done when**: A successful and a deliberately-failing request each produce exactly one `ApiRequestEvent` with correct `duration_ms` and `is_error`/`error_category` fields, matching the shape of the rate-limit/retry/timeout examples in `docs/telemetry/CO-3395-dummy-data.sql`.

### Step 6: Wire transaction event emission for order pulls
- **Files**: `src/Controllers/CustomerOrderController.php`, relevant classes under `src/Controllers/Order/` (per project architecture notes — verify exact sub-controller responsible for assembling order totals/items before editing)
- **What**: After an order (and its items) is successfully assembled for a `pull` response, build one `TransactionEvent` (`transaction_id`, `order_id`, `order_number`, `status`, `total_amount`, `currency`, `item_count`, `total_quantity`, `created_at`, `synced_at`) plus one `TransactionItemEvent` per line item (`item_id`, `sku`, `product_name`, `quantity`, `unit_price`, `total_price`, `status`, `error_message`) from already-computed order data and dispatch via `TelemetryClient`. Must reuse values already computed by the controller rather than re-querying the DB, to avoid added load. Must apply whatever pseudonymization strategy is settled in Open Question 4 to any end-customer reference before it leaves the process — note the real schema (Section 3.1a) has **no dedicated customer-identity field** on `Transaction`/`TransactionItem` at all, so this may already be satisfied by construction; confirm no customer PII is smuggled into `order_number` or `product_name` for edge cases (e.g. personalized product names).
- **Why**: Satisfies "relevant transaction data ... is transferred to the telemetry platform" (AC) at the one place order data is fully assembled.
- **Done when**: Pulling a real/test order produces one `TransactionEvent` plus one `TransactionItemEvent` per line, whose item count/totals match the order, matching the shape of `docs/telemetry/CO-3395-dummy-data.sql`'s order fixtures, and no raw customer PII (name/email/address) is present in the payload.

### Step 7: Document the technical implementation
- **Files**: `docs/telemetry/README.md` (new) or a section appended to this plan file once implemented; update `.github/copilot-instructions.md` "Architecture" section with a short "Telemetry" subsection once merged.
- **What**: Document: which events are emitted and from where, the config toggle, the endpoint contract version targeted, the PII-minimization approach used, and how to verify locally (e.g. tailing `var/log/telemetry.log`).
- **Why**: Explicit AC: "the technical implementation is documented."
- **Done when**: A new contributor can read the doc and understand what telemetry exists, how to disable it, and how to confirm it's working, without reading the Jira tickets.

## 5. Test Plan

All new telemetry code must have `@covers` annotations and extend `AbstractTestCase` (`/Users/Magnus.Pienkny/Public/connector-woocommerce3/tests/src/AbstractTestCase.php`), using its `createDbMock()`/`createUtilMock()`/`createContainerMock()` helpers and WP_Mock (per `tests/bootstrap.php`) to stub `wp_remote_post`, `get_option`, etc. Mirror `src/` under `tests/src/`.

### Test Step 1: Config defaults and toggle behavior
- **Test file**: `tests/src/Utilities/ConfigTest.php` (extend if it exists; otherwise create, mirroring existing `Utilities` test conventions)
- **Base class**: `AbstractTestCase`
- **Cases**:
  - `OPTIONS_TELEMETRY_ENABLED` defaults to `false` when the WP option is unset.
  - `OPTIONS_TELEMETRY_ENDPOINT` resolves to the configured default string.
  - Explicitly setting the option to `true`/`false` via `update_option` (mocked) round-trips correctly through `Config::get()`.
- **Done when**: All three cases pass; no real WordPress option table involved (WP_Mock stubs only).

### Test Step 2: Telemetry event DTO construction and serialization
- **Test file**: `tests/src/Telemetry/Event/TransactionEventTest.php`, `TransactionItemEventTest.php`, `SyncRunEventTest.php`, `SyncItemEventTest.php`, `ApiRequestEventTest.php`
- **Base class**: `AbstractTestCase`
- **Cases**:
  - Each DTO can be constructed with all fields (per Section 3.1a) and exposes them via getters.
  - Serialization (e.g. `toArray()`/`jsonSerialize()`) produces exactly the field names/types listed in `docs/telemetry/CO-3395-data-model.md` — this test is the executable contract test against the schema.
  - Optional fields default to `''`/`0` (never `null`), matching the model's "no Nullable" convention.
  - Missing a required field at construction time throws a `TypeError` (enforced by PHP's typed/readonly properties) rather than silently producing an incomplete payload.
- **Done when**: Serialized fixtures for at least one `Transaction`+`TransactionItem` and one `SyncRun`+`SyncItem`+`ApiRequest` chain can be diffed field-by-field against the worked example in `docs/telemetry/CO-3395-dummy-data.sql` and match exactly.

### Test Step 3: TelemetryClient transport and failure handling
- **Test file**: `tests/src/Telemetry/TelemetryClientTest.php`
- **Base class**: `AbstractTestCase`
- **Cases**:
  - When `OPTIONS_TELEMETRY_ENABLED` is `false`, no HTTP call is attempted (mock `wp_remote_post` and assert it is never called via WP_Mock's `Functions::expects()->never()`).
  - When enabled, `wp_remote_post` is called with the configured endpoint, non-blocking option, and a JSON body matching the event's serialized form.
  - When `wp_remote_post` returns a `WP_Error` or a non-2xx response, the client logs the failure (assert against an injected/mocked logger) and does **not** throw.
  - A real/unexpected `\Throwable` thrown deep in serialization is caught at the client boundary and logged, never propagated to the caller (defence-in-depth for "does not unduly interfere with ongoing operation").
- **Done when**: All four cases pass without any real network I/O (fully mocked).

### Test Step 4: Synchronization event emission from `Connector.php`
- **Test file**: `tests/src/ConnectorTest.php` (extend existing file — confirm current coverage first) or a new `tests/src/Telemetry/SynchronizationTelemetryListenerTest.php` if the logic is extracted into its own listener class (preferred, for testability — see Step 4/5 "Why extract a listener class" note below)
- **Base class**: `AbstractTestCase`
- **Cases**:
  - Dispatching a `HandlePullEvent` results in exactly one `TelemetryClient::send()` (mocked) call with a `SyncRunEvent` (`direction`/`object_type`/`scope` matching the event) plus one `SyncItemEvent` per entity processed.
  - Same for `HandlePushEvent` and `HandleDeleteEvent` (`direction`/`result` differ).
  - `HandleStatsEvent` emission — the real model has no `statistic` value in `object_type`/`triggered_by`; decide whether stats calls map to an existing `object_type` value or are excluded, and document the decision here once made (this is a small residual open question, not a full blocker).
  - `records_total`/`records_succeeded`/`records_failed`/`result` in the emitted `SyncRunEvent` match `$event->getResult()`/params for a representative fixture, matching `docs/telemetry/CO-3395-dummy-data.sql`'s `sync_runs` row (3 total, 2 succeeded, 1 failed, `result = 'partial'`).
- **Done when**: All listener registrations are exercised without requiring a real WordPress/database environment.

### Test Step 5: Technical request/error event emission
- **Test file**: `tests/src/Telemetry/RequestTelemetryListenerTest.php`
- **Base class**: `AbstractTestCase`
- **Cases**:
  - A successful BEFORE→AFTER pair produces one `ApiRequestEvent` with `duration_ms > 0` and `is_error = false`.
  - A request that throws inside the controller (simulate via a stub) produces an `ApiRequestEvent` with `is_error = true` and a populated `error_category`/`error_message`, and the exception still propagates/is handled per existing core behavior (this test must confirm the listener does not swallow or alter existing error handling).
  - A simulated rate-limit response followed by a retry produces two `ApiRequestEvent`s linked via `retry_of_request_id`, mirroring the rate-limit/retry pair in `docs/telemetry/CO-3395-dummy-data.sql`.
- **Done when**: Both cases pass in isolation from the real `Application` class (mock the dispatcher/event objects).

### Test Step 6: Transaction event emission for orders
- **Test file**: `tests/src/Controllers/CustomerOrderControllerTest.php` (extend if exists) or `tests/src/Telemetry/TransactionTelemetryTest.php`
- **Base class**: `AbstractTestCase` (or `ControllerTestCase` if one exists in this repo — verify; current listing shows no such class, only `AbstractTestCase`/`TestCase`, so confirm during implementation whether a controller-specific base is needed)
- **Cases**:
  - Pulling a fixture order with N items emits one `TransactionEvent` plus N `TransactionItemEvent`s with matching totals/currency/item count, matching the shape of an order row in `docs/telemetry/CO-3395-dummy-data.sql`.
  - The emitted payload contains no raw customer name/email/address string (assert against a denylist of PII-shaped fields/values from the fixture).
  - When telemetry is disabled, pulling an order still succeeds and returns the correct JTL response, with zero telemetry calls attempted.
- **Done when**: All cases pass and confirm PII minimization empirically, not just by code review.

## 6. Suggested Follow-up Agents (optional)

- **php-feature-developer**: Once Open Questions 1 and 2 are resolved (real schema + real endpoint contract obtained), this agent could implement Steps 1-3 (config, DTOs, HTTP client) as a first, self-contained PR.
- **php-feature-developer** (separate PR): Implement Steps 4-6 (the three event-emission integration points) once Steps 1-3 are merged, so each PR stays reviewable.
- **php-code-reviewer**: Review each PR for adherence to `declare(strict_types=1)`, PHPStan max-level cleanliness, and the "must not throw/block" requirement around all telemetry code paths.
- **php-qa**: After implementation, cross-check the final code against this plan's acceptance criteria mapping (Section 2) before closing CO-3503.

**No agent has been started.** Per instructions, implementation must not begin until you explicitly authorize it — and, separately, Open Questions 1-2 above should realistically be resolved with Martin Kuen / whoever holds `data-model.md` before any implementation agent is dispatched, or the first PR will likely need significant rework once the real schema surfaces.

## 7. Out of Scope

- Business analysis of telemetry data, KPI definitions, or BI visualization (explicitly excluded by the ticket).
- Building or modifying the receiving telemetry API / Azure infrastructure (CO-3526 — owned by Martin Kuen, a separate ticket).
- Defining or redefining the generic event/data models themselves (CO-3395/96/97/98 — already done, to be consumed as-is).
- Instrumenting `jtl/connector` core itself (CO-3527 — separate, not-yet-scoped story; legal approval for core-level data collection is explicitly still pending per that ticket).
- Any changes to the PrestaShop connector (CO-3504 — separate sibling story).
- Central storage/warehousing of telemetry data (DATA-87).
