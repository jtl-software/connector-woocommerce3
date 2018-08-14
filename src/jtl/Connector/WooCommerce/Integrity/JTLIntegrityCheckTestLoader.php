<?php

namespace jtl\Connector\WooCommerce\Integrity;

use Jtl\Connector\Integrity\Models\Test\AbstractTestLoader;
use Jtl\Connector\Integrity\Shops\WooCommerce\Tests\DbConnectionTest;
use Jtl\Connector\Integrity\Shops\WooCommerce\Tests\DuplicatedSkuTest;
use Jtl\Connector\Integrity\Shops\WooCommerce\Tests\NotSupportedProductTypesTest;
use Jtl\Connector\Integrity\Shops\WooCommerce\Tests\OrphanCategoriesTest;
use Jtl\Connector\Integrity\Shops\WooCommerce\Tests\OrphanVarCombisTest;
use Jtl\Connector\Integrity\Shops\WooCommerce\Tests\ProductsWithoutCategoriesTest;
use Jtl\Connector\Integrity\Shops\WooCommerce\Tests\ProductsWithoutPriceTest;
use Jtl\Connector\Integrity\Shops\WooCommerce\Tests\VarCombiChildrenWithSimpleFatherTest;
use Jtl\Connector\Integrity\Shops\WooCommerce\Tests\VarCombiProductsWithoutVariationsTest;

class JTLIntegrityCheckTestLoader extends AbstractTestLoader
{
    public function __construct()
    {
        parent::__construct();
        
        $sort = 1;
        
        $this->addTest(new DbConnectionTest($sort++));
        //$this->addTest(new OrphanCategoriesTest($sort++));
        //$this->addTest(new ProductsWithoutCategoriesTest($sort++));
        //$this->addTest(new ProductsWithoutPriceTest($sort++));
        $this->addTest(new NotSupportedProductTypesTest($sort++));
        $this->addTest(new DuplicatedSkuTest($sort++));
        //$this->addTest(new OrphanVarCombisTest($sort++));
        $this->addTest(new VarCombiChildrenWithSimpleFatherTest($sort++));
        $this->addTest(new VarCombiProductsWithoutVariationsTest($sort++));
    }
}
