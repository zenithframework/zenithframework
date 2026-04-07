<?php

declare(strict_types=1);

namespace Zen\UI;

class ZenTemplate
{
    protected string $viewsPath;
    protected string $cachePath;
    protected array $data = [];
    protected static array $sections = [];
    protected static array $stacks = [];
    protected static ?string $extends = null;
    protected static array $components = [];

    public function __construct()
    {
        $this->viewsPath = dirname(__DIR__, 2) . '/views';
        $this->cachePath = dirname(__DIR__, 3) . '/boot/cache/views';
        
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    public function render(string $template, array $data = []): string
    {
        $this->data = array_merge($this->data, $data);
        
        $zenPath = $this->resolvePath($template, 'zen.php');
        $phpPath = $this->resolvePath($template, 'php');
        
        if (file_exists($zenPath)) {
            return $this->renderZen($zenPath);
        }
        
        if (file_exists($phpPath)) {
            return $this->renderFile($phpPath);
        }
        
        throw new \RuntimeException("Template [{$template}] not found.");
    }

    protected function renderZen(string $file): string
    {
        $content = file_get_contents($file);
        
        if ($this->hasDirectives($content)) {
            $compiled = $this->getCompiled($file, $content);
            return $this->evalTemplate($compiled);
        }
        
        return $this->renderFile($file);
    }

    protected function getCompiled(string $templateFile, string $content): string
    {
        $templateName = basename($templateFile, '.zen.php');
        $cacheFile = $this->cachePath . '/' . $templateName . '.php';
        
        $templateMtime = filemtime($templateFile);
        
        if (file_exists($cacheFile)) {
            $cacheMtime = filemtime($cacheFile);
            
            if ($cacheMtime >= $templateMtime) {
                return file_get_contents($cacheFile);
            }
        }
        
        $compiled = TemplateDirectives::compile($content);
        file_put_contents($cacheFile, $compiled);
        
        return $compiled;
    }

    protected function hasDirectives(string $content): bool
    {
        $directives = [
            '@if(', '@else', '@elseif(', '@endif',
            '@foreach', '@endforeach', '@for', '@endfor',
            '@while', '@endwhile', '@forelse',
            '@extends', '@section', '@yield', '@include',
            '@component', '@slot', '@push', '@stack',
            '@csrf', '@method(', '@auth', '@guest',
            '@php', '{{', '{!!',
            '<zen:', '@hasSection',
        ];

        foreach ($directives as $directive) {
            if (str_contains($content, $directive)) {
                return true;
            }
        }

        return false;
    }

    protected function evalTemplate(string $compiled): string
    {
        extract($this->data);
        
        ob_start();
        eval('?>' . $compiled);
        return ob_get_clean();
    }

    protected function resolvePath(string $template, string $extension): string
    {
        $path = str_replace('.', '/', $template);
        return $this->viewsPath . '/' . $path . '.' . $extension;
    }

    protected function renderFile(string $file): string
    {
        extract($this->data);
        
        ob_start();
        include $file;
        return ob_get_clean();
    }

    public function with(array $data): self
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    public function __call(string $method, array $args): mixed
    {
        return $this->data[$method] ?? null;
    }

    public static function extends(string $template): string
    {
        self::$extends = $template;
        return '';
    }

    public static function section(string $name): void
    {
        self::$sections[$name] = '';
        ob_start();
    }

    public static function endsection(): void
    {
        if (ob_get_level() > 0) {
            $content = ob_get_clean();
            $keys = array_keys(self::$sections);
            $lastKey = end($keys);
            self::$sections[$lastKey] = $content;
        }
    }

    public static function yield(string $name, string $default = ''): string
    {
        return self::$sections[$name] ?? $default;
    }

    public static function include(string $template): string
    {
        $tpl = new self();
        return $tpl->render($template);
    }

    public static function includeIf(string $template): string
    {
        $tpl = new self();
        $path = str_replace('.', '/', $template);
        $fullPath = $tpl->viewsPath . '/' . $path . '.php';
        
        if (file_exists($fullPath)) {
            return $tpl->render($template);
        }
        return '';
    }

    public static function includeWhen(bool $condition, string $template): string
    {
        if ($condition) {
            return self::include($template);
        }
        return '';
    }

    public static function includeFirst(array $templates): string
    {
        $tpl = new self();
        
        foreach ($templates as $template) {
            $path = str_replace('.', '/', $template);
            $fullPath = $tpl->viewsPath . '/' . $path . '.php';
            
            if (file_exists($fullPath)) {
                return $tpl->render($template);
            }
        }
        return '';
    }

    public static function component(string $name, array $data = []): string
    {
        self::$components[] = ['name' => $name, 'data' => $data];
        ob_start();
        return '';
    }

    public static function endcomponent(): string
    {
        $slot = ob_get_clean();
        $component = array_pop(self::$components);
        
        if ($component) {
            $componentClass = self::findComponentClass($component['name']);
            
            if ($componentClass && class_exists($componentClass)) {
                $instance = new $componentClass($component['data']);
                
                if (method_exists($instance, 'render')) {
                    return $instance->render($slot);
                }
            }
        }
        
        return $slot;
    }

    protected static function findComponentClass(string $name): ?string
    {
        $dirs = ['App\\UI\\Components\\', 'App\\View\\Components\\', 'Zen\\UI\\Components\\'];
        $name = str_replace('.', '\\', $name);
        
        foreach ($dirs as $dir) {
            $class = $dir . $name;
            if (class_exists($class)) {
                return $class;
            }
        }
        return null;
    }

    public static function slot(string $name): void
    {
        ob_start();
    }

    public static function endslot(): string
    {
        return ob_get_clean();
    }

    public static function stack(string $name): string
    {
        return self::$stacks[$name] ?? '';
    }

    protected static ?string $currentStack = null;

    public static function push(string $name): void
    {
        self::$currentStack = $name;
        ob_start();
    }

    public static function endpush(): void
    {
        $content = ob_get_clean();
        $name = self::$currentStack;
        if ($name && !isset(self::$stacks[$name])) {
            self::$stacks[$name] = '';
        }
        if ($name) {
            self::$stacks[$name] .= $content;
        }
        self::$currentStack = null;
    }

    public static function prepend(string $name): void
    {
        self::$currentStack = $name;
        ob_start();
    }

    public static function endprepend(): void
    {
        $content = ob_get_clean();
        $name = self::$currentStack;
        if ($name && !isset(self::$stacks[$name])) {
            self::$stacks[$name] = '';
        }
        if ($name) {
            self::$stacks[$name] = $content . self::$stacks[$name];
        }
        self::$currentStack = null;
    }

    public static function hasSection(string $name): bool
    {
        return isset(self::$sections[$name]);
    }

    public static function sectionIs(string $name, string $value): string
    {
        return (self::$sections[$name] ?? '') === $value ? self::$sections[$name] : '';
    }

    public static function parent(): string
    {
        return '@parent';
    }
}

function zen(string $template, array $data = []): string
{
    $zen = new ZenTemplate();
    return $zen->render($template, $data);
}
