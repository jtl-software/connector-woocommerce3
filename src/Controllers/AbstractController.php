<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers;

use DI\Attribute\Inject;
use DI\Container;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Util;

abstract class AbstractController
{
    protected Db $db;

    protected Container $container;

    protected Util $util;

    /**
     * @param Db   $db
     * @param Util $util
     */
    public function __construct(Db $db, Util $util)
    {
        $this->db   = $db;
        $this->util = $util;
    }

    /**
     * @param Container $container
     * @return void
     */
    #[Inject]
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }
}
