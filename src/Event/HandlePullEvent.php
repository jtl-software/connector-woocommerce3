<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Event;

use Jtl\Connector\Core\Model\QueryFilter;
use Symfony\Contracts\EventDispatcher\Event;

class HandlePullEvent extends Event
{
    public const EVENT_NAME = 'connector.handle.pull';

    protected $result;
    protected string $controller;

    /** @var QueryFilter[] $params */
    protected array $params;

    /**
     * @param string        $controller
     * @param QueryFilter[] $params
     */
    public function __construct(string $controller, array $params)
    {
        $this->controller = $controller;
        $this->params     = $params;
    }

    /**
     * @return string
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * @return QueryFilter[]
     */
    public function getParams(): array
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
