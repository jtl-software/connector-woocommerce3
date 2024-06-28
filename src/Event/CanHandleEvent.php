<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Event;

use Symfony\Contracts\EventDispatcher\Event;

class CanHandleEvent extends Event
{
    public const EVENT_NAME = 'connector.can_handle';

    protected string $controller;
    protected string $action;
    protected bool $canHandle = false;

    /**
     * @param string $controller
     * @param string $action
     */
    public function __construct(string $controller, string $action)
    {
        $this->controller = $controller;
        $this->action     = $action;
    }

    /**
     * @return string
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * @return string
     */
    public function getAction(): string
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
     * @param bool $canHandle
     * @return void
     */
    public function setCanHandle(bool $canHandle): void
    {
        $this->canHandle = $this->canHandle || $canHandle;
    }
}
