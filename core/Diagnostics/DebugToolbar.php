<?php

declare(strict_types=1);

namespace Zen\Diagnostics;

class DebugToolbar
{
    protected static float $startTime = 0;
    protected static int $startMemory = 0;
    protected static array $logs = [];
    protected static int $queryCount = 0;
    protected static string $route = '';
    
    public static function start(): void
    {
        self::$startTime = microtime(true);
        self::$startMemory = memory_get_usage();
    }
    
    public static function stop(): void
    {
        self::$startTime = 0;
        self::$startMemory = 0;
    }
    
    public static function setRoute(string $route): void
    {
        self::$route = $route;
    }
    
    public static function recordQuery(string $query): void
    {
        self::$queryCount++;
    }
    
    public static function log(string $message): void
    {
        self::$logs[] = [
            'message' => $message,
            'time' => round((microtime(true) - self::$startTime) * 1000, 2),
        ];
    }
    
    public static function isEnabled(): bool
    {
        return config('app.env', 'production') !== 'production';
    }
    
    public static function render(): string
    {
        if (!self::isEnabled()) {
            return '';
        }
        
        $time = round((microtime(true) - self::$startTime) * 1000, 2);
        $memory = round((memory_get_usage() - self::$startMemory) / 1024, 2);
        $route = htmlspecialchars(self::$route, ENT_QUOTES, 'UTF-8');
        
        $logsJson = json_encode(self::$logs);
        
        return '<div id="debug-toolbar" style="position:fixed;bottom:0;left:0;right:0;background:#0d1117;color:#c9d1d9;padding:12px 20px;font-family:monospace;font-size:13px;z-index:99999;display:flex;justify-content:space-between;align-items:center;border-top:3px solid #58a6ff;box-shadow:0 -4px 20px rgba(0,0,0,0.5);">
            <div style="display:flex;gap:25px;">
                <span>⏱ <span id="dt-time">' . $time . '</span>ms</span>
                <span>💾 <span id="dt-memory">' . $memory . '</span>KB</span>
                <span>📊 <span id="dt-queries">' . self::$queryCount . '</span> queries</span>
                <span>🛣️ ' . $route . '</span>
            </div>
            <div>
                <button onclick="document.getElementById(\'debug-details\').style.display=document.getElementById(\'debug-details\').style.display===\'none\'?\'block\':\'none\'" style="background:#30363d;border:none;color:#c9d1d9;padding:8px 15px;border-radius:6px;cursor:pointer;font-size:12px;">📋 Logs</button>
            </div>
        </div>
        <div id="debug-details" style="display:none;position:fixed;bottom:50px;left:0;right:0;background:#161b22;padding:20px;max-height:300px;overflow:auto;border-top:2px solid #30363d;">
            <pre style="color:#c9d1d9;font-size:12px;margin:0;">' . self::formatLogs() . '</pre>
        </div>
        <script>setInterval(function(){var t=Date.now()-' . (self::$startTime * 1000) . ';document.getElementById("dt-time").textContent=t.toFixed(2);},100);</script>';
    }
    
    protected static function formatLogs(): string
    {
        if (empty(self::$logs)) {
            return 'No logs recorded.';
        }
        
        $output = '';
        foreach (self::$logs as $log) {
            $output .= '[' . $log['time'] . 'ms] ' . htmlspecialchars($log['message'], ENT_QUOTES, 'UTF-8') . "\n";
        }
        
        return $output;
    }
    
    public static function inject(string $content): string
    {
        if (!self::isEnabled()) {
            return $content;
        }
        
        if (strpos($content, '<body') !== false) {
            return preg_replace('/<body([^>]*)>/', '$0' . self::render(), $content, 1);
        }
        
        return $content . self::render();
    }
}