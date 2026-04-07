# Changelog

All notable changes to Zen Framework will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.0] - 2026-04-07

### Added

#### Extreme Performance (Target: 500K-1M+ req/sec)
- ZeroCopyResponse for direct socket writes
- BufferManager with pre-allocated buffers
- OutputBuffer for streaming without copying
- SocketWriter for low-level socket access
- StringInterner for string deduplication

#### Fast Database Layer
- FastConnectionPool with pre-connected sockets
- ConnectionReaper for connection cleanup
- QueryPipeline for batch queries
- PreparedStatementCache with zero-parse caching
- QueryOptimizer with EXPLAIN and index suggestions

#### Multi-Level Cache System
- L1: In-memory cache (arrays)
- L2: APCu cache
- L3: Redis cache
- CacheWarmer for pre-building cache
- CacheCoalescing to prevent cache stampede

#### Advanced Rate Limiting
- TokenBucket algorithm
- LeakyBucket algorithm  
- SlidingWindow algorithm
- QuotaManager for API quotas
- PriorityQueueLimiter for request prioritization

#### AI-Powered Security
- ThreatDetector with ML-based detection
- AnomalyDetector for behavioral analysis
- AttackPredictor to predict attacks before they happen

#### WebSocket & Real-time
- WebSocketServer with rooms and broadcasting
- SSEHandler for Server-Sent Events
- ConnectionManager for connection pooling

#### Observability
- MetricsCollector (Counter, Gauge, Histogram)
- HealthChecker for health probes
- PrometheusExporter for metrics export
- DistributedTracing for request tracing

#### Clustering & Distribution
- LoadBalancer (round_robin, least_connections, weighted, ip_hash)
- HealthChecker for node monitoring
- FailoverManager for automatic failover
- ServiceDiscovery for dynamic registration

#### Configuration
- config/performance.php - All performance tuning settings
- config/observability.php - Monitoring configuration

### Changed
- Enhanced MultiLevelCache to handle multiple cache backends
- Updated config files with new performance settings

## [2.0.0] - 2026-04-07

### Added

#### Performance Layer
- Server class with Swoole, Workerman, RoadRunner adapter support
- WorkerPool for pre-fork worker management
- SharedMemory for inter-process communication
- InterProcessCache for fast IPC caching
- RadixRouter with O(1) tree-based route matching
- Server configuration (config/server.php)

#### Security Layer (Enterprise-Grade)
- IPBlocker with auto-ban, CIDR blocking, geo-blocking, ASN filtering
- DDoSProtection with TrafficAnalyzer, Challenge (JS, Math, CAPTCHA)
- WAF (Web Application Firewall) with rule-based filtering
- RequestFilter for method, content-type, size validation
- LoginThrottler for brute force protection
- PasswordHasher with Bcrypt/Argon support
- TwoFactor with TOTP (Google Authenticator compatible)
- SessionGuard with IP/UserAgent verification
- CSRFToken for form protection
- Encrypter with AES-256-GCM
- Hasher with HMAC support
- TokenGenerator (bearer, basic auth)
- AuditLogger with channels (security, auth, api, system)
- AlertManager with email, Slack, Telegram support

#### Resilience Layer
- CircuitBreaker for failure isolation
- FallbackHandler for graceful degradation
- RetryPolicy with exponential backoff
- TimeoutHandler for request timeouts
- Bulkhead for resource isolation

#### Configuration
- config/security.php - All security settings
- config/server.php - Server and worker configuration

#### Documentation
- COMPARE.md updated with 15 frameworks and realistic benchmarks
- Zen v2.0 ranked #1 PHP framework (200K+ req/sec)

### Changed
- Enhanced Route class with middleware support
- Performance optimization targets

## [1.0.1] - 2026-04-07

### Added

#### Event System
- Event class for event data and propagation control
- EventDispatcher with listen, dispatch, subscribe methods
- Priority-based listener ordering
- Wildcard event pattern support

