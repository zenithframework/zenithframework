<?php

declare(strict_types=1);

namespace Zen\Log;

class Logger
{
    protected string $path;
    protected string $level = 'info';

    public function __construct()
    {
        $this->path = dirname(__DIR__, 2) . '/logs';
        
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        $logMessage = "[{$timestamp}] {$level}: {$message}{$contextStr}\n";

        $filename = $this->path . '/' . date('Y-m-d') . '.log';
        file_put_contents($filename, $logMessage, FILE_APPEND);
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
        
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }
}

function logger(string $message = '', array $context = [])
{
    static $logger = null;
    
    if ($logger === null) {
        $logger = new Logger();
    }
    
    if ($message === '') {
        return $logger;
    }
    
    $logger->info($message, $context);
}
