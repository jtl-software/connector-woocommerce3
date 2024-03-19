<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\GermanMarket;

use JtlWooCommerceConnector\Integrations\Plugins\AbstractPlugin;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;

/**
 * Class GermanMarket
 * @package JtlWooCommerceConnector\Integrations\Plugins\GermanMarket
 */
class GermanMarket extends AbstractPlugin
{
    /**
     * @return bool
     */
    public function canBeUsed(): bool
    {
        return SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET);
    }

    /**
     *
     */
    public function setAutoOptions()
    {
        //LIEFERZEITEN
        update_option('woocommerce_global_lieferzeit', '-1', true);
        //update_option('woocommerce_de_show_delivery_time_overview', 'off', true);
        update_option('woocommerce_de_show_delivery_time_product_page', 'on', true);
        update_option('woocommerce_de_show_delivery_time_checkout', 'on', true);
        update_option('woocommerce_de_show_delivery_time_order_summary', 'on', true);

        //GM STREICHPREISE DISABLE
        update_option('woocommerce_de_show_sale_label_overview', 'off', true);
        update_option('woocommerce_de_show_sale_label_product_page', 'off', true);

        //PRODUKTE
        update_option('german_market_attribute_in_product_name', 'off', true);
        update_option('gm_show_product_attributes', 'off', true);
        update_option('gm_show_single_price_of_order_items', 'on', true);

        update_option('german_market_product_images_in_order', 'on', true);
        update_option('german_market_product_images_in_cart', 'on', true);

        update_option('gm_gtin_activation', 'on', true);
        update_option('gm_gtin_product_pages', 'on', true);

        update_option('woocommerce_de_show_price_per_unit', 'on', true);
        update_option('woocommerce_de_automatic_calculation_ppu', 'on', true);
        update_option('woocommerce_de_automatic_calculation_use_wc_weight', 'off', true);
        update_option('woocommerce_de_automatic_calculation_use_wc_weight_scale_unit', 'kg', true);
        update_option('woocommerce_de_automatic_calculation_use_wc_weight_mult', '1', true);

        //Globale Optionen
        update_option('wgm_use_split_tax', 'on', true);
        update_option('gm_gross_shipping_costs_and_fees', 'off', true);
    }
}
