<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Event;

use Symfony\Component\EventDispatcher\Event;

class CanHandleEvent extends Event
{
    const EVENT_NAME = 'connector.can_handle';

    protected $controller;
    protected $action;
    protected $canHandle = false;

    public function __construct($controller, $action)
    {
        $this->controller = $controller;
        $this->action = $action;
    }

    public function getController()
    {
        return $this->controller;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function isCanHandle()
    {
        return $this->canHandle;
    }

    public function setCanHandle($canHandle)
    {
        $this->canHandle = $this->canHandle || $canHandle;
    }
}
