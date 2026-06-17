<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Utilities;

/**
 * Central registry of all JTL Connector link-table names (without WordPress prefix).
 *
 * These constants hold the bare table suffixes. Prepend `$wpdb->prefix` at
 * runtime to obtain the fully-qualified table name.
 */
final class LinkTableNames
{
    public const string CATEGORY           = 'jtl_connector_link_category';
    public const string CROSSSELLING       = 'jtl_connector_link_crossselling';
    public const string CROSSSELLING_GROUP = 'jtl_connector_link_crossselling_group';
    public const string CURRENCY           = 'jtl_connector_link_currency';
    public const string CUSTOMER           = 'jtl_connector_link_customer';
    public const string CUSTOMER_GROUP     = 'jtl_connector_link_customer_group';
    public const string IMAGE              = 'jtl_connector_link_image';
    public const string LANGUAGE           = 'jtl_connector_link_language';
    public const string MANUFACTURER       = 'jtl_connector_link_manufacturer';
    public const string MANUFACTURER_UNIT  = 'jtl_connector_link_manufacturer_unit';
    public const string MEASUREMENT_UNIT   = 'jtl_connector_link_measurement_unit';
    public const string ORDER              = 'jtl_connector_link_order';
    public const string PAYMENT            = 'jtl_connector_link_payment';
    public const string PRODUCT            = 'jtl_connector_link_product';
    public const string SHIPPING_CLASS     = 'jtl_connector_link_shipping_class';
    public const string SHIPPING_METHOD    = 'jtl_connector_link_shipping_method';
    public const string SPECIFIC           = 'jtl_connector_link_specific';
    public const string SPECIFIC_VALUE     = 'jtl_connector_link_specific_value';
    public const string TAX_CLASS          = 'jtl_connector_link_tax_class';
}
