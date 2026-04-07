<?php

declare(strict_types=1);

namespace Zen\Observability;

class MetricsCollector
{
    protected array $metrics = [];
    protected string $prefix = 'zen';

    public function __construct()
    {
        $this->prefix = config('observability.prefix') ?? 'zen';
    }

    public function increment(string $name, int $value = 1): void
    {
        $key = $this->prefix . '.' . $name;
        
        if (!isset($this->metrics[$key])) {
            $this->metrics[$key] = ['type' => 'counter', 'value' => 0];
        }

        $this->metrics[$key]['value'] += $value;
    }

    public function gauge(string $name, float $value): void
    {
        $key = $this->prefix . '.' . $name;
        
        $this->metrics[$key] = ['type' => 'gauge', 'value' => $value];
    }

    public function histogram(string $name, float $value, array $buckets = [0.1, 0.5, 1, 5, 10]): void
    {
        $key = $this->prefix . '.' . $name;
        
        if (!isset($this->metrics[$key])) {
            $this->metrics[$key] = [
                'type' => 'histogram',
                'values' => [],
                'buckets' => array_fill_keys($buckets, 0),
                'count' => 0,
                'sum' => 0,
            ];
        }

        $this->metrics[$key]['values'][] = $value;
        $this->metrics[$key]['count']++;
        $this->metrics[$key]['sum'] += $value;

        foreach ($this->metrics[$key]['buckets'] as $bucket => $count) {
            if ($value <= $bucket) {
                $this->metrics[$key]['buckets'][$bucket]++;
            }
        }
    }

    public function timing(string $name, float $milliseconds): void
    {
        $this->histogram($name . '.duration', $milliseconds);
    }

    public function get(string $name): mixed
    {
        $key = $this->prefix . '.' . $name;
        return $this->metrics[$key] ?? null;
    }

    public function all(): array
    {
        return $this->metrics;
    }

    public function reset(): void
    {
        $this->metrics = [];
    }

    public function getStats(): array
    {
        $stats = [];
        
        foreach ($this->metrics as $name => $metric) {
            if ($metric['type'] === 'histogram' && !empty($metric['values'])) {
                $values = $metric['values'];
                sort($values);
                $count = count($values);
                
                $stats[$name] = [
                    'count' => $count,
                    'sum' => $metric['sum'],
                    'avg' => $metric['sum'] / $count,
                    'min' => $values[0],
                    'max' => $values[$count - 1],
                    'p50' => $values[(int) ($count * 0.5)],
                    'p95' => $values[(int) ($count * 0.95)],
                    'p99' => $values[(int) ($count * 0.99)],
                ];
            } else {
                $stats[$name] = $metric['value'];
            }
        }
        
        return $stats;
    }
}

class HealthChecker
{
    protected array $checks = [];
    protected array $results = [];

    public function register(string $name, callable $check): void
    {
        $this->checks[$name] = $check;
    }

    public function check(string $name): array
    {
        if (!isset($this->checks[$name])) {
            return ['status' => 'unknown', 'message' => 'Check not found'];
        }

        $start = microtime(true);
        
        try {
            $result = ($this->checks[$name])();
            $duration = (microtime(true) - $start) * 1000;

            $this->results[$name] = [
                'status' => 'healthy',
                'duration_ms' => round($duration, 2),
                'result' => $result,
                'checked_at' => time(),
            ];
        } catch (\Throwable $e) {
            $this->results[$name] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'checked_at' => time(),
            ];
        }

        return $this->results[$name];
    }

    public function checkAll(): array
    {
        foreach (array_keys($this->checks) as $name) {
            $this->check($name);
        }

        return $this->results;
    }

    public function isHealthy(string $name): bool
    {
        return ($this->results[$name]['status'] ?? '') === 'healthy';
    }

    public function isHealthyAll(): bool
    {
        foreach ($this->results as $result) {
            if ($result['status'] !== 'healthy') {
                return false;
            }
        }
        return true;
    }

    public function getStatus(): string
    {
        $healthy = 0;
        $unhealthy = 0;

        foreach ($this->results as $result) {
            if ($result['status'] === 'healthy') {
                $healthy++;
            } else {
                $unhealthy++;
            }
        }

        if ($unhealthy === 0) {
            return 'healthy';
        }

        if ($healthy > 0) {
            return 'degraded';
        }

        return 'unhealthy';
    }
}

class PrometheusExporter
{
    protected MetricsCollector $metrics;

    public function __construct(MetricsCollector $metrics)
    {
        $this->metrics = $metrics;
    }

    public function export(): string
    {
        $output = '';
        
        foreach ($this->metrics->all() as $name => $metric) {
            $name = str_replace('.', '_', $name);
            
            if ($metric['type'] === 'counter') {
                $output .= "# TYPE {$name} counter\n";
                $output .= "{$name} " . $metric['value'] . "\n";
            } elseif ($metric['type'] === 'gauge') {
                $output .= "# TYPE {$name} gauge\n";
                $output .= "{$name} " . $metric['value'] . "\n";
            } elseif ($metric['type'] === 'histogram') {
                $output .= "# TYPE {$name} histogram\n";
                
                if (!empty($metric['buckets'])) {
                    foreach ($metric['buckets'] as $bucket => $count) {
                        $bucketLabel = str_replace('.', '_', (string) $bucket);
                        $output .= "{$name}_bucket{{le=\"{$bucketLabel}\"}} {$count}\n";
                    }
                }
                
                $output .= "{$name}_count " . ($metric['count'] ?? 0) . "\n";
                $output .= "{$name}_sum " . ($metric['sum'] ?? 0) . "\n";
            }
        }
        
        return $output;
    }
}

class DistributedTracing
{
    protected ?string $traceId = null;
    protected array $spans = [];
    protected string $serviceName = 'zen';

    public function __construct()
    {
        $this->serviceName = config('observability.service') ?? 'zen';
    }

    public function startTrace(): string
    {
        $this->traceId = bin2hex(random_bytes(16));
        $this->spans = [];
        
        return $this->traceId;
    }

    public function startSpan(string $name): array
    {
        $span = [
            'name' => $name,
            'trace_id' => $this->traceId,
            'span_id' => bin2hex(random_bytes(8)),
            'start_time' => microtime(true),
            'parent_id' => count($this->spans) > 0 ? end($this->spans)['span_id'] : null,
        ];
        
        $this->spans[] = $span;
        
        return $span;
    }

    public function endSpan(string $name, array $span): void
    {
        $span['end_time'] = microtime(true);
        $span['duration_ms'] = ($span['end_time'] - $span['start_time']) * 1000;

        $key = array_search($span, $this->spans, true);
        if ($key !== false) {
            $this->spans[$key] = $span;
        }
    }

    public function getTraceId(): ?string
    {
        return $this->traceId;
    }

    public function getSpans(): array
    {
        return $this->spans;
    }

    public function export(): array
    {
        return [
            'trace_id' => $this->traceId,
            'service' => $this->serviceName,
            'spans' => $this->spans,
            'duration_ms' => !empty($this->spans) 
                ? end($this->spans)['duration_ms'] ?? 0 
                : 0,
        ];
    }
}