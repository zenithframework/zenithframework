# ⚡ Zen Framework Debugger

A comprehensive, Laravel Telescope-style debugging toolkit for Zen Framework with modern UI, real-time insights, and Markdown export capabilities.

## 🎯 Features

### 1. **Debug Toolbar** 
Modern, collapsible toolbar overlay with:
- ⏱️ **Performance Metrics**: Execution time, memory usage, peak memory
- 📊 **Query Profiler**: Track all database queries with timing and backtrace
- 📝 **Log Viewer**: Real-time application logs with severity filtering
- ⚡ **Event Timeline**: Application lifecycle events
- 💾 **Cache Tracker**: Monitor cache hits/misses
- 🔐 **Session Inspector**: View session data
- 🌐 **Request/Response Analyzer**: HTTP request and response details

### 2. **Enhanced Error Page**
Beautiful, informative error pages with:
- 📋 **Markdown Export**: Copy error reports as formatted Markdown
- 🔍 **Stack Trace Viewer**: Interactive stack trace with file/line highlighting
- 💻 **Code Context**: Shows source code around the error (10 lines before/after)
- 🌐 **Request Information**: Complete HTTP request details
- ⚙️ **Server Environment**: Server configuration and environment variables
- 📄 **Copy Details**: One-click copy of error details to clipboard

### 3. **Helper Functions**

