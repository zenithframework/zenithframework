<?php

declare(strict_types=1);

namespace Zen\Performance;

class ZeroCopyResponse
{
    protected static bool $enabled = true;
    protected static int $bufferSize = 65536;

    public static function send(string $content, array $headers = [], int $status = 200): void
    {
        if (!self::$enabled) {
            echo $content;
            return;
        }

        http_response_code($status);

        foreach ($headers as $name => $value) {
            header("{$name}: {$value}");
        }

        if (function_exists('fastcgi_finish_request')) {
            echo $content;
            fastcgi_finish_request();
        } else {
            echo $content;
        }
    }

    public static function sendChunked(string $content, array $headers = []): void
    {
        $headers['Transfer-Encoding'] = 'chunked';
        $headers['Content-Type'] = $headers['Content-Type'] ?? 'text/plain';

        foreach ($headers as $name => $value) {
            header("{$name}: {$value}");
        }

        $chunks = str_split($content, self::$bufferSize);
        foreach ($chunks as $chunk) {
            echo dechex(strlen($chunk)) . "\r\n{$chunk}\r\n";
        }
        echo "0\r\n\r\n";
    }

    public static function stream(callable $callback, array $headers = []): void
    {
        $headers['Transfer-Encoding'] = 'chunked';

        foreach ($headers as $name => $value) {
            header("{$name}: {$value}");
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        while ($chunk = $callback()) {
            if ($chunk === null) {
                break;
            }
            echo dechex(strlen($chunk)) . "\r\n{$chunk}\r\n";
            flush();
        }
        echo "0\r\n\r\n";
    }

    public static function disable(): void
    {
        self::$enabled = false;
    }

    public static function enable(): void
    {
        self::$enabled = true;
    }

    public static function setBufferSize(int $size): void
    {
        self::$bufferSize = $size;
    }
}

class BufferManager
{
    protected static array $pools = [];
    protected static int $defaultSize = 4096;

    public static function getBuffer(int $size = 4096): string
    {
        if (!isset(self::$pools[$size])) {
            self::$pools[$size] = [];
        }

        if (empty(self::$pools[$size])) {
            return str_repeat("\0", $size);
        }

        return array_pop(self::$pools[$size]);
    }

    public static function releaseBuffer(string &$buffer): void
    {
        $size = strlen($buffer);
        
        if (!isset(self::$pools[$size])) {
            self::$pools[$size] = [];
        }

        if (count(self::$pools[$size]) < 100) {
            self::$pools[$size][] = $buffer;
        }

        $buffer = '';
    }

    public static function getStats(): array
    {
        $total = 0;
        foreach (self::$pools as $size => $buffers) {
            $total += count($buffers);
        }

        return [
            'total_buffers' => $total,
            'pool_sizes' => array_keys(self::$pools),
            'buffer_count' => count(self::$pools),
        ];
    }
}

class OutputBuffer
{
    protected static bool $enabled = true;
    protected static int $level = 0;

    public static function start(): void
    {
        if (self::$enabled) {
            ob_start();
            self::$level++;
        }
    }

    public static function clean(): string
    {
        if (self::$enabled && ob_get_level() > 0) {
            return ob_get_clean() ?: '';
        }
        return '';
    }

    public static function flush(): void
    {
        if (self::$enabled && ob_get_level() > 0) {
            ob_flush();
            flush();
        }
    }

    public static function end(): string
    {
        if (self::$enabled && ob_get_level() > 0) {
            self::$level--;
            return ob_end_clean() ?: '';
        }
        return '';
    }

    public static function get(): string
    {
        if (ob_get_level() > 0) {
            return ob_get_contents() ?: '';
        }
        return '';
    }

    public static function disable(): void
    {
        self::$enabled = false;
    }

    public static function enable(): void
    {
        self::$enabled = true;
    }
}

class SocketWriter
{
    protected $socket = null;
    protected string $buffer = '';
    protected int $bufferSize = 8192;

    public function __construct(?string $host = null, int $port = 0)
    {
        if ($host !== null && $port > 0) {
            $this->socket = @fsockopen($host, $port, $errno, $errstr, 0.1);
        }
        $this->bufferSize = (int) (config('performance.socket_buffer_size') ?? 8192);
    }

    public function write(string $data): int
    {
        if ($this->socket === null) {
            return 0;
        }

        return fwrite($this->socket, $data);
    }

    public function writeHeader(string $data): int
    {
        $this->buffer .= $data;
        
        if (strlen($this->buffer) >= $this->bufferSize) {
            return $this->flush();
        }

        return strlen($data);
    }

    public function flush(): int
    {
        if (empty($this->buffer)) {
            return 0;
        }

        $written = 0;
        if ($this->socket !== null) {
            $written = fwrite($this->socket, $this->buffer);
        }

        $this->buffer = '';
        return $written;
    }

    public function close(): void
    {
        if ($this->socket !== null) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    public function isConnected(): bool
    {
        return $this->socket !== null;
    }

    public function __destruct()
    {
        $this->close();
    }
}

class StringInterner
{
    protected static array $strings = [];
    protected static int $maxCache = 10000;

    public static function intern(string $string): string
    {
        $hash = crc32($string);

        if (isset(self::$strings[$hash])) {
            return self::$strings[$hash];
        }

        if (count(self::$strings) >= self::$maxCache) {
            self::$strings = array_slice(self::$strings, -self::$maxCache / 2);
        }

        self::$strings[$hash] = $string;
        return $string;
    }

    public static function clear(): void
    {
        self::$strings = [];
    }

    public static function getStats(): array
    {
        return [
            'cached_strings' => count(self::$strings),
            'max_cache' => self::$maxCache,
        ];
    }
}