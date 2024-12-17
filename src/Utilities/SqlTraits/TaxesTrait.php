<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Utilities\SqlTraits;

use Jtl\Connector\Core\Model\TaxRate;

trait TaxesTrait
{
    /**
     * @param float $rate
     * @return string
     */
    public static function taxClassByRate(float $rate): string
    {
        global $wpdb;
        $wtr = $wpdb->prefix . 'woocommerce_tax_rates';

        return \sprintf(
            "
            SELECT tax_rate_class
            FROM {$wtr}
            WHERE tax_rate = '%s'",
            \number_format($rate, 4)
        );
    }

    /**
     * @param TaxRate ...$taxRates
     * @return string
     */
    public static function getTaxClassByTaxRates(TaxRate ...$taxRates): string
    {
        global $wpdb;

        $conditions = [];
        foreach ($taxRates as $taxRate) {
            $conditions[] = \sprintf(
                "(tax_rate_country = '%s' AND tax_rate='%s')",
                $wpdb->_escape($taxRate->getCountryIso()),
                \number_format($taxRate->getRate(), 4)
            );
        }

        return \sprintf(
            'SELECT DISTINCT tax_rate_class AS taxClassName, COUNT(tax_rate_class) AS hits 
                FROM %swoocommerce_tax_rates 
                WHERE %s 
                GROUP BY tax_rate_class 
                ORDER BY hits DESC',
            $wpdb->prefix,
            \join(' OR ', $conditions)
        );
    }

    /**
     * @param int|string $taxRateId
     * @return string
     */
    public static function taxRateById(int|string $taxRateId): string
    {
        global $wpdb;
        $wtr = $wpdb->prefix . 'woocommerce_tax_rates';

        return "SELECT tax_rate FROM {$wtr} WHERE tax_rate_id = {$taxRateId}";
    }

    /**
     * @return string
     */
    public static function getAllTaxRates(): string
    {
        global $wpdb;
        $wtr = $wpdb->prefix . 'woocommerce_tax_rates';

        return "SELECT tax_rate_country, tax_rate, tax_rate_class FROM {$wtr} ORDER BY tax_rate DESC";
    }
}
