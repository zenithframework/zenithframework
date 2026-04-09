<?php

declare(strict_types=1);

namespace Zenith\Http;

class Request
{
    public string $method;
    public string $uri;
    public array $query;
    public array $body;
    public array $headers;
    public ?string $ip;
    public ?string $userAgent;
    public array $server;
    public array $files;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->query = $_GET;
        $this->body = $this->parseBody();
        $this->headers = $this->parseHeaders();
        $this->ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $this->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $this->server = $_SERVER;
        $this->files = $_FILES;
    }

    public static function capture(): self
    {
        return new self();
    }

    public static function create(string $method, string $uri, array $query = [], array $body = [], array $headers = []): self
    {
        $request = new self();
        $request->method = $method;
        $request->uri = $uri;
        $request->query = $query;
        $request->body = $body;
        $request->headers = $headers;
        $request->ip = null;
        $request->userAgent = null;
        $request->server = $_SERVER;
        $request->files = [];
        
        return $request;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function header(string $key, ?string $default = null): ?string
    {
        $key = str_replace('-', '_', strtoupper($key));
        return $this->headers[$key] ?? $default;
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method;
    }

    public function isAjax(): bool
    {
        return $this->header('X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    public function isXmlHttpRequest(): bool
    {
        return $this->isAjax();
    }

    public function expectsJson(): bool
    {
        return str_contains($this->header('ACCEPT') ?? '', 'application/json');
    }

    public function wantsJson(): bool
    {
        return $this->expectsJson();
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('AUTHORIZATION');

        if ($header === null || !str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return substr($header, 7);
    }

    public function token(): ?string
    {
        return $this->bearerToken() ?? $this->input('_token');
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    public function has(string $key): bool
    {
        return isset($this->body[$key]) || isset($this->query[$key]);
    }

    public function hasAny(array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->has($key)) {
                return true;
            }
        }
        return false;
    }

    public function missing(string $key): bool
    {
        return !$this->has($key);
    }

    public function filled(string $key): bool
    {
        return $this->has($key) && !empty($this->input($key));
    }

    public function empty(array $keys): array
    {
        return array_filter($this->only($keys), fn($v) => empty($v));
    }

    public function keys(): array
    {
        return array_keys($this->all());
    }

    public function file(string $key): ?UploadedFile
    {
        return isset($this->files[$key]) ? new UploadedFile($this->files[$key]) : null;
    }

    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] !== UPLOAD_ERR_NO_FILE;
    }

    public function files(): array
    {
        $uploaded = [];
        foreach ($this->files as $key => $file) {
            if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploaded[$key] = new UploadedFile($file);
            }
        }
        return $uploaded;
    }

    public function ip(string $type = 'ipv4'): ?string
    {
        if ($this->ip === null) {
            return null;
        }

        if ($type === 'ipv6' && filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->ip;
        }

        if ($type === 'ipv4' && filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->ip;
        }

        return $this->ip;
    }

    public function userAgent(): ?string
    {
        return $this->userAgent;
    }

    public function root(): string
    {
        $scheme = $_SERVER['HTTPS'] ?? 'off' === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }

    public function url(): string
    {
        return $this->root() . $this->uri;
    }

    public function fullUrl(): string
    {
        return $this->url() . (count($this->query) > 0 ? '?' . http_build_query($this->query) : '');
    }

    public function path(): string
    {
        return trim(parse_url($this->uri, PHP_URL_PATH), '/');
    }

    public function segment(int $index, ?string $default = null): ?string
    {
        $segments = explode('/', trim($this->uri, '/'));
        return $segments[$index - 1] ?? $default;
    }

    public function segments(): array
    {
        return array_filter(explode('/', trim($this->uri, '/')));
    }

    public function is(string $pattern): bool
    {
        $path = $this->path();
        $pattern = trim($pattern, '/');
        
        if (str_contains($pattern, '*')) {
            $regex = '#^' . str_replace(['/', '*'], ['\\/', '.*'], $pattern) . '$#';
            return (bool) preg_match($regex, $path);
        }
        
        return $path === $pattern;
    }

    public function routeIs(string ...$patterns): bool
    {
        $routeName = $this->header('X-ROUTE-NAME') ?? '';
        
        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                $regex = '#^' . str_replace('*', '.*', $pattern) . '$#';
                if (preg_match($regex, $routeName)) {
                    return true;
                }
            } elseif ($routeName === $pattern) {
                return true;
            }
        }
        
        return false;
    }

    public function validate(array $rules): array
    {
        $validator = new \Zenith\Validation\Validator($this->all(), $rules);
        
        if ($validator->fails()) {
            throw new \InvalidArgumentException(json_encode($validator->errors()));
        }
        
        return $validator->validated();
    }

    protected function parseBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $input = file_get_contents('php://input');

        if (str_contains($contentType, 'application/json')) {
            return json_decode($input, true) ?? [];
        }

        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($input, $parsed);
            return $parsed ?? [];
        }

        return $_POST;
    }

    protected function parseHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['CONTENT_TYPE'] = $_SERVER['CONTENT_TYPE'];
        }

        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['CONTENT_LENGTH'] = $_SERVER['CONTENT_LENGTH'];
        }

        return $headers;
    }
}

class UploadedFile
{
    public string $name;
    public string $type;
    public int $error;
    public int $size;
    public string $tmpName;

    public function __construct(array $file)
    {
        $this->name = $file['name'] ?? '';
        $this->type = $file['type'] ?? '';
        $this->error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        $this->size = $file['size'] ?? 0;
        $this->tmpName = $file['tmp_name'] ?? '';
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && is_uploaded_file($this->tmpName);
    }

    public function getClientOriginalName(): string
    {
        return $this->name;
    }

    public function getClientOriginalExtension(): string
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }

    public function getMimeType(): string
    {
        if ($this->isValid()) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $this->tmpName);
            finfo_close($finfo);
            return $mime ?? $this->type;
        }
        return $this->type;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getErrorMessage(): string
    {
        return match ($this->error) {
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
            default => 'Unknown error',
        };
    }

    public function move(string $destination): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        $dir = dirname($destination);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return move_uploaded_file($this->tmpName, $destination);
    }

    public function store(string $path, ?string $name = null): ?string
    {
        $name = $name ?? $this->getClientOriginalName();
        $fullPath = dirname(__DIR__, 2) . '/storage/' . trim($path, '/') . '/' . $name;
        
        if ($this->move($fullPath)) {
            return $path . '/' . $name;
        }
        
        return null;
    }

    public function storeAs(string $path, string $name): ?string
    {
        return $this->store($path, $name);
    }
}
