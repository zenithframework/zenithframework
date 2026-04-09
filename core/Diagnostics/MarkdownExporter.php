<?php

declare(strict_types=1);

namespace Zenith\Diagnostics;

class MarkdownExporter
{
    public static function exportDebugReport(array $data = []): string
    {
        $metrics = DebugPanel::getMetrics();
        $queries = DebugPanel::getQueries();
        $logs = DebugPanel::getLogs();
        $events = DebugPanel::getEvents();
        $cache = DebugPanel::getCache();
        $session = DebugPanel::getSession();
        $request = DebugPanel::getRequest();
        $response = DebugPanel::getResponse();

        return self::generateMarkdown($metrics, $queries, $logs, $events, $cache, $session, $request, $response, $data);
    }

    public static function generateMarkdown(
        array $metrics,
        array $queries,
        array $logs,
        array $events,
        array $cache,
        array $session,
        array $request,
        array $response,
        array $additionalData = []
    ): string {
        $markdown = "# ðŸ” Zenith Framework Debug Report\n\n";
        $markdown .= "**Generated:** " . date('Y-m-d H:i:s') . "\n";
        $markdown .= "**PHP Version:** " . PHP_VERSION . "\n";
        $markdown .= "**Environment:** " . config('app.env', 'development') . "\n\n";

        // Performance Metrics
        $markdown .= "## âš¡ Performance Metrics\n\n";
        $markdown .= "| Metric | Value |\n";
        $markdown .= "|--------|-------|\n";
        $markdown .= "| **Execution Time** | {$metrics['time']} ms |\n";
        $markdown .= "| **Memory Usage** | {$metrics['memory']} KB |\n";
        $markdown .= "| **Peak Memory** | {$metrics['peak_memory']} MB |\n";
        $markdown .= "| **Total Queries** | {$metrics['queries']} |\n";
        $markdown .= "| **Query Time** | {$metrics['query_time']} ms |\n";
        $markdown .= "| **Cache Hits** | {$metrics['cache_hits']} |\n";
        $markdown .= "| **Cache Misses** | {$metrics['cache_misses']} |\n\n";

        // Route Information
        if (!empty($metrics['route'])) {
            $markdown .= "## ðŸ›£ï¸ Route\n\n";
            $markdown .= "**Current Route:** `{$metrics['route']}`\n\n";
        }

        // Database Queries
        $markdown .= "## ðŸ“Š Database Queries ({$metrics['queries']})\n\n";
        if (empty($queries)) {
            $markdown .= "*No queries recorded*\n\n";
        } else {
            foreach ($queries as $index => $query) {
                $timeClass = $query['time'] > 100 ? 'ðŸ”´' : ($query['time'] > 50 ? 'ðŸŸ¡' : 'ðŸŸ¢');
                $markdown .= "### Query #" . ($index + 1) . " {$timeClass} {$query['time']} ms\n\n";
                $markdown .= "```sql\n{$query['sql']}\n```\n\n";
                if (!empty($query['bindings'])) {
                    $markdown .= "**Bindings:**\n";
                    $markdown .= "```json\n" . json_encode($query['bindings'], JSON_PRETTY_PRINT) . "\n```\n\n";
                }
                if (!empty($query['backtrace'])) {
                    $markdown .= "**Backtrace:**\n";
                    foreach ($query['backtrace'] as $trace) {
                        $file = $trace['file'] ?? 'unknown';
                        $line = $trace['line'] ?? 0;
                        $markdown .= "- `{$file}:{$line}`\n";
                    }
                    $markdown .= "\n";
                }
            }
        }

        // Application Logs
        $markdown .= "## ðŸ“ Application Logs (" . count($logs) . ")\n\n";
        if (empty($logs)) {
            $markdown .= "*No logs recorded*\n\n";
        } else {
            foreach ($logs as $log) {
                $icon = match ($log['level']) {
                    'error' => 'ðŸ”´',
                    'warning' => 'ðŸŸ¡',
                    'debug' => 'ðŸŸ£',
                    default => 'ðŸ”µ',
                };
                $markdown .= "{$icon} **[{$log['level']}]** {$log['message']}\n";
                $markdown .= "- **Time:** {$log['time']} ms | **Timestamp:** {$log['timestamp']}\n";
                if (!empty($log['context'])) {
                    $markdown .= "- **Context:**\n```json\n" . json_encode($log['context'], JSON_PRETTY_PRINT) . "\n```\n";
                }
                $markdown .= "\n";
            }
        }

        // Timeline Events
        $markdown .= "## âš¡ Timeline Events (" . count($events) . ")\n\n";
        if (empty($events)) {
            $markdown .= "*No events recorded*\n\n";
        } else {
            foreach ($events as $event) {
                $markdown .= "### âš¡ {$event['event']}\n";
                $markdown .= "- **Time:** {$event['time']} ms\n";
                $markdown .= "- **Timestamp:** {$event['timestamp']}\n";
                if (!empty($event['data'])) {
                    $markdown .= "- **Data:**\n```json\n" . json_encode($event['data'], JSON_PRETTY_PRINT) . "\n```\n";
                }
                $markdown .= "\n";
            }
        }

        // Cache Access
        $markdown .= "## ðŸ’¾ Cache Access (" . count($cache) . ")\n\n";
        if (empty($cache)) {
            $markdown .= "*No cache access recorded*\n\n";
        } else {
            $hits = array_filter($cache, fn($c) => $c['hit']);
            $misses = array_filter($cache, fn($c) => !$c['hit']);
            
            $markdown .= "- **Total Access:** " . count($cache) . "\n";
            $markdown .= "- **Hits:** " . count($hits) . " âœ…\n";
            $markdown .= "- **Misses:** " . count($misses) . " âŒ\n\n";

            foreach ($cache as $item) {
                $status = $item['hit'] ? 'âœ… HIT' : 'âŒ MISS';
                $markdown .= "- **{$status}:** `{$item['key']}`\n";
            }
            $markdown .= "\n";
        }

        // Session Data
        $markdown .= "## ðŸ” Session Data\n\n";
        if (empty($session)) {
            $markdown .= "*No session data*\n\n";
        } else {
            $markdown .= "```json\n" . json_encode($session, JSON_PRETTY_PRINT) . "\n```\n\n";
        }

        // Request Information
        $markdown .= "## ðŸŒ Request Information\n\n";
        if (empty($request)) {
            $markdown .= "*No request data*\n\n";
        } else {
            $markdown .= "```json\n" . json_encode($request, JSON_PRETTY_PRINT) . "\n```\n\n";
        }

        // Response Information
        $markdown .= "## ðŸ“¤ Response Information\n\n";
        if (empty($response)) {
            $markdown .= "*No response data*\n\n";
        } else {
            $markdown .= "```json\n" . json_encode($response, JSON_PRETTY_PRINT) . "\n```\n\n";
        }

        // Additional Data
        if (!empty($additionalData)) {
            $markdown .= "## ðŸ“‹ Additional Data\n\n";
            foreach ($additionalData as $key => $value) {
                $markdown .= "### {$key}\n\n";
                if (is_array($value) || is_object($value)) {
                    $markdown .= "```json\n" . json_encode($value, JSON_PRETTY_PRINT) . "\n```\n\n";
                } else {
                    $markdown .= "{$value}\n\n";
                }
            }
        }

        // Footer
        $markdown .= "---\n\n";
        $markdown .= "*Generated by **Zenith Framework Debugger** âš¡*\n";

        return $markdown;
    }

