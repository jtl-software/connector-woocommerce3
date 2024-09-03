<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Event;

use Jtl\Connector\Core\Model\QueryFilter;
use Symfony\Contracts\EventDispatcher\Event;

class HandleDeleteEvent extends Event
{
    public const EVENT_NAME = 'connector.handle.delete';

    protected mixed $result = null;
    protected string $controller;
    /** @var QueryFilter[]  */
    protected array $entities;

    /**
     * @param string        $controller
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

    /**
     * @return mixed
     */
    public function getResult(): mixed
    {
        return $this->result;
    }

    /**
     * @param mixed $result
     * @return static
     */
    public function setResult(mixed $result): static
    {
        $this->result = $result;
        return $this;
    }
}
