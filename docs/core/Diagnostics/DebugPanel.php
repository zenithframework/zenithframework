<?php

declare(strict_types=1);

namespace Zenith\Diagnostics;

class DebugPanel
{
    protected static float $startTime = 0;
    protected static int $startMemory = 0;
    protected static array $logs = [];
    protected static array $queries = [];
    protected static array $events = [];
    protected static array $timers = [];
    protected static array $metrics = [];
    protected static string $currentRoute = '';
    protected static array $request = [];
    protected static array $response = [];
    protected static array $session = [];
    protected static array $cache = [];
    protected static int $queryCount = 0;
    protected static float $queryTime = 0;
    protected static int $cacheHits = 0;
    protected static int $cacheMisses = 0;

    public static function start(): void
    {
        self::$startTime = microtime(true);
        self::$startMemory = memory_get_usage();
        self::recordEvent('Application Bootstrapped');
    }

    public static function stop(): void
    {
        self::recordEvent('Request Completed');
        self::$startTime = 0;
        self::$startMemory = 0;
    }

    public static function setRoute(string $route): void
    {
        self::$currentRoute = $route;
    }

    public static function recordQuery(string $sql, array $bindings = [], float $time = 0): void
    {
        self::$queryCount++;
        self::$queryTime += $time;

        self::$queries[] = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => $time,
            'timestamp' => microtime(true),
            'backtrace' => self::getBacktrace(),
        ];
    }

    public static function recordCacheAccess(string $key, bool $hit): void
    {
        if ($hit) {
            self::$cacheHits++;
        } else {
            self::$cacheMisses++;
        }

        self::$cache[] = [
            'key' => $key,
            'hit' => $hit,
            'timestamp' => microtime(true),
        ];
    }

    public static function log(string $message, string $level = 'info', array $context = []): void
    {
        self::$logs[] = [
            'message' => $message,
            'level' => $level,
            'context' => $context,
            'time' => round((microtime(true) - self::$startTime) * 1000, 2),
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    public static function recordEvent(string $event, array $data = []): void
    {
        self::$events[] = [
            'event' => $event,
            'data' => $data,
            'time' => round((microtime(true) - self::$startTime) * 1000, 2),
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    public static function startTimer(string $name): void
    {
        self::$timers[$name] = [
            'start' => microtime(true),
            'start_memory' => memory_get_usage(),
        ];
    }

    public static function stopTimer(string $name): float
    {
        if (!isset(self::$timers[$name])) {
            return 0;
        }

        $duration = (microtime(true) - self::$timers[$name]['start']) * 1000;
        $memoryUsed = memory_get_usage() - self::$timers[$name]['start_memory'];

        self::$metrics[$name] = [
            'duration' => round($duration, 2),
            'memory' => round($memoryUsed / 1024, 2),
        ];

        unset(self::$timers[$name]);

        return round($duration, 2);
    }

    public static function setRequest(array $data): void
    {
        self::$request = $data;
    }

    public static function setResponse(array $data): void
    {
        self::$response = $data;
    }

    public static function setSession(array $data): void
    {
        self::$session = $data;
    }

    public static function isEnabled(): bool
    {
        return config('app.debug', false) && config('app.env', 'production') !== 'production';
    }

    public static function getMetrics(): array
    {
        $time = round((microtime(true) - self::$startTime) * 1000, 2);
        $memory = round((memory_get_usage() - self::$startMemory) / 1024, 2);
        $peakMemory = round(memory_get_peak_usage() / 1024 / 1024, 2);

        return [
            'time' => $time,
            'memory' => $memory,
            'peak_memory' => $peakMemory,
            'queries' => self::$queryCount,
            'query_time' => round(self::$queryTime, 2),
            'cache_hits' => self::$cacheHits,
            'cache_misses' => self::$cacheMisses,
            'logs' => count(self::$logs),
            'events' => count(self::$events),
            'route' => self::$currentRoute,
        ];
    }

    public static function getQueries(): array
    {
        return self::$queries;
    }

    public static function getLogs(): array
    {
        return self::$logs;
    }

    public static function getEvents(): array
    {
        return self::$events;
    }

    public static function getCache(): array
    {
        return self::$cache;
    }

    public static function getSession(): array
    {
        return self::$session;
    }

    public static function getRequest(): array
    {
        return self::$request;
    }

    public static function getResponse(): array
    {
        return self::$response;
    }

    public static function clear(): void
    {
        self::$logs = [];
        self::$queries = [];
        self::$events = [];
        self::$timers = [];
        self::$metrics = [];
        self::$request = [];
        self::$response = [];
        self::$session = [];
        self::$cache = [];
        self::$queryCount = 0;
        self::$queryTime = 0;
        self::$cacheHits = 0;
        self::$cacheMisses = 0;
    }

    protected static function getBacktrace(int $limit = 5): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit + 2);
        return array_slice($trace, 2, $limit);
    }

    public static function renderToolbar(): string
    {
        if (!self::isEnabled()) {
            return '';
        }

        $metrics = self::getMetrics();
        $queriesJson = json_encode(self::formatQueriesForToolbar());
        $logsJson = json_encode(self::$logs);
        $eventsJson = json_encode(self::$events);
        $cacheJson = json_encode(self::$cache);
        $sessionJson = json_encode(self::$session);
        $requestJson = json_encode(self::$request);
        $responseJson = json_encode(self::$response);

        return self::renderModernToolbar($metrics, $queriesJson, $logsJson, $eventsJson, $cacheJson, $sessionJson, $requestJson, $responseJson);
    }

    protected static function formatQueriesForToolbar(): array
    {
        return array_map(function ($query) {
            return [
                'sql' => $query['sql'],
                'time' => $query['time'],
                'backtrace' => $query['backtrace'],
            ];
        }, self::$queries);
    }

    protected static function renderModernToolbar(
        array $metrics,
        string $queriesJson,
        string $logsJson,
        string $eventsJson,
        string $cacheJson,
        string $sessionJson,
        string $requestJson,
        string $responseJson
    ): string {
        $nonce = bin2hex(random_bytes(16));

        return '
<!-- Zen Debug Panel -->
<div id="zen-debug-root"></div>
<script nonce="' . $nonce . '">
(function() {
    const metrics = ' . json_encode($metrics) . ';
    const queries = ' . $queriesJson . ';
    const logs = ' . $logsJson . ';
    const events = ' . $eventsJson . ';
    const cache = ' . $cacheJson . ';
    const session = ' . $sessionJson . ';
    const request = ' . $requestJson . ';
    const response = ' . $responseJson . ';

    const styles = `
        #zen-debug-root { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        #zen-debug-toolbar { position: fixed; bottom: 0; left: 0; right: 0; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #e2e8f0; padding: 0; font-size: 13px; z-index: 2147483647; border-top: 3px solid #3b82f6; box-shadow: 0 -8px 30px rgba(0,0,0,0.4); font-family: "SF Mono", "Fira Code", Menlo, Monaco, Consolas, monospace; }
        #zen-debug-header { display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; background: rgba(15, 23, 42, 0.5); border-bottom: 1px solid #334155; }
        #zen-debug-logo { font-weight: bold; font-size: 15px; color: #3b82f6; }
        #zen-debug-logo span { color: #10b981; }
        #zen-debug-controls { display: flex; gap: 10px; align-items: center; }
        .zen-debug-btn { background: #334155; border: 1px solid #475569; color: #e2e8f0; padding: 6px 14px; border-radius: 6px; cursor: pointer; font-size: 12px; transition: all 0.2s; }
        .zen-debug-btn:hover { background: #475569; border-color: #64748b; }
        .zen-debug-btn-primary { background: #3b82f6; border-color: #3b82f6; }
        .zen-debug-btn-primary:hover { background: #2563eb; }
        #zen-debug-metrics { display: flex; gap: 25px; padding: 12px 20px; border-bottom: 1px solid #334155; }
        .zen-metric { display: flex; flex-direction: column; align-items: center; }
        .zen-metric-value { font-size: 20px; font-weight: bold; color: #60a5fa; }
        .zen-metric-label { font-size: 11px; color: #94a3b8; text-transform: uppercase; margin-top: 2px; }
        #zen-debug-tabs { display: flex; padding: 0 20px; background: rgba(15, 23, 42, 0.3); }
        .zen-tab { padding: 12px 20px; cursor: pointer; color: #94a3b8; border-bottom: 3px solid transparent; transition: all 0.2s; position: relative; }
        .zen-tab:hover { color: #e2e8f0; background: rgba(59, 130, 246, 0.1); }
        .zen-tab.active { color: #3b82f6; border-bottom-color: #3b82f6; }
        .zen-tab-badge { position: absolute; top: 6px; right: 6px; background: #ef4444; color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px; font-weight: bold; }
        #zen-debug-content { max-height: 60vh; overflow: auto; background: #0f172a; }
        .zen-panel { display: none; padding: 20px; }
        .zen-panel.active { display: block; }
        .zen-panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #334155; }
        .zen-panel-title { font-size: 16px; font-weight: bold; color: #60a5fa; }
        .zen-search { background: #1e293b; border: 1px solid #334155; color: #e2e8f0; padding: 8px 12px; border-radius: 6px; width: 300px; font-size: 12px; }
        .zen-search:focus { outline: none; border-color: #3b82f6; }
        .zen-query { background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 15px; margin-bottom: 10px; transition: all 0.2s; }
        .zen-query:hover { border-color: #3b82f6; }
        .zen-query-slow { border-color: #f59e0b; background: rgba(245, 158, 11, 0.1); }
        .zen-query-sql { font-family: "SF Mono", "Fira Code", Menlo, Monaco, Consolas, monospace; color: #e2e8f0; font-size: 13px; margin-bottom: 8px; word-break: break-all; }
        .zen-query-meta { display: flex; gap: 20px; font-size: 11px; color: #94a3b8; }
        .zen-query-time { color: #10b981; }
        .zen-query-time.warning { color: #f59e0b; }
        .zen-query-time.error { color: #ef4444; }
        .zen-log { padding: 10px 15px; margin-bottom: 8px; border-radius: 6px; border-left: 4px solid #3b82f6; background: #1e293b; }
        .zen-log.info { border-left-color: #3b82f6; }
        .zen-log.warning { border-left-color: #f59e0b; background: rgba(245, 158, 11, 0.1); }
        .zen-log.error { border-left-color: #ef4444; background: rgba(239, 68, 68, 0.1); }
        .zen-log.debug { border-left-color: #8b5cf6; }
        .zen-log-message { color: #e2e8f0; font-size: 13px; margin-bottom: 5px; }
        .zen-log-meta { display: flex; gap: 15px; font-size: 11px; color: #64748b; }
        .zen-event { padding: 10px 15px; margin-bottom: 8px; background: #1e293b; border-radius: 6px; border-left: 4px solid #8b5cf6; }
        .zen-event-name { font-weight: bold; color: #8b5cf6; margin-bottom: 5px; }
        .zen-event-time { font-size: 11px; color: #64748b; }
        .zen-cache-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 15px; margin-bottom: 8px; background: #1e293b; border-radius: 6px; }
        .zen-cache-key { font-family: monospace; color: #e2e8f0; font-size: 13px; }
        .zen-cache-status { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .zen-cache-hit { background: #10b981; color: white; }
        .zen-cache-miss { background: #ef4444; color: white; }
        .zen-data-table { width: 100%; border-collapse: collapse; }
        .zen-data-table th { background: #1e293b; color: #94a3b8; padding: 12px; text-align: left; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #334155; }
        .zen-data-table td { padding: 12px; border-bottom: 1px solid #334155; color: #e2e8f0; font-size: 13px; }
        .zen-data-table tr:hover { background: rgba(59, 130, 246, 0.1); }
        .zen-code { background: #0f172a; border: 1px solid #334155; border-radius: 6px; padding: 15px; font-family: monospace; font-size: 12px; overflow: auto; max-height: 400px; color: #e2e8f0; }
        .zen-empty { text-align: center; padding: 40px; color: #64748b; font-size: 14px; }
        .zen-backtrace { margin-top: 10px; padding-top: 10px; border-top: 1px solid #334155; font-size: 11px; color: #64748b; }
        .zen-backtrace-item { padding: 3px 0; }
        @media (max-width: 768px) {
            #zen-debug-metrics { flex-wrap: wrap; gap: 15px; }
            .zen-metric { flex: 0 0 calc(33.333% - 10px); }
        }
    `;

    const styleSheet = document.createElement("style");
    styleSheet.textContent = styles;
    document.head.appendChild(styleSheet);

    const html = `
        <div id="zen-debug-toolbar">
            <div id="zen-debug-header">
                <div id="zen-debug-logo">âš¡ Zen <span>Debugger</span></div>
                <div id="zen-debug-controls">
                    <button class="zen-debug-btn" onclick="ZenDebug.copyMarkdown()" title="Copy as Markdown">ðŸ“‹ Copy MD</button>
                    <button class="zen-debug-btn" onclick="ZenDebug.exportJSON()" title="Export as JSON">ðŸ’¾ Export</button>
                    <button class="zen-debug-btn" onclick="ZenDebug.clear()" title="Clear debug data">ðŸ—‘ï¸ Clear</button>
                    <button class="zen-debug-btn" onclick="ZenDebug.toggle()" title="Minimize/Maximize">â–¼</button>
                </div>
            </div>
            <div id="zen-debug-metrics">
                <div class="zen-metric">
                    <div class="zen-metric-value">${metrics.time.toFixed(2)}</div>
                    <div class="zen-metric-label">Time (ms)</div>
                </div>
                <div class="zen-metric">
                    <div class="zen-metric-value">${metrics.memory.toFixed(2)}</div>
                    <div class="zen-metric-label">Memory (KB)</div>
                </div>
                <div class="zen-metric">
                    <div class="zen-metric-value">${metrics.peak_memory.toFixed(2)}</div>
                    <div class="zen-metric-label">Peak (MB)</div>
                </div>
                <div class="zen-metric">
                    <div class="zen-metric-value">${metrics.queries}</div>
                    <div class="zen-metric-label">Queries</div>
                </div>
                <div class="zen-metric">
                    <div class="zen-metric-value">${metrics.query_time.toFixed(2)}</div>
                    <div class="zen-metric-label">Query Time (ms)</div>
                </div>
                <div class="zen-metric">
                    <div class="zen-metric-value" style="color: #10b981">${metrics.cache_hits}</div>
                    <div class="zen-metric-label">Cache Hits</div>
                </div>
                <div class="zen-metric">
                    <div class="zen-metric-value" style="color: #ef4444">${metrics.cache_misses}</div>
                    <div class="zen-metric-label">Cache Misses</div>
                </div>
            </div>
            <div id="zen-debug-tabs">
                <div class="zen-tab active" data-tab="queries" onclick="ZenDebug.switchTab(\'queries\')">
                    ðŸ“Š Queries
                    ${queries.length > 0 ? `<span class="zen-tab-badge">${queries.length}</span>` : \'\'}
                </div>
                <div class="zen-tab" data-tab="logs" onclick="ZenDebug.switchTab(\'logs\')">
                    ðŸ“ Logs
                    ${logs.length > 0 ? `<span class="zen-tab-badge">${logs.length}</span>` : \'\'}
                </div>
                <div class="zen-tab" data-tab="events" onclick="ZenDebug.switchTab(\'events\')">
                    âš¡ Events
                    ${events.length > 0 ? `<span class="zen-tab-badge">${events.length}</span>` : \'\'}
                </div>
                <div class="zen-tab" data-tab="cache" onclick="ZenDebug.switchTab(\'cache\')">
                    ðŸ’¾ Cache
                    ${cache.length > 0 ? `<span class="zen-tab-badge">${cache.length}</span>` : \'\'}
                </div>
                <div class="zen-tab" data-tab="session" onclick="ZenDebug.switchTab(\'session\')">
                    ðŸ” Session
                </div>
                <div class="zen-tab" data-tab="request" onclick="ZenDebug.switchTab(\'request\')">
                    ðŸŒ Request
                </div>
                <div class="zen-tab" data-tab="response" onclick="ZenDebug.switchTab(\'response\')">
                    ðŸ“¤ Response
                </div>
            </div>
            <div id="zen-debug-content">
                <div id="zen-panel-queries" class="zen-panel active">
                    <div class="zen-panel-header">
                        <div class="zen-panel-title">Database Queries (${queries.length})</div>
                        <input type="text" class="zen-search" placeholder="Search queries..." oninput="ZenDebug.searchQueries(this.value)">
                    </div>
                    <div id="zen-queries-list"></div>
                </div>
                <div id="zen-panel-logs" class="zen-panel">
                    <div class="zen-panel-header">
                        <div class="zen-panel-title">Application Logs (${logs.length})</div>
                        <input type="text" class="zen-search" placeholder="Search logs..." oninput="ZenDebug.searchLogs(this.value)">
                    </div>
                    <div id="zen-logs-list"></div>
                </div>
                <div id="zen-panel-events" class="zen-panel">
                    <div class="zen-panel-header">
                        <div class="zen-panel-title">Timeline Events (${events.length})</div>
                    </div>
                    <div id="zen-events-list"></div>
                </div>
                <div id="zen-panel-cache" class="zen-panel">
                    <div class="zen-panel-header">
                        <div class="zen-panel-title">Cache Access (${cache.length})</div>
                    </div>
                    <div id="zen-cache-list"></div>
                </div>
                <div id="zen-panel-session" class="zen-panel">
                    <div class="zen-panel-header">
                        <div class="zen-panel-title">Session Data</div>
                    </div>
                    <div id="zen-session-data"></div>
                </div>
                <div id="zen-panel-request" class="zen-panel">
                    <div class="zen-panel-header">
                        <div class="zen-panel-title">Request Information</div>
                    </div>
                    <div id="zen-request-data"></div>
                </div>
                <div id="zen-panel-response" class="zen-panel">
                    <div class="zen-panel-header">
                        <div class="zen-panel-title">Response Information</div>
                    </div>
                    <div id="zen-response-data"></div>
                </div>
            </div>
        </div>
    `;

    document.getElementById(\'zen-debug-root\').innerHTML = html;

    window.ZenDebug = {
        queries: queries,
        logs: logs,
        events: events,
        cache: cache,
        session: session,
        request: request,
        response: response,
        metrics: metrics,
        isVisible: true,

        init() {
            this.renderQueries();
            this.renderLogs();
            this.renderEvents();
            this.renderCache();
            this.renderSession();
            this.renderRequest();
            this.renderResponse();
        },

        switchTab(tabName) {
            document.querySelectorAll(\'.zen-tab\').forEach(t => t.classList.remove(\'active\'));
            document.querySelectorAll(\'.zen-panel\').forEach(p => p.classList.remove(\'active\'));
            document.querySelector(`.zen-tab[data-tab="${tabName}"]`).classList.add(\'active\');
            document.getElementById(`zen-panel-${tabName}`).classList.add(\'active\');
        },

        renderQueries(filteredQueries = null) {
            const container = document.getElementById(\'zen-queries-list\');
            const items = filteredQueries || this.queries;

            if (items.length === 0) {
                container.innerHTML = \'<div class="zen-empty">No queries recorded</div>\';
                return;
            }

            container.innerHTML = items.map(q => {
                const timeClass = q.time > 100 ? \'error\' : (q.time > 50 ? \'warning\' : \'\');
                const queryClass = q.time > 100 ? \'zen-query zen-query-slow\' : \'zen-query\';
                const backtrace = q.backtrace && q.backtrace.length > 0 
                    ? \'<div class="zen-backtrace">\' + q.backtrace.map(t => `<div class="zen-backtrace-item">${t.file || \'unknown\'}:${t.line || 0}</div>`).join(\'\') + \'</div>\'
                    : \'\';
                return `
                    <div class="${queryClass}">
                        <div class="zen-query-sql">${this.escapeHtml(q.sql)}</div>
                        <div class="zen-query-meta">
                            <span class="zen-query-time ${timeClass}">â± ${q.time.toFixed(2)}ms</span>
                        </div>
                        ${backtrace}
                    </div>
                `;
            }).join(\'\');
        },

        renderLogs(filteredLogs = null) {
            const container = document.getElementById(\'zen-logs-list\');
            const items = filteredLogs || this.logs;

            if (items.length === 0) {
                container.innerHTML = \'<div class="zen-empty">No logs recorded</div>\';
                return;
            }

            container.innerHTML = items.map(l => `
                <div class="zen-log ${l.level}">
                    <div class="zen-log-message">${this.escapeHtml(l.message)}</div>
                    <div class="zen-log-meta">
                        <span>ðŸ• ${l.time}ms</span>
                        <span>ðŸ“… ${l.timestamp}</span>
                        <span style="text-transform: uppercase">${l.level}</span>
                    </div>
                </div>
            `).join(\'\');
        },

        renderEvents() {
            const container = document.getElementById(\'zen-events-list\');

            if (this.events.length === 0) {
                container.innerHTML = \'<div class="zen-empty">No events recorded</div>\';
                return;
            }

            container.innerHTML = this.events.map(e => `
                <div class="zen-event">
                    <div class="zen-event-name">âš¡ ${this.escapeHtml(e.event)}</div>
                    <div class="zen-event-time">ðŸ• ${e.time}ms | ðŸ“… ${e.timestamp}</div>
                </div>
            `).join(\'\');
        },

        renderCache() {
            const container = document.getElementById(\'zen-cache-list\');

            if (this.cache.length === 0) {
                container.innerHTML = \'<div class="zen-empty">No cache access recorded</div>\';
                return;
            }

            container.innerHTML = this.cache.map(c => `
                <div class="zen-cache-item">
                    <div class="zen-cache-key">${this.escapeHtml(c.key)}</div>
                    <span class="zen-cache-status ${c.hit ? \'zen-cache-hit\' : \'zen-cache-miss\'}">
                        ${c.hit ? \'âœ“ HIT\' : \'âœ— MISS\'}
                    </span>
                </div>
            `).join(\'\');
        },

        renderSession() {
            const container = document.getElementById(\'zen-session-data\');

            if (!this.session || Object.keys(this.session).length === 0) {
                container.innerHTML = \'<div class="zen-empty">No session data</div>\';
                return;
            }

            container.innerHTML = this.renderTable(this.session);
        },

        renderRequest() {
            const container = document.getElementById(\'zen-request-data\');

            if (!this.request || Object.keys(this.request).length === 0) {
                container.innerHTML = \'<div class="zen-empty">No request data</div>\';
                return;
            }

            container.innerHTML = this.renderTable(this.request);
        },

        renderResponse() {
            const container = document.getElementById(\'zen-response-data\');

            if (!this.response || Object.keys(this.response).length === 0) {
                container.innerHTML = \'<div class="zen-empty">No response data</div>\';
                return;
            }

            container.innerHTML = this.renderTable(this.response);
        },

        renderTable(data) {
            if (typeof data !== \'object\' || data === null) {
                return `<div class="zen-code">${this.escapeHtml(JSON.stringify(data, null, 2))}</div>`;
            }

            const rows = Object.entries(data).map(([key, value]) => {
                const displayValue = typeof value === \'object\' ? JSON.stringify(value, null, 2) : value;
                return `<tr><td><strong>${this.escapeHtml(key)}</strong></td><td>${this.escapeHtml(String(displayValue))}</td></tr>`;
            }).join(\'\');

            return `<table class="zen-data-table"><thead><tr><th>Key</th><th>Value</th></tr></thead><tbody>${rows}</tbody></table>`;
        },

        searchQueries(query) {
            if (!query) {
                this.renderQueries();
                return;
            }
            const filtered = this.queries.filter(q => q.sql.toLowerCase().includes(query.toLowerCase()));
            this.renderQueries(filtered);
        },

        searchLogs(query) {
            if (!query) {
                this.renderLogs();
                return;
            }
            const filtered = this.logs.filter(l => l.message.toLowerCase().includes(query.toLowerCase()));
            this.renderLogs(filtered);
        },

        toggle() {
            const content = document.getElementById(\'zen-debug-content\');
            const btn = event.target;
            this.isVisible = !this.isVisible;
            content.style.display = this.isVisible ? \'block\' : \'none\';
            btn.textContent = this.isVisible ? \'â–¼\' : \'â–²\';
        },

        copyMarkdown() {
            const md = ZenDebugMarkdownExporter.export(metrics, queries, logs, events, cache);
            navigator.clipboard.writeText(md).then(() => {
                alert(\'âœ“ Markdown copied to clipboard!\');
            });
        },

        exportJSON() {
            const data = { metrics, queries, logs, events, cache, session, request, response };
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: \'application/json\' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement(\'a\');
            a.href = url;
            a.download = `zen-debug-${Date.now()}.json`;
            a.click();
            URL.revokeObjectURL(url);
        },

        clear() {
            if (confirm(\'Clear all debug data?\')) {
                this.queries = [];
                this.logs = [];
                this.events = [];
                this.cache = [];
                this.renderQueries();
                this.renderLogs();
                this.renderEvents();
                this.renderCache();
            }
        },

        escapeHtml(text) {
            const div = document.createElement(\'div\');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    ZenDebug.init();
})();
</script>
<!-- End Zen Debug Panel -->
';
    }

    public static function inject(string $content): string
    {
        if (!self::isEnabled()) {
            return $content;
        }

        $toolbar = self::renderToolbar();

        if (strpos($content, '</body>') !== false) {
            return str_replace('</body>', $toolbar . '</body>', $content);
        }

        return $content . $toolbar;
    }
}
