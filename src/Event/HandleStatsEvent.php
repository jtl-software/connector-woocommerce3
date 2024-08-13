<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Event;

use Symfony\Contracts\EventDispatcher\Event;

class HandleStatsEvent extends Event
{
    public const EVENT_NAME = 'connector.handle.stats';

    protected mixed $result;
    protected string $controller;

    /**
     * @param string $controller
     */
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

    /**
     * @return mixed
     */
    public function getResult(): mixed
    {
        return $this->result;
    }

    /**
     * @param mixed $result
     * @return $this
     */
    public function setResult(mixed $result): static
    {
        $this->result = $result;
        return $this;
    }
}
