<?php

declare(strict_types=1);

namespace Zen\UI;

class Layout
{
    protected string $view;
    protected array $data = [];
    protected array $sections = [];
    protected ?string $currentSection = null;

    public static function make(string $view): static
    {
        return new static($view);
    }

    public function __construct(string $view)
    {
        $this->view = $view;
    }

    public function with(array $data): static
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    public function section(string $name, callable $callback): void
    {
        $this->sections[$name] = $callback();
    }

    public function getSection(string $name): string
    {
        return $this->sections[$name] ?? '';
    }

    public function hasSection(string $name): bool
    {
        return isset($this->sections[$name]);
    }

    public function yield(string $name): string
    {
        return $this->getSection($name);
    }

    public function render(): string
    {
        $content = $this->renderView($this->view);
        return $this->injectSections($content);
    }

    protected function renderView(string $view): string
    {
        $path = dirname(__DIR__, 2) . '/views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($path)) {
            throw new \RuntimeException("View [{$view}] not found.");
        }

        extract($this->data);

        ob_start();
        require $path;
        return ob_get_clean();
    }

    protected function injectSections(string $content): string
    {
        foreach ($this->sections as $name => $content) {
            $placeholder = '@section(' . $name . ')';
            $content = str_replace($placeholder, $content, $content);

            $pattern = '/@section\(' . preg_quote($name, '/') . '\)([\s\S]*?)@endsection/';
            $content = preg_replace($pattern, '', $content);
        }

        $content = preg_replace('/@section\([^)]+\)[\s\S]*?@endsection/', '', $content);
        $content = preg_replace('/@yield\([^)]+\)/', '', $content);

        return $content;
    }
}
