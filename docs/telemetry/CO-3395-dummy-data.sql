-- ============================================================
-- Connector Telemetry: Dummy-Daten fuer ClickHouse
-- ============================================================
-- Szenario: WaWi loest einen Delta-Sync aus und holt 3 neue
-- Orders aus Shopify. 2 Orders erfolgreich, 1 fehlgeschlagen
-- (Rate Limit + Retry).
-- ============================================================

-- ------------------------------------------------------------
-- 1. transactions
-- ------------------------------------------------------------
INSERT INTO transactions (
    transaction_id, order_id, order_number, source_system, status,
    total_amount, currency, item_count, total_quantity,
    created_at, synced_at,
    tenant_id, tenant_name, connector_type, connector_version,
    environment, wawi_type, wawi_version
) VALUES
(
    'a1b2c3d4-0001-4000-8000-000000000001',
    'shopify-7001', '#7001', 'shopify', 'completed',
    149.97, 'EUR', 3, 5,
    '2026-04-30 09:12:00.000', '2026-04-30 09:15:02.000',
    'tenant-100', 'Mustermann GmbH', 'shopify', '2.4.1',
    'production', 'JTL-Wawi', '1.9.4'
),
(
    'a1b2c3d4-0002-4000-8000-000000000002',
    'shopify-7002', '#7002', 'shopify', 'completed',
    59.99, 'EUR', 1, 1,
    '2026-04-30 09:13:10.000', '2026-04-30 09:15:03.000',
    'tenant-100', 'Mustermann GmbH', 'shopify', '2.4.1',
    'production', 'JTL-Wawi', '1.9.4'
),
(
    'a1b2c3d4-0003-4000-8000-000000000003',
    'shopify-7003', '#7003', 'shopify', 'error',
    234.50, 'EUR', 2, 3,
    '2026-04-30 09:14:22.000', '2026-04-30 09:15:05.000',
    'tenant-100', 'Mustermann GmbH', 'shopify', '2.4.1',
    'production', 'JTL-Wawi', '1.9.4'
);

-- ------------------------------------------------------------
-- 2. transaction_items
-- ------------------------------------------------------------
INSERT INTO transaction_items (
    item_id, transaction_id, sku, product_name,
    quantity, unit_price, total_price, status, error_message
) VALUES
-- Order #7001: 3 Positionen
(
    'b1b2c3d4-0001-4000-8000-000000000001',
    'a1b2c3d4-0001-4000-8000-000000000001',
    'SKU-1001', 'T-Shirt Blau Gr. M',
    2, 29.99, 59.98, 'synced', ''
),
(
    'b1b2c3d4-0002-4000-8000-000000000002',
    'a1b2c3d4-0001-4000-8000-000000000001',
    'SKU-1002', 'Jeans Slim Fit W32',
    1, 69.99, 69.99, 'synced', ''
),
(
    'b1b2c3d4-0003-4000-8000-000000000003',
    'a1b2c3d4-0001-4000-8000-000000000001',
    'SKU-1003', 'Socken 3er-Pack',
    2, 10.00, 20.00, 'synced', ''
),
-- Order #7002: 1 Position
(
    'b1b2c3d4-0004-4000-8000-000000000004',
    'a1b2c3d4-0002-4000-8000-000000000002',
    'SKU-2001', 'Sneaker Weiss Gr. 43',
    1, 59.99, 59.99, 'synced', ''
),
-- Order #7003: 2 Positionen (fehlgeschlagen)
(
    'b1b2c3d4-0005-4000-8000-000000000005',
    'a1b2c3d4-0003-4000-8000-000000000003',
    'SKU-3001', 'Winterjacke Schwarz L',
    1, 189.50, 189.50, 'failed', 'Timeout beim Schreiben in WaWi'
),
(
    'b1b2c3d4-0006-4000-8000-000000000006',
    'a1b2c3d4-0003-4000-8000-000000000003',
    'SKU-3002', 'Schal Grau',
    2, 22.50, 45.00, 'failed', 'Timeout beim Schreiben in WaWi'
);

-- ------------------------------------------------------------
-- 3. sync_runs
-- ------------------------------------------------------------
INSERT INTO sync_runs (
    sync_run_id, triggered_by, direction, object_type, scope,
    timestamp_start, timestamp_end, duration_ms,
    records_total, records_succeeded, records_failed, result,
    tenant_id, tenant_name, connector_type, connector_version,
    environment, wawi_type, wawi_version
) VALUES
(
    'c1b2c3d4-0001-4000-8000-000000000001',
    'wawi_scheduled', 'inbound', 'order', 'delta',
    '2026-04-30 09:15:00.000', '2026-04-30 09:15:05.000', 5000,
    3, 2, 1, 'partial',
    'tenant-100', 'Mustermann GmbH', 'shopify', '2.4.1',
    'production', 'JTL-Wawi', '1.9.4'
);

