<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Utilities;

/**
 * UtilGermanized is a singleton that can be used by controllers or mappers that are meant for the Germanized plugin.
 *
 * @package JtlWooCommerceConnector\Utilities
 */
class Germanized
{
    /** @var array<int, string> Index used in database mapped to translated salutation. */
    private array $salutations;

    /** @var array<string, string> */
    private static array $units = [
        'l'  => 'L',
        'ml' => 'mL',
        'dl' => 'dL',
        'cl' => 'cL',
    ];

    /**
     * Germanized constructor.
     */
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
            $gzdProduct = \wc_gzd_get_gzd_product($wcProduct);

            if (\is_bool($gzdProduct)) {
                return false;
            } else {
                return $gzdProduct->has_unit_product();
            }
        }
        /** @phpstan-ignore property.notFound */
        return $wcProduct->gzd_product->has_product_units();
    }

    /**
     * Backward compatibility method
     *
     * @param \WC_Product $wcProduct
     * @return string|false|null
     */
    public function getUnit(\WC_Product $wcProduct): string|false|null
    {
        if ($this->pluginVersionIsGreaterOrEqual('3.0.0')) {
            $gzdProduct = \wc_gzd_get_gzd_product($wcProduct);

            if (\is_bool($gzdProduct)) {
                return false;
            } else {
                /** @var false|string|null $gzdProductUnit */
                $gzdProductUnit = $gzdProduct->get_unit();
                return $gzdProductUnit;
            }
        }
        /** @var false|string|null $gzdProductUnit */
        $gzdProductUnit = $wcProduct->gzd_product->unit; /** @phpstan-ignore property.notFound */
        return $gzdProductUnit;
    }

    /**
     * Backward compatibility method
     *
     * @param \WC_Product $wcProduct
     * @return false|string|null
     */
    public function getUnitProduct(\WC_Product $wcProduct): false|null|string
    {
        if ($this->pluginVersionIsGreaterOrEqual('3.0.0')) {
            $gzdProduct = \wc_gzd_get_gzd_product($wcProduct);

            if (\is_bool($gzdProduct)) {
                return false;
            } else {
                /** @var false|string|null $gzdUnitProduct */
                $gzdUnitProduct = $gzdProduct->get_unit_product();
                return $gzdUnitProduct;
            }
        }
        /** @var false|string|null $gzdUnitProduct */
        $gzdUnitProduct = $wcProduct->gzd_product->unit_product; /** @phpstan-ignore property.notFound */
        return $gzdUnitProduct;
    }

    /**
     * Backward compatibility method
     *
     * @param \WC_Product $wcProduct
     * @return false|string|null
     */
    public function getUnitBase(\WC_Product $wcProduct): false|null|string
    {
        if ($this->pluginVersionIsGreaterOrEqual('3.0.0')) {
            $gzdProduct = \wc_gzd_get_gzd_product($wcProduct);

            if (\is_bool($gzdProduct)) {
                return false;
            } else {
                /** @var false|string|null $gzdUnitBase */
                $gzdUnitBase = $gzdProduct->get_unit_base();
                return $gzdUnitBase;
            }
        }
        /** @var false|string|null $gzdUnitBase */
        $gzdUnitBase = $wcProduct->gzd_product->unit_base; /** @phpstan-ignore property.notFound */
        return $gzdUnitBase;
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
