<?php

declare(strict_types=1);

namespace Zen\Diagnostics;

class ErrorHandler
{
    public static function register(): void
    {
        error_reporting(E_ALL);

        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleException(\Throwable $e): void
    {
        $isCli = php_sapi_name() === 'cli';

        if (!$isCli) {
            $status = match (true) {
                $e instanceof \RuntimeException && $e->getCode() >= 400 && $e->getCode() < 600 => $e->getCode(),
                default => 500,
            };
            http_response_code($status);
        }

        if ($isCli) {
            fwrite(STDERR, "\033[31mError: " . $e->getMessage() . "\033[0m\n");
            fwrite(STDERR, "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n");
            fwrite(STDERR, $e->getTraceAsString() . "\n");
        } else {
            $acceptsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

            if ($acceptsJson) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ], JSON_PRETTY_PRINT);
            } else {
                header('Content-Type: text/html');
                echo '<!DOCTYPE html><html><head><title>Error</title>';
                echo '<style>body{font-family:monospace;margin:40px;background:#1a1a2e;color:#eee;}pre{background:#16213e;padding:20px;border-radius:8px;overflow-x:auto;}</style>';
                echo '</head><body>';
                echo '<h1>' . htmlspecialchars($e->getMessage()) . '</h1>';
                echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
                echo '</body></html>';
            }
        }

        exit(1);
    }

    public static function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        throw new \ErrorException($message, 0, $level, $file, $line);
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $isCli = php_sapi_name() === 'cli';
            
            if ($isCli) {
                fwrite(STDERR, "\033[31mFatal Error: " . $error['message'] . "\033[0m\n");
                fwrite(STDERR, "File: " . $error['file'] . " Line: " . $error['line'] . "\n");
            } else {
                self::handleException(new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
            }
        }
    }
}
