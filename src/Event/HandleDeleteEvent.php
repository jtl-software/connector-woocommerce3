<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Event;

use Jtl\Connector\Core\Model\QueryFilter;
use Symfony\Contracts\EventDispatcher\Event;

class HandleDeleteEvent extends Event
{
    public const EVENT_NAME = 'connector.handle.delete';

    protected $result;
    protected string $controller;
    protected array $entities;

    /**
     * @param string $controller
     * @param QueryFilter[] $entities
     */
    public function __construct(string $controller, array $entities)
    {
        $this->controller = $controller;
        $this->entities   = $entities;
    }

    /**
     * @return string
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * @return array|QueryFilter[]
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param $result
     * @return static
     */
    public function setResult($result): static
    {
        $this->result = $result;
        return $this;
    }
}
