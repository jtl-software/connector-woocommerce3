<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Event;

use Symfony\Component\EventDispatcher\Event;

class CanHandleEvent extends Event
{
    public const EVENT_NAME = 'connector.can_handle';

    protected $controller;
    protected $action;
    protected bool $canHandle = false;

    public function __construct($controller, $action)
    {
        $this->controller = $controller;
        $this->action     = $action;
    }

    public function getController()
    {
        return $this->controller;
    }

    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return bool
     */
    public function isCanHandle(): bool
    {
        return $this->canHandle;
    }

    /**
     * @param $canHandle
     * @return void
     */
    public function setCanHandle($canHandle): void
    {
        $this->canHandle = $this->canHandle || $canHandle;
    }
}