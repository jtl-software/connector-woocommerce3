<?php

namespace JtlWooCommerceConnector\Event;

use Symfony\Contracts\EventDispatcher\Event;

class HandlePullEvent extends Event
{
    public const EVENT_NAME = 'connector.handle.pull';

    protected $result;
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
