<?php

namespace JtlWooCommerceConnector\Event;

use Symfony\Contracts\EventDispatcher\Event;

class HandlePushEvent extends Event
{
    public const EVENT_NAME = 'connector.handle.push';

    protected $result;
    protected $controller;
    protected $entities;

    public function __construct($controller, $entities)
    {
        $this->controller = $controller;
        $this->entities   = $entities;
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

    public function setResult($result): static
    {
        $this->result = $result;
        return $this;
    }
}
