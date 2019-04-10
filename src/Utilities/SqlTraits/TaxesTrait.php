<?php
/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 10:55
 */

namespace JtlWooCommerceConnector\Utilities\SqlTraits;


trait TaxesTrait {
	public static function taxClassByRate( $rate ) {
		global $wpdb;
		$wtr = $wpdb->prefix . 'woocommerce_tax_rates';
		
		return sprintf( "
            SELECT tax_rate_class
            FROM {$wtr}
            WHERE tax_rate = '%s'",
			number_format( $rate, 4 )
		);
	}
	
	public static function taxRateById( $taxRateId ) {
		global $wpdb;
		$wtr = $wpdb->prefix . 'woocommerce_tax_rates';
		
		return "SELECT tax_rate FROM {$wtr} WHERE tax_rate_id = {$taxRateId}";
	}
	
	public static function getAllTaxRates() {
		global $wpdb;
		$wtr = $wpdb->prefix . 'woocommerce_tax_rates';
		
		return "SELECT tax_rate FROM {$wtr} ORDER BY tax_rate DESC";
	}
}