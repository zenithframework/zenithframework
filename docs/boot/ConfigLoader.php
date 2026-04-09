<?php

declare(strict_types=1);

namespace Zenith\Boot;

use Zenith\Container;

class ConfigLoader
{
    protected array $config = [];
    protected string $cachePath;
    protected bool $loaded = false;

    public function __construct()
    {
        $this->cachePath = dirname(__DIR__, 2) . '/boot/cache/config.php';
    }

    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        // Check for config cache first
        $cacheDir = (defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__)) . '/boot/cache/config.php';
        $this->cachePath = $cacheDir;
        
        if (file_exists($this->cachePath)) {
            $cached = require $this->cachePath;
            if (is_array($cached)) {
                $this->config = $cached;
                $this->loaded = true;
                return;
            }
        }

        // Use PROJECT_ROOT if defined, otherwise fallback to parent directory
        $configDir = (defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__)) . '/config/';

        if (!is_dir($configDir)) {
            return;
        }

        $files = glob($configDir . '*.php');

        foreach ($files as $file) {
            $key = pathinfo($file, PATHINFO_FILENAME);
            $this->config[$key] = require $file;
        }

        $this->saveCache();
        $this->loaded = true;
    }

    protected function saveCache(): void
    {
        $cacheDir = dirname($this->cachePath);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $data = "<?php\n\nreturn " . var_export($this->config, true) . ";\n";
        file_put_contents($this->cachePath, $data);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->load();
        
        $segments = explode('.', $key);
        $data = $this->config;

        foreach ($segments as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return $default;
            }
            $data = $data[$segment];
        }

        return $data;
    }

    public function set(string $key, mixed $value): void
    {
        $this->load();
        
        $segments = explode('.', $key);
        $data = &$this->config;

        while (count($segments) > 1) {
            $segment = array_shift($segments);
            if (!isset($data[$segment]) || !is_array($data[$segment])) {
                $data[$segment] = [];
            }
            $data = &$data[$segment];
        }

        $data[array_shift($segments)] = $value;
        $this->saveCache();
    }

    public function all(): array
    {
        $this->load();
        return $this->config;
    }
}
