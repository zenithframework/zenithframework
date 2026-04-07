<?php

declare(strict_types=1);

namespace Zen\Diagnostics;

use Exception;
use Throwable;

class ErrorPage
{
    public static function render(Throwable $e): string
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        
        if (str_contains($accept, 'application/json')) {
            return self::jsonError($e);
        }
        
        return self::htmlError($e);
    }
    
    protected static function htmlError(Throwable $e): string
    {
        $appName = config('app.name', 'Zen');
        $appEnv = config('app.env', 'production');
        $phpVersion = PHP_VERSION;
        $memoryUsage = round(memory_get_usage() / 1024 / 1024, 2) . ' MB';
        
        $trace = self::formatTrace($e);
        $contextCode = self::getContextCode($e);
        $requestInfo = self::getRequestInfo();
        
        $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $file = htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8');
        $line = $e->getLine();
        
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - ' . $appName . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: "SF Mono", "Fira Code", Menlo, Monaco, Consolas, monospace;
            background: #0d1117; color: #c9d1d9; 
            padding: 0; min-height: 100vh;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        .error-header {
            background: linear-gradient(135deg, #f85149 0%, #da3633 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .error-title { font-size: 24px; font-weight: bold; }
        .copy-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
        }
        .error-message { background: #161b22; padding: 25px 30px; border-bottom: 1px solid #30363d; }
        .message-text { font-size: 18px; color: #f85149; font-weight: 500; word-break: break-word; }
        .tabs { background: #161b22; display: flex; border-bottom: 1px solid #30363d; }
        .tab { padding: 15px 25px; cursor: pointer; color: #8b949e; border-bottom: 2px solid transparent; }
        .tab:hover { color: #c9d1d9; }
        .tab.active { color: #58a6ff; border-bottom-color: #58a6ff; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .trace-box { background: #0d1117; padding: 20px; max-height: 500px; overflow: auto; }
        .trace-line { padding: 8px 15px; border-left: 3px solid transparent; font-size: 13px; line-height: 1.5; }
        .trace-line:hover { background: #161b22; border-left-color: #f85149; }
        .trace-file { color: #58a6ff; }
        .trace-line-num { color: #7ee787; }
        .trace-func { color: #d2a8ff; }
        .context-box { background: #0d1117; padding: 20px; overflow: auto; max-height: 400px; }
        .code-line { display: flex; padding: 3px 15px; font-size: 13px; line-height: 1.6; }
        .line-num { color: #6e7681; width: 50px; text-align: right; padding-right: 15px; }
        .line-code { color: #c9d1d9; white-space: pre; }
        .line-error { background: rgba(248, 81, 73, 0.2); color: #f85149; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1px; background: #30363d; }
        .info-item { background: #161b22; padding: 15px 20px; }
        .info-label { color: #8b949e; font-size: 12px; margin-bottom: 5px; text-transform: uppercase; }
        .info-value { color: #c9d1d9; font-size: 14px; word-break: break-all; }
        .app-info { background: #161b22; padding: 20px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .app-item { text-align: center; }
        .app-label { color: #8b949e; font-size: 12px; margin-bottom: 5px; }
        .app-value { color: #58a6ff; font-size: 16px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-header">
            <span class="error-title">💥 Server Error</span>
            <button class="copy-btn" onclick="copyReport()">📋 Copy Report</button>
        </div>
        
        <div class="error-message">
            <div class="message-text">' . $msg . '</div>
        </div>
        
        <div class="tabs">
            <div class="tab active" onclick="showTab(\'trace\')">Stack Trace</div>
            <div class="tab" onclick="showTab(\'context\')">Context Code</div>
            <div class="tab" onclick="showTab(\'request\')">Request</div>
            <div class="tab" onclick="showTab(\'app\')">Application</div>
        </div>
        
        <div id="trace" class="tab-content active">
            <div class="trace-box">' . $trace . '</div>
        </div>
        
        <div id="context" class="tab-content">
            <div class="context-box">' . $contextCode . '</div>
        </div>
        
        <div id="request" class="tab-content">
            <div class="info-grid">' . $requestInfo . '</div>
        </div>
        
        <div id="app" class="tab-content">
            <div class="app-info">
                <div class="app-item">
                    <div class="app-label">Application</div>
                    <div class="app-value">' . $appName . '</div>
                </div>
                <div class="app-item">
                    <div class="app-label">Environment</div>
                    <div class="app-value">' . $appEnv . '</div>
                </div>
                <div class="app-item">
                    <div class="app-label">PHP Version</div>
                    <div class="app-value">' . $phpVersion . '</div>
                </div>
                <div class="app-item">
                    <div class="app-label">Memory</div>
                    <div class="app-value">' . $memoryUsage . '</div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function showTab(tabName) {
        document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
        document.querySelectorAll(".tab-content").forEach(c => c.classList.remove("active"));
        event.target.classList.add("active");
        document.getElementById(tabName).classList.add("active");
    }
    function copyReport() {
        const report = "Error: ' . $msg . '\n\nFile: ' . $file . '\nLine: ' . $line . '\n\nStack Trace:\n' . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '";
        navigator.clipboard.writeText(report).then(() => { alert("Error report copied!"); });
    }
    </script>
</body>
</html>';
    }
    
    protected static function formatTrace(Throwable $e): string
    {
        $trace = $e->getTrace();
        $html = '';
        
        foreach (array_slice($trace, 0, 15) as $item) {
            $file = htmlspecialchars($item['file'] ?? 'unknown', ENT_QUOTES, 'UTF-8');
            $line = $item['line'] ?? 0;
            $class = htmlspecialchars($item['class'] ?? '', ENT_QUOTES, 'UTF-8');
            $func = htmlspecialchars($item['function'] ?? '', ENT_QUOTES, 'UTF-8');
            
            if (strpos($file, 'Zen\\') !== false || strpos($file, 'App\\') !== false || strpos($file, 'core\\') !== false) {
                $html .= '<div class="trace-line"><span class="trace-func">' . $class . $func . '</span> <span class="trace-file">' . $file . '</span>:<span class="trace-line-num">' . $line . '</span></div>';
            }
        }
        
        return $html ?: '<div class="trace-line">No trace available</div>';
    }
    
    protected static function getContextCode(Throwable $e): string
    {
        $file = $e->getFile();
        $line = $e->getLine();
        
        if (!file_exists($file)) {
            return '<div class="code-line">File not found</div>';
        }
        
        $lines = file($file);
        $start = max(0, $line - 10);
        $end = min(count($lines), $line + 10);
        
        $html = '';
        
        for ($i = $start; $i < $end; $i++) {
            $lineNum = $i + 1;
            $isErrorLine = ($i === $line - 1);
            $class = $isErrorLine ? 'code-line line-error' : 'code-line';
            $code = htmlspecialchars($lines[$i], ENT_QUOTES, 'UTF-8');
            
            $html .= '<div class="' . $class . '"><span class="line-num">' . $lineNum . '</span><span class="line-code">' . $code . '</span></div>';
        }
        
        return $html;
    }
    
    protected static function getRequestInfo(): string
    {
        $method = htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $uri = htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $ip = htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $userAgent = htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $referer = htmlspecialchars($_SERVER['HTTP_REFERER'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $timestamp = date('Y-m-d H:i:s');
        
        return '<div class="info-item"><div class="info-label">Method</div><div class="info-value">' . $method . '</div></div>
<div class="info-item"><div class="info-label">URI</div><div class="info-value">' . $uri . '</div></div>
<div class="info-item"><div class="info-label">IP Address</div><div class="info-value">' . $ip . '</div></div>
<div class="info-item"><div class="info-label">User Agent</div><div class="info-value">' . $userAgent . '</div></div>
<div class="info-item"><div class="info-label">Referer</div><div class="info-value">' . $referer . '</div></div>
<div class="info-item"><div class="info-label">Timestamp</div><div class="info-value">' . $timestamp . '</div></div>';
    }
    
    protected static function jsonError(Throwable $e): string
    {
        header('Content-Type: application/json');
        
        return json_encode([
            'error' => true,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => array_map(fn($t) => [
                'file' => $t['file'] ?? null,
                'line' => $t['line'] ?? null,
                'function' => $t['function'] ?? null,
                'class' => $t['class'] ?? null,
            ], array_slice($e->getTrace(), 0, 10)),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}