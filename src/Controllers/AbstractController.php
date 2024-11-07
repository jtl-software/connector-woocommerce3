<?php

namespace JtlWooCommerceConnector\Controllers;

use DI\Container;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Util;

abstract class AbstractController
{
    /**
     * @var Db
     */
    protected Db $db;

    /**
     * @var Container
     */
    protected Container $container;

    /**
     * @var Util
     */
    protected Util $util;

    /**
     * @param Db $db
     * @param Util $util
     */
    public function __construct(Db $db, Util $util)
    {
        $this->db   = $db;
        $this->util = $util;
    }

    /**
     * @Inject
     * @param Container $container
     * @return void
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }
}