#### `dump(...$values)`
Dump variables without stopping execution (like Laravel's `dump()`):
```php
dump($user);
dump($query, $results, $bindings);
```

Features:
- Modern dark-themed UI with syntax highlighting
- Multiple variable support
- Type information display
- Non-terminating (continues execution)

#### `dd($value)`
Dump and die (existing, enhanced):
```php
dd($data);
```

#### `debug_log($message, $level, $context)`
Log messages to both debug panel and log files:
```php
debug_log('User logged in', 'info', ['user_id' => 123]);
debug_log('Payment failed', 'error', ['order_id' => 456]);
debug_log('Cache miss for key: user_123', 'debug');
```

### 4. **CLI Debug Commands**

```bash
# Show debugger information and status
php zen debug:info

# View recent log entries
php zen debug:log

# Clear debug cache and logs
php zen debug:clear
```

### 5. **Markdown Export**

Export complete debug reports as formatted Markdown:
- Performance metrics tables
- SQL queries with syntax highlighting
- Application logs with severity icons
- Cache access statistics
- Session/request/response data
- Error reports with stack traces

**Copy from toolbar**: Click "📋 Copy MD" button
**Copy from error page**: Click "📋 Copy MD" or "📄 Copy Details"

## 🚀 Installation & Setup

### 1. Enable Debug Mode

In your `.env` file:
```env
APP_DEBUG=true
APP_ENV=development
```

### 2. Add Debug Middleware

To enable the debug toolbar, add the middleware to your route or globally in `boot/Engine.php`:

```php
use Zen\Http\Middleware\DebugMiddleware;

// For specific routes
$router->group(['middleware' => [DebugMiddleware::class]], function($router) {
    $router->get('/dashboard', 'DashboardController@index');
});

// Or globally (in boot/Engine.php)
$app->bind(DebugMiddleware::class);
```

### 3. Query Tracking (Automatic)

Database query tracking is automatically enabled when debug mode is on. All queries executed through `QueryBuilder` are tracked.

## 📊 Usage Examples

### Debug Toolbar Panels

#### Queries Panel
- View all SQL queries with execution time
- Color-coded performance (🟢 fast, 🟡 moderate, 🔴 slow)
- Search queries by keyword
- View query backtrace

#### Logs Panel
- Real-time application logs
- Filter by log level (info, warning, error, debug)
- Timestamps and execution context
- Search functionality

#### Cache Panel
- Track cache hits (✅) and misses (❌)
- Monitor cache key access patterns
- Hit/miss ratio statistics

#### Session Panel
- View all session data in tabular format
- Inspect user authentication state
- Debug session variables

#### Request/Response Panels
- HTTP method, URI, IP address
- Request headers and body
- Response status code and headers
- Content length and timing

### Using dump()

```php
// Debug a single variable
dump($user);

// Debug multiple variables
dump($query, $bindings, $results);

// Debug in a loop
foreach ($users as $user) {
    dump($user->email); // Continue execution
}

// Debug API responses
$response = Http::get('https://api.example.com/data');
dump($response->json());
```

### Using debug_log()

```php
// Informational logging
debug_log('User visited homepage', 'info');

// Warning logging
debug_log('Deprecated method called', 'warning');

// Error logging with context
debug_log('Payment processing failed', 'error', [
    'order_id' => 123,
    'amount' => 99.99,
    'gateway' => 'stripe'
]);

// Debug logging
debug_log('Cache key generated: user_123_profile', 'debug');
```

### Markdown Export Example

When you click "📋 Copy MD", you get:

```markdown
# 🔍 Zen Framework Debug Report

**Generated:** 2026-04-09 15:30:45
**PHP Version:** 8.5.3
**Environment:** development

## ⚡ Performance Metrics

| Metric | Value |
|--------|-------|
| **Execution Time** | 45.23 ms |
| **Memory Usage** | 2048.50 KB |
| **Peak Memory** | 4.25 MB |
| **Total Queries** | 12 |
| **Query Time** | 8.75 ms |
| **Cache Hits** | 5 |
| **Cache Misses** | 2 |

## 📊 Database Queries (12)

### Query #1 🟢 0.45 ms

```sql
SELECT * FROM users WHERE id = 1
```

...
```

## 🎨 Customization

### Disable Debug Toolbar

```php
// In .env
APP_DEBUG=false
// or
APP_ENV=production
```

### Clear Debug Data

```bash
# CLI command
php zen debug:clear

# Or programmatically
Zen\Diagnostics\DebugPanel::clear();
```

### Manual Debug Panel Usage

```php
use Zen\Diagnostics\DebugPanel;

// Start/stop timing
DebugPanel::start();
// ... your code ...
DebugPanel::stop();

// Record custom events
DebugPanel::recordEvent('Custom Event', ['data' => 'value']);

// Log messages
DebugPanel::log('Custom log message', 'info', ['context' => 'data']);

// Get metrics
$metrics = DebugPanel::getMetrics();
```

## 🔧 Architecture

### File Structure

```
core/Diagnostics/
├── DebugPanel.php          # Main debugger orchestrator
├── MarkdownExporter.php    # Markdown export generator
├── ErrorPage.php           # Enhanced error page renderer
├── ErrorHandler.php        # Global error handler
└── DebugToolbar.php        # Legacy toolbar (deprecated)

core/Http/Middleware/
└── DebugMiddleware.php     # Response pipeline integration

core/Console/Commands/
├── DebugInfoCommand.php    # CLI debug information
├── DebugLogCommand.php     # CLI log viewer
└── DebugClearCommand.php   # CLI cache clear

public/js/
└── error-page.js           # Error page JavaScript
```

### Integration Points

1. **QueryBuilder**: Automatic query tracking
2. **ErrorHandler**: Enhanced error pages with markdown export
3. **Response Pipeline**: Debug middleware injection
4. **Logger**: Dual logging (panel + file)

## 📈 Performance Impact

The debugger is designed to have minimal performance impact:
- Only active when `APP_DEBUG=true` and `APP_ENV!=production`
- Query tracking adds ~0.1ms per query
- Memory overhead: ~1-2MB for typical requests
- Automatically disabled in production

## 🐛 Troubleshooting

### Debug toolbar not showing
- Check `APP_DEBUG=true` in `.env`
- Verify `APP_ENV=development`
- Ensure `DebugMiddleware` is registered
- Check that response is HTML (not JSON/API)

### Queries not being tracked
- Verify you're using `QueryBuilder` (not raw PDO)
- Check that `DebugPanel::isEnabled()` returns true

### Markdown export not working
- Ensure browser supports clipboard API (HTTPS required)
- Check browser console for JavaScript errors

## 📝 License

Part of Zen Framework - Released under MIT License

---

**Built with ❤️ for developers** ⚡
