<?php

declare(strict_types=1);

use Zen\Container;
use Zen\Boot\ConfigLoader;
use Zen\Boot\Ignition;
use Zen\Http\Request;
use Zen\Http\Response;
use Zen\Http\Redirect;
use Zen\Routing\Router;

if (!function_exists('app')) {
    function app(?string $abstract = null): mixed
    {
        static $container = null;
        static $initialized = false;

        if ($container === null && !$initialized) {
            $initialized = true;
            $container = Ignition::fire();
        }

        if ($abstract === null) {
            return $container;
        }

        return $container->make($abstract);
    }
}

if (!function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        $loader = app(ConfigLoader::class);

        if ($key === null) {
            return $loader;
        }

        return $loader->get($key, $default);
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = []): string
    {
        $templatePath = dirname(__DIR__, 2) . '/views';
        $path = str_replace('.', '/', $template);
        
        $zenPath = $templatePath . '/' . $path . '.zen.php';
        $phpPath = $templatePath . '/' . $path . '.php';
        
        if (file_exists($zenPath)) {
            return render_zen_template($zenPath, $data);
        }
        
        if (file_exists($phpPath)) {
            return render_php_template($phpPath, $data);
        }
        
        throw new \RuntimeException("View [{$template}] not found.");
    }
}

if (!function_exists('render_php_template')) {
    function render_php_template(string $file, array $data = []): string
    {
        extract($data);
        ob_start();
        require $file;
        return ob_get_clean();
    }
}

if (!function_exists('render_zen_template')) {
    function render_zen_template(string $file, array $data = []): string
    {
        // Get cache directory
        $cacheDir = dirname(__DIR__, 2) . '/storage/framework/views';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        
        // Generate cache filename based on template path and modification time
        $cacheKey = md5($file . filemtime($file));
        $cacheFile = $cacheDir . '/' . $cacheKey . '.php';
        
        $content = file_get_contents($file);
        
        // Check if file has any Zen directives that need compilation
        $hasDirectives = preg_match('/@(if|else|foreach|for|while|extends|section|yield|include|component|csrf|method|auth|guest|php|{{|{!!)/', $content);
        
        if ($hasDirectives) {
            // Check for cached compiled version (only in production)
            $env = getenv('APP_ENV') ?: 'development';
            $useCache = $env === 'production' && file_exists($cacheFile);
            
            if ($useCache) {
                $compiled = file_get_contents($cacheFile);
            } else {
                // Use full template compilation
                $compiled = \Zen\UI\TemplateDirectives::compile($content);
                
                // Cache the compiled template in production
                if ($env === 'production') {
                    @file_put_contents($cacheFile, $compiled);
                }
            }
            
            // Check if this template extends another layout
            if (preg_match("/@extends\s*\(['\"]([^'\"]+)['\"]\)/", $content, $matches)) {
                $layoutPath = dirname(__DIR__, 2) . '/views/layouts/' . str_replace('.', '/', $matches[1]) . '.zen.php';
                
                if (file_exists($layoutPath)) {
                    // Render layout with sections
                    return render_zen_with_layout($file, $layoutPath, $data);
                }
            }
            
            // Simple compilation without extends
            extract($data);
            
            ob_start();
            eval('?>' . $compiled);
            return ob_get_clean();
        }
        
        // No directives - just render as PHP
        return render_php_template($file, $data);
    }
    
    function render_zen_with_layout(string $templateFile, string $layoutFile, array $data): string
    {
        // Get layout content
        $layoutContent = file_get_contents($layoutFile);
        $layoutCompiled = \Zen\UI\TemplateDirectives::compile($layoutContent);
        
        // Get template content and extract sections
        $templateContent = file_get_contents($templateFile);
        
        $sectionData = [];
        
        // Extract inline sections: @section('title', 'value')
        preg_match_all("/@section\s*\(['\"]([^'\"]+)['\"]\s*,\s*(.+?)\)\s*/", $templateContent, $inlineSections, PREG_SET_ORDER);
        foreach ($inlineSections as $section) {
            $sectionName = $section[1];
            $sectionValue = trim($section[2]);
            // Compile the value (it might contain {{ }} or other directives)
            $sectionValue = \Zen\UI\TemplateDirectives::compile($sectionValue);
            $sectionData[$sectionName] = $sectionValue;
        }
        
        // Extract block sections: @section('name') ... @endsection
        preg_match_all("/@section\s*\(['\"]([^'\"]+)['\"]\s*\)([\s\S]*?)@endsection/", $templateContent, $blockSections, PREG_SET_ORDER);
        foreach ($blockSections as $section) {
            $sectionName = $section[1];
            $sectionContent = trim($section[2]);
            // Compile the section content
            $sectionContent = \Zen\UI\TemplateDirectives::compile($sectionContent);
            $sectionData[$sectionName] = $sectionContent;
        }
        
        // Merge template data with section data
        $fullData = array_merge($data, $sectionData);
        
        extract($fullData);
        
        ob_start();
        eval('?>' . $layoutCompiled);
        return ob_get_clean();
    }
}

if (!function_exists('response')) {
    function response(mixed $content = '', int $status = 200, array $headers = []): Response
    {
        return new Response($content, $status, $headers);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): Response
    {
        return new Response('', $status, ['Location' => $url]);
    }
}

if (!function_exists('json')) {
    function json(mixed $data, int $status = 200): Response
    {
        return new Response(
            json_encode(['status' => 'success', 'data' => $data], JSON_THROW_ON_ERROR),
            $status,
            ['Content-Type' => 'application/json']
        );
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): Response
    {
        return new Response('', $status, ['Location' => $url]);
    }
}

if (!function_exists('request')) {
    function request(): Request
    {
        return app(Request::class);
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = []): string
    {
        $router = app(Router::class);
        return $router->url($name, $params);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $token = csrf_token();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_token'];
    }
}

if (!function_exists('session_token')) {
    function session_token(): string
    {
        return csrf_token();
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $baseUrl = rtrim(config('app.url', ''), '/');
        $path = ltrim($path, '/');
        return $baseUrl . '/' . $path;
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        if ($value === false) {
            $envFile = dirname(__DIR__, 2) . '/.env';

            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                foreach ($lines as $line) {
                    if (str_starts_with(trim($line), '#')) {
                        continue;
                    }

                    [$envKey, $envValue] = array_pad(explode('=', $line, 2), 2, '');
                    $envValue = trim($envValue, '"\' ');

                    if ($envKey === $key) {
                        $value = $envValue;
                        break;
                    }
                }
            }

            if ($value === false) {
                return $default;
            }
        }

        return match (strtolower($value)) {
            'true' => true,
            'false' => false,
            'null', '' => null,
            default => $value,
        };
    }
}

if (!function_exists('abort')) {
    function abort(int $code, string $message = ''): never
    {
        throw new \RuntimeException($message ?: "HTTP {$code} Error", $code);
    }
}

if (!function_exists('dd')) {
    function dd(mixed $value): never
    {
        echo '<pre>';
        var_dump($value);
        echo '</pre>';
        exit(1);
    }
}

if (!function_exists('session')) {
    function session(?string $key = null, mixed $default = null): mixed
    {
        $session = new \Zen\Session\Session();
        
        if ($key === null) {
            return $session;
        }
        
        return $session->get($key, $default);
    }
}

if (!function_exists('auth')) {
    function auth(?string $guard = null): \Zen\Auth\Auth
    {
        return new \Zen\Auth\Auth();
    }
}