    public static function exportErrorReport(\Throwable $exception): string
    {
        $markdown = "# ðŸ’¥ Error Report\n\n";
        $markdown .= "**Generated:** " . date('Y-m-d H:i:s') . "\n";
        $markdown .= "**PHP Version:** " . PHP_VERSION . "\n";
        $markdown .= "**Environment:** " . config('app.env', 'development') . "\n\n";

        $markdown .= "## Exception Details\n\n";
        $markdown .= "- **Type:** `" . get_class($exception) . "`\n";
        $markdown .= "- **Message:** `{$exception->getMessage()}`\n";
        $markdown .= "- **File:** `{$exception->getFile()}`\n";
        $markdown .= "- **Line:** `{$exception->getLine()}`\n\n";

        $markdown .= "## Stack Trace\n\n";
        foreach ($exception->getTrace() as $index => $trace) {
            $file = $trace['file'] ?? 'unknown';
            $line = $trace['line'] ?? 0;
            $class = $trace['class'] ?? '';
            $function = $trace['function'] ?? '';
            
            $markdown .= "### #" . ($index + 1) . "\n";
            $markdown .= "- **Location:** `{$file}:{$line}`\n";
            if ($class) {
                $markdown .= "- **Call:** `{$class}::{$function}()`\n";
            } else {
                $markdown .= "- **Call:** `{$function}()`\n";
            }
            if (!empty($trace['args'])) {
                $markdown .= "- **Arguments:**\n```json\n" . json_encode($trace['args'], JSON_PRETTY_PRINT) . "\n```\n";
            }
            $markdown .= "\n";
        }

        // Request Info
        $markdown .= "## Request Information\n\n";
        $markdown .= "- **Method:** `" . ($_SERVER['REQUEST_METHOD'] ?? 'N/A') . "`\n";
        $markdown .= "- **URI:** `" . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "`\n";
        $markdown .= "- **IP:** `" . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "`\n";
        $markdown .= "- **User Agent:** `" . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . "`\n\n";

        // Code Context
        $markdown .= "## Code Context\n\n";
        if (file_exists($exception->getFile())) {
            $lines = file($exception->getFile());
            $start = max(0, $exception->getLine() - 10);
            $end = min(count($lines), $exception->getLine() + 10);
            
            $markdown .= "```php\n";
            for ($i = $start; $i < $end; $i++) {
                $lineNum = $i + 1;
                $marker = ($i === $exception->getLine() - 1) ? ' >>> ' : '     ';
                $markdown .= "{$marker}{$lineNum}: " . rtrim($lines[$i]) . "\n";
            }
            $markdown .= "```\n\n";
        }

        $markdown .= "---\n\n";
        $markdown .= "*Generated by **Zenith Framework Debugger** âš¡*\n";

        return $markdown;
    }
}
