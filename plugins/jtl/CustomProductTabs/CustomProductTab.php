<?php

namespace jtl\CustomProductTabs;

use Nette\Utils\RegexpException;
use Nette\Utils\Strings;

/**
 * Class CustomProductTab
 * @package jtl\CustomProductTabs
 */
class CustomProductTab
{
    /**
     * @var string
     */
    protected string $id = '';

    /**
     * @var string
     */
    protected string $title = '';

    /**
     * @var string
     */
    protected string $content = '';

    /**
     * CustomProductTab constructor.
     * @param string $title
     * @param string $content
     * @throws RegexpException
     */
    public function __construct(string $title, string $content)
    {
        $this->id      = Strings::webalize($title);
        $this->title   = $title;
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return $this
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }
}
