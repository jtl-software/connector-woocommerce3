<?php

namespace JtlWooCommerceConnector\Controllers;

use DI\Annotation\Inject;
use DI\Container;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Util;

abstract class AbstractController
{
    /**
     * @var Db
     */
    protected $db;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Util
     */
    protected $util;

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
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }
}
