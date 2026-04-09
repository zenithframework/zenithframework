<?php

declare(strict_types=1);

namespace Zenith\UI;

use Zenith\UI\ZenTemplate;

class TemplateCompiler
{
    protected string $viewsPath;
    protected string $cachePath;
    protected array $stacks = [];
    protected array $sections = [];
    protected ?string $currentSection = null;
    protected ?string $currentStack = null;
    protected array $data = [];
    protected ?string $extends = null;
    protected array $components = [];
    protected ?string $currentComponent = null;
    protected array $componentData = [];

    public function __construct()
    {
        $this->viewsPath = dirname(__DIR__, 2) . '/views';
        $this->cachePath = dirname(__DIR__, 2) . '/storage/framework/views';
        
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    public function compile(string $template, array $data = []): string
    {
        $this->data = $data;
        
        $path = $this->resolvePath($template);
        
        if (!file_exists($path)) {
            throw new \RuntimeException("Template [{$template}] not found.");
        }

        $content = file_get_contents($path);
        
        return $this->compileContent($content);
    }

    public function compileContent(string $content): string
    {
        return TemplateDirectives::compile($content);
    }

    protected function compileShortConditionals(string $content): string
    {
        $content = preg_replace(
            '/@sectionIs\((["\'])(.*?)\1,\s*(["\'])(.*?)\3\)/',
            '<?php echo ZenTemplate::sectionIs(\2, \4); ?>',
            $content
        );

        return $content;
    }

    public function render(string $template, array $data = []): string
    {
        $compiled = $this->compile($template, $data);
        
        extract($data);

        ob_start();
        eval('?>' . $compiled);
        return ob_get_clean();
    }

    protected function resolvePath(string $template, string $extension = 'zen.php'): string
    {
        $path = str_replace('.', '/', $template);
        return $this->viewsPath . '/' . $path . '.' . $extension;
    }

    public function getViewsPath(): string
    {
        return $this->viewsPath;
    }

    public function getCachePath(): string
    {
        return $this->cachePath;
    }

    public function setExtends(?string $template): void
    {
        $this->extends = $template;
    }

    public function getExtends(): ?string
    {
        return $this->extends;
    }

    public function startSection(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    public function endSection(): void
    {
        if ($this->currentSection !== null) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = null;
        }
    }

    public function getSection(string $name): ?string
    {
        return $this->sections[$name] ?? null;
    }

    public function hasSection(string $name): bool
    {
        return isset($this->sections[$name]);
    }

    public function yield(string $name, string $default = ''): string
    {
        return $this->getSection($name) ?? $default;
    }

    public function section(string $name): void
    {
        $this->startSection($name);
    }

    public function sectionIs(string $name, string $value): string
    {
        $section = $this->getSection($name);
        return ($section === $value) ? $section : '';
    }

    public function parent(): string
    {
        return '@parent';
    }

    public function include(string $template, array $data = []): string
    {
        $compiler = new self();
        return $compiler->render($template, array_merge($this->data, $data));
    }

    public function includeIf(string $template, array $data = []): string
    {
        $path = $this->resolvePath($template);
        if (file_exists($path)) {
            return $this->include($template, $data);
        }
        return '';
    }

    public function includeWhen(bool $condition, string $template, array $data = []): string
    {
        if ($condition) {
            return $this->include($template, $data);
        }
        return '';
    }

    public function includeFirst(array $templates, array $data = []): string
    {
        foreach ($templates as $template) {
            $path = $this->resolvePath($template);
            if (file_exists($path)) {
                return $this->include($template, $data);
            }
        }
        return '';
    }

    public function component(string $name, array $data = []): void
    {
        $this->currentComponent = $name;
        $this->componentData = $data;
        ob_start();
    }

    public function endComponent(): string
    {
        $slot = ob_get_clean();
        $name = $this->currentComponent;
        
        $componentClass = $this->findComponentClass($name);
        
        if ($componentClass && class_exists($componentClass)) {
            $component = new $componentClass($this->componentData);
            
            if (method_exists($component, 'render')) {
                return $component->render($slot);
            }
        }

        $this->currentComponent = null;
        $this->componentData = [];
        
        return $slot;
    }

    protected function findComponentClass(string $name): ?string
    {
        $componentDirs = [
            'App\\UI\\Components\\',
            'App\\View\\Components\\',
            'Zen\\UI\\Components\\',
        ];

        $name = str_replace('.', '\\', $name);
        
        foreach ($componentDirs as $dir) {
            $class = $dir . $name;
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }

    public function slot(string $name): void
    {
        ob_start();
    }

    public function endSlot(): string
    {
        return ob_get_clean();
    }

    public function stack(string $name): string
    {
        return $this->stacks[$name] ?? '';
    }

    public function push(string $name): void
    {
        $this->currentStack = $name;
        ob_start();
    }

    public function endPush(): void
    {
        if ($this->currentStack !== null) {
            $content = ob_get_clean();
            if (!isset($this->stacks[$this->currentStack])) {
                $this->stacks[$this->currentStack] = '';
            }
            $this->stacks[$this->currentStack] .= $content;
            $this->currentStack = null;
        }
    }

    public function prepend(string $name): void
    {
        $this->currentStack = $name;
        ob_start();
    }

    public function endPrepend(): void
    {
        if ($this->currentStack !== null) {
            $content = ob_get_clean();
            if (!isset($this->stacks[$this->currentStack])) {
                $this->stacks[$this->currentStack] = '';
            }
            $this->stacks[$this->currentStack] = $content . $this->stacks[$this->currentStack];
            $this->currentStack = null;
        }
    }
}
