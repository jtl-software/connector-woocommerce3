<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Event;

use jtl\Connector\Result\Action;
use Symfony\Component\EventDispatcher\Event;

class HandlePullEvent extends Event
{
    public const EVENT_NAME = 'connector.handle.pull';

    /**
     * @var Action
     */
    protected Action $result;
    protected $controller;
    protected $params;

    public function __construct($controller, $params)
    {
        $this->controller = $controller;
        $this->params     = $params;
    }

    public function getController()
    {
        return $this->controller;
    }

    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return Action
     */
    public function getResult(): Action
    {
        return $this->result;
    }

    /**
     * @param $result
     * @return $this
     */
    public function setResult($result): static
    {
        $this->result = $result;
        return $this;
    }
}
