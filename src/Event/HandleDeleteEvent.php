<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Event;

use jtl\Connector\Result\Action;
use Symfony\Component\EventDispatcher\Event;

class HandleDeleteEvent extends Event
{
    const EVENT_NAME = 'connector.handle.delete';

    /**
     * @var Action
     */
    protected $result;
    protected $controller;
    protected $entities;

    public function __construct($controller, $entities)
    {
        $this->controller = $controller;
        $this->entities = $entities;
    }

    public function getController()
    {
        return $this->controller;
    }

    public function getEntities()
    {
        return $this->entities;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }
}
