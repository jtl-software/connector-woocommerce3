<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Controllers;

use DI\Container;
use DI\ContainerBuilder;
use JtlWooCommerceConnector\Controllers\AbstractController;
use JtlWooCommerceConnector\Controllers\GlobalData\TaxRateController;
use JtlWooCommerceConnector\Tests\AbstractTestCase;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Util;

class AbstractControllerTest extends AbstractTestCase
{
    /**
     * The core framework resolves controllers from a PHP-DI container with attributes enabled.
     *
     * @return void
     * @throws \Exception
     * @covers \JtlWooCommerceConnector\Controllers\AbstractController::setContainer
     */
    public function testContainerIsInjectedWhenControllerIsResolved(): void
    {
        $container = (new ContainerBuilder())
            ->useAutowiring(true)
            ->useAttributes(true)
            ->build();
        $container->set(Db::class, $this->createMock(Db::class));
        $container->set(Util::class, $this->createMock(Util::class));

        $controller = $container->get(TaxRateController::class);

        $this->assertInstanceOf(TaxRateController::class, $controller);
        $property = new \ReflectionProperty(AbstractController::class, 'container');
        $this->assertTrue($property->isInitialized($controller));
        $this->assertSame($container, $property->getValue($controller));
    }
}
