<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Utilities;

use JtlWooCommerceConnector\Utilities\SqlTraits\CategoryTrait;
use JtlWooCommerceConnector\Utilities\SqlTraits\ChecksumTrait;
use JtlWooCommerceConnector\Utilities\SqlTraits\CrossSellingTrait;
use JtlWooCommerceConnector\Utilities\SqlTraits\CustomerGroupTrait;
use JtlWooCommerceConnector\Utilities\SqlTraits\CustomerOrderTrait;
use JtlWooCommerceConnector\Utilities\SqlTraits\CustomerTrait;
use JtlWooCommerceConnector\Utilities\SqlTraits\GermanizedDataTrait;
use JtlWooCommerceConnector\Utilities\SqlTraits\GermanMarketTrait;
use JtlWooCommerceConnector\Utilities\SqlTraits\GlobalDataTrait;
use JtlWooCommerceConnector\Utilities\SqlTraits\ImageTrait;
use JtlWooCommerceConnector\Utilities\SqlTraits\ManufacturerTrait;
use JtlWooCommerceConnector\Utilities\SqlTraits\PaymentTrait;
use JtlWooCommerceConnector\Utilities\SqlTraits\PrimaryKeyMappingTrait;
use JtlWooCommerceConnector\Utilities\SqlTraits\ProductTrait;
use JtlWooCommerceConnector\Utilities\SqlTraits\SpecificTrait;
use JtlWooCommerceConnector\Utilities\SqlTraits\TaxesTrait;
use JtlWooCommerceConnector\Utilities\SqlTraits\WooCommerceDataTrait;

final class SqlHelper {
	use CategoryTrait,
		ChecksumTrait,
		CrossSellingTrait,
		CustomerTrait,
        CustomerGroupTrait,
		CustomerOrderTrait,
        GermanMarketTrait,
		GermanizedDataTrait,
		GlobalDataTrait,
		ImageTrait,
        ManufacturerTrait,
		PaymentTrait,
		PrimaryKeyMappingTrait,
		ProductTrait,
		SpecificTrait,
		TaxesTrait,
		WooCommerceDataTrait;
}