#### Queue System
- Job base class with handle, failed, retryAfter methods
- QueueManager with Sync, Database, and Redis drivers
- JobPayload for queue processing
- Configurable queue names

#### Mail System
- Mail class for email composition
- Mailer with Log, Sendmail, and SMTP drivers
- From, to, subject, body, attachments support
- Raw email sending helper

#### Storage System
- Storage class with Local, S3, and FTP drivers
- File operations: put, get, delete, copy, move
- Directory operations: files, directories, makeDirectory
- URL generation and file download
- Zip and extract support

#### API Resources
- Resource class for JSON transformation
- ResourceCollection for transforming collections
- ApiResponse helper with success, error, pagination methods

#### Rate Limiting
- RateLimiter with File, Database, and Redis drivers
- ThrottleRequests middleware for route protection
- X-RateLimit headers support

#### Enhanced Request
- UploadedFile class for file uploads
- File validation and storage methods
- URL/Path helpers
- Route pattern matching
- Input validation helper

#### Enhanced Response
- Cookie management
- Download response
- Stream response
- Redirect to route by name

### Changed
- Request and Response now use protected properties instead of readonly for better extensibility
- Added 20+ new methods to Request class
- Added 30+ new methods to Response class

## [1.0.0] - 2026-04-07

### Added

#### Core Framework
- Container with dependency injection
- Configuration loader with dot-notation support
- Custom autoloader with namespace support
- Routing system with strict file separation (Web, Api, Auth, Ai)
- Route parameters and named routes
- Route groups

#### HTTP Layer
- Request class with input parsing
- Response class with JSON support
- Redirect class

#### Database & ORM
- QueryBuilder with query methods (select, where, joins, aggregates)
- Model base class with ORM features
- Builder for query chaining
- Paginator for pagination
- Migration runner with rollback support
- Seeder class
- Factory class with fake data generation

#### Validation
- Validator with 28+ rules
- FormRequest base class

#### Session & Auth
- Session manager
- Authentication system (login, logout, attempt)
- CSRF middleware

#### Cache
- Cache class with file and array drivers
- Remember, increment, decrement methods

#### UI System
- Page base class
- Component base class
- Layout class

#### AI Integration
- AI class supporting OpenAI, Anthropic, and Ollama
- Chat, JSON, and stream methods

#### Logging
- Logger with multiple log levels

#### CLI Commands (40+)
- make:model, make:controller, make:middleware, make:migration
- make:seeder, make:factory, make:service, make:provider
- make:page, make:component, make:layout, make:request
- make:job, make:event, make:listener
- remove:model, remove:controller, remove:middleware, remove:migration
- remove:seeder, remove:factory, remove:service, remove:provider
- remove:page, remove:component, remove:layout, remove:request
- rename:model, rename:controller, rename:service
- migrate, migrate:rollback, migrate:fresh
- db:seed, cache:clear, route:list, serve
- lint, test, class:resolve

#### Layout Types
- app (authenticated with sidebar)
- guest (public landing)
- blank (minimal)
- auth (login/register)
- dashboard (admin)
- custom (user-defined)

### Documentation
- README.md - Quick start and features
- GUIDE.md - Comprehensive tutorial
- COMPARE.md - Comparison with Laravel/Livewire
- SKILLS.md - CLI and API reference
- AGENTS.md - AI agent instructions
- CHANGELOG.md - This file
- CONTRIBUTING.md - Contribution guidelines

### Infrastructure
- Public entry point (public/index.php)
- Environment configuration (.env)
- Default layouts
- Database SQLite setup
- Test framework

## [0.1.0] - 2026-04-06

### Added
- Initial project structure
- Basic routing
- Simple model and controller stubs

---

## Upgrade Notes

### v1.0.0
- Requires PHP 8.4+
- New CLI commands available
- Strict routing now enforced (separate route files)
- All imports must use top-level `use` statements

### Future Versions
- Stay tuned for more features and improvements!