-- ------------------------------------------------------------
-- 4. sync_items
-- ------------------------------------------------------------
INSERT INTO sync_items (
    sync_item_id, sync_run_id, transaction_id, object_ref,
    result, error_code, error_message, duration_ms
) VALUES
(
    'd1b2c3d4-0001-4000-8000-000000000001',
    'c1b2c3d4-0001-4000-8000-000000000001',
    'a1b2c3d4-0001-4000-8000-000000000001',
    'Shopify Order #7001',
    'success', '', '', 1200
),
(
    'd1b2c3d4-0002-4000-8000-000000000002',
    'c1b2c3d4-0001-4000-8000-000000000001',
    'a1b2c3d4-0002-4000-8000-000000000002',
    'Shopify Order #7002',
    'success', '', '', 800
),
(
    'd1b2c3d4-0003-4000-8000-000000000003',
    'c1b2c3d4-0001-4000-8000-000000000001',
    'a1b2c3d4-0003-4000-8000-000000000003',
    'Shopify Order #7003',
    'failed', 'RATE_LIMIT', 'Shopify API Rate Limit erreicht, Retry fehlgeschlagen', 3000
);

-- ------------------------------------------------------------
-- 5. api_requests
-- ------------------------------------------------------------
INSERT INTO api_requests (
    request_id, sync_run_id, sync_item_id, timestamp,
    target_system, http_method, endpoint, http_status,
    duration_ms, request_size_bytes, response_size_bytes,
    is_error, error_category, error_message,
    is_retry, retry_attempt, retry_of_request_id,
    is_rate_limited, rate_limit_wait_ms,
    tenant_id, tenant_name, connector_type, connector_version,
    environment, wawi_type, wawi_version
) VALUES
-- Batch-Abruf: GET /orders (alle 3 Orders)
(
    'e1b2c3d4-0001-4000-8000-000000000001',
    'c1b2c3d4-0001-4000-8000-000000000001',
    '',
    '2026-04-30 09:15:00.100',
    'shopify', 'GET', '/admin/api/2024-01/orders.json', 200,
    320, 128, 12400,
    0, '', '',
    0, 0, '',
    0, 0,
    'tenant-100', 'Mustermann GmbH', 'shopify', '2.4.1',
    'production', 'JTL-Wawi', '1.9.4'
),
-- Order #7001 → WaWi schreiben (OK)
(
    'e1b2c3d4-0002-4000-8000-000000000002',
    'c1b2c3d4-0001-4000-8000-000000000001',
    'd1b2c3d4-0001-4000-8000-000000000001',
    '2026-04-30 09:15:00.500',
    'wawi', 'POST', '/api/v1/orders', 201,
    450, 2048, 256,
    0, '', '',
    0, 0, '',
    0, 0,
    'tenant-100', 'Mustermann GmbH', 'shopify', '2.4.1',
    'production', 'JTL-Wawi', '1.9.4'
),
-- Order #7002 → WaWi schreiben (OK)
(
    'e1b2c3d4-0003-4000-8000-000000000003',
    'c1b2c3d4-0001-4000-8000-000000000001',
    'd1b2c3d4-0002-4000-8000-000000000002',
    '2026-04-30 09:15:01.500',
    'wawi', 'POST', '/api/v1/orders', 201,
    380, 1024, 256,
    0, '', '',
    0, 0, '',
    0, 0,
    'tenant-100', 'Mustermann GmbH', 'shopify', '2.4.1',
    'production', 'JTL-Wawi', '1.9.4'
),
-- Order #7003 → Shopify Detail-Abruf: Rate Limit!
(
    'e1b2c3d4-0004-4000-8000-000000000004',
    'c1b2c3d4-0001-4000-8000-000000000001',
    'd1b2c3d4-0003-4000-8000-000000000003',
    '2026-04-30 09:15:02.000',
    'shopify', 'GET', '/admin/api/2024-01/orders/7003.json', 429,
    85, 128, 64,
    1, 'rate_limit', 'Too Many Requests',
    0, 0, '',
    1, 2000,
    'tenant-100', 'Mustermann GmbH', 'shopify', '2.4.1',
    'production', 'JTL-Wawi', '1.9.4'
),
-- Order #7003 → Retry nach Rate Limit (OK diesmal)
(
    'e1b2c3d4-0005-4000-8000-000000000005',
    'c1b2c3d4-0001-4000-8000-000000000001',
    'd1b2c3d4-0003-4000-8000-000000000003',
    '2026-04-30 09:15:04.100',
    'shopify', 'GET', '/admin/api/2024-01/orders/7003.json', 200,
    290, 128, 4800,
    0, '', '',
    1, 1, 'e1b2c3d4-0004-4000-8000-000000000004',
    0, 0,
    'tenant-100', 'Mustermann GmbH', 'shopify', '2.4.1',
    'production', 'JTL-Wawi', '1.9.4'
),
-- Order #7003 → WaWi schreiben: Timeout
(
    'e1b2c3d4-0006-4000-8000-000000000006',
    'c1b2c3d4-0001-4000-8000-000000000001',
    'd1b2c3d4-0003-4000-8000-000000000003',
    '2026-04-30 09:15:04.500',
    'wawi', 'POST', '/api/v1/orders', 504,
    30000, 2560, 0,
    1, 'timeout', 'Gateway Timeout',
    0, 0, '',
    0, 0,
    'tenant-100', 'Mustermann GmbH', 'shopify', '2.4.1',
    'production', 'JTL-Wawi', '1.9.4'
);
