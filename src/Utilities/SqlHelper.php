<?php

namespace JtlWooCommerceConnector\Utilities;

use JtlWooCommerceConnector\Utilities\SqlTraits\CategoryTrait;
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

class SqlHelper
{
    use CategoryTrait;
    use CrossSellingTrait;
    use CustomerTrait;
    use CustomerGroupTrait;
    use CustomerOrderTrait;
    use GermanMarketTrait;
    use GermanizedDataTrait;
    use GlobalDataTrait;
    use ImageTrait;
    use ManufacturerTrait;
    use PaymentTrait;
    use PrimaryKeyMappingTrait;
    use ProductTrait;
    use SpecificTrait;
    use TaxesTrait;
    use WooCommerceDataTrait;
}
