<?php

declare(strict_types=1);

namespace Zen\UI;

abstract class Page
{
    protected string $title = '';
    protected ?string $layout = null;

    public function render(): string
    {
        return '';
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function layout(?string $layout): static
    {
        $this->layout = $layout;
        return $this;
    }

    public function getLayout(): ?string
    {
        return $this->layout;
    }
}
