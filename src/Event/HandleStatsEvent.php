<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Event;

use Jtl\Connector\Core\Model\Statistic;
use Symfony\Contracts\EventDispatcher\Event;

class HandleStatsEvent extends Event
{
    public const EVENT_NAME = 'connector.handle.stats';

    protected $result;
    protected string $controller;

    public function __construct(string $controller)
    {
        $this->controller = $controller;
    }

    /**
     * @return string
     */
    public function getController(): string
    {
        return $this->controller;
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
