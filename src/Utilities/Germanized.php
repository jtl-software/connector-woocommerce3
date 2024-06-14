<?php

namespace JtlWooCommerceConnector\Utilities;

/**
 * UtilGermanized is a singleton that can be used by controllers or mappers that are meant for the Germanized plugin.
 * @package JtlWooCommerceConnector\Utilities
 */
class Germanized
{
    /**
     * @var array<int, string> Index used in database mapped to translated salutation.
     */
    private array $salutations;

    /**
     * @var array<string, string>
     */
    private static array $units = [
        'l'  => 'L',
        'ml' => 'mL',
        'dl' => 'dL',
        'cl' => 'cL',
    ];

    public function __construct()
    {
        $this->salutations = [
            1 => \__('Mr.', 'woocommerce-germanized'),// m
            2 => \__('Ms.', 'woocommerce-germanized') // f
        ];
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        include_once(\ABSPATH . 'wp-admin/includes/plugin.php');

        return \is_plugin_active('woocommerce-germanized/woocommerce-germanized.php');
    }

    /**
     * @param string $index
     * @return string
     */
    public function parseIndexToSalutation(string $index): string
    {
        return $this->salutations[(int)$index] ?? '';
    }

    /**
     * @param string $code
     * @return string
     */
    public function parseUnit(string $code): string
    {
        return \in_array($code, \array_keys(self::$units)) ? self::$units[$code] : $code;
    }

    /**
     * Backward compatibility method
     *
     * @param \WC_Product $wcProduct
     * @return bool
     */
    public function hasUnitProduct(\WC_Product $wcProduct): bool
    {
        if ($this->pluginVersionIsGreaterOrEqual('3.0.0')) {
            return \wc_gzd_get_gzd_product($wcProduct)->has_unit_product();
        }
        return $wcProduct->gzd_product->has_product_units();
    }

    /**
     * Backward compatibility method
     *
     * @param \WC_Product $wcProduct
     * @return mixed|null
     */
    public function getUnit(\WC_Product $wcProduct): mixed
    {
        if ($this->pluginVersionIsGreaterOrEqual('3.0.0')) {
            return \wc_gzd_get_gzd_product($wcProduct)->get_unit();
        }
        return $wcProduct->gzd_product->unit;
    }

    /**
     * Backward compatibility method
     *
     * @param \WC_Product $wcProduct
     * @return mixed|null
     */
    public function getUnitProduct(\WC_Product $wcProduct): mixed
    {
        if ($this->pluginVersionIsGreaterOrEqual('3.0.0')) {
            return \wc_gzd_get_gzd_product($wcProduct)->get_unit_product();
        }
        return $wcProduct->gzd_product->unit_product;
    }

    /**
     * Backward compatibility method
     *
     * @param \WC_Product $wcProduct
     * @return mixed|null
     */
    public function getUnitBase(\WC_Product $wcProduct): mixed
    {
        if ($this->pluginVersionIsGreaterOrEqual('3.0.0')) {
            return \wc_gzd_get_gzd_product($wcProduct)->get_unit_base();
        }
        return $wcProduct->gzd_product->unit_base;
    }

    /**
     * @param string $versionToCompare
     * @return bool
     */
    public function pluginVersionIsGreaterOrEqual(string $versionToCompare): bool
    {
        $currentVersion = SupportedPlugins::getVersionOf(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED);
        if (\is_null($currentVersion)) {
            $currentVersion = SupportedPlugins::getVersionOf(
                SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2
            );
        }

        $currentVersion = $currentVersion ?? '';

        if (\version_compare($currentVersion, $versionToCompare, '>=')) {
            return true;
        }
        return false;
    }
}
