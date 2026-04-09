<?php

declare(strict_types=1);

namespace Tests;

class TestCase
{
    protected array $errors = [];

    public function assertTrue(bool $condition): void
    {
        if (!$condition) {
            throw new \RuntimeException("Expected true, got false");
        }
    }

    public function assertFalse(bool $condition): void
    {
        if ($condition) {
            throw new \RuntimeException("Expected false, got true");
        }
    }

    public function assertEquals(mixed $expected, mixed $actual): void
    {
        if ($expected !== $actual) {
            throw new \RuntimeException("Expected " . var_export($expected, true) . ", got " . var_export($actual, true));
        }
    }

    public function assertNull(mixed $value): void
    {
        if ($value !== null) {
            throw new \RuntimeException("Expected null, got " . var_export($value, true));
        }
    }

    public function assertNotNull(mixed $value): void
    {
        if ($value === null) {
            throw new \RuntimeException("Expected not null, got null");
        }
    }

    public function assertCount(int $expected, array $array): void
    {
        $count = count($array);
        if ($count !== $expected) {
            throw new \RuntimeException("Expected {$expected} items, got {$count}");
        }
    }

    public function assertArrayHasKey(mixed $key, array $array): void
    {
        if (!array_key_exists($key, $array)) {
            throw new \RuntimeException("Array does not have key: " . var_export($key, true));
        }
    }

    public function assertStringContainsString(string $needle, string $haystack): void
    {
        if (strpos($haystack, $needle) === false) {
            throw new \RuntimeException("Failed asserting that '{$haystack}' contains '{$needle}'");
        }
    }

    protected function setUp(): void {}
    protected function tearDown(): void {}

    public function run(): array
    {
        $this->setUp();
        
        $methods = get_class_methods($this);
        $passed = 0;
        $failed = 0;

        foreach ($methods as $method) {
            if (str_starts_with($method, 'test_')) {
                try {
                    $this->{$method}();
                    if (empty($this->errors)) {
                        $passed++;
                    } else {
                        $failed++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $this->errors[] = $e->getMessage();
                }
            }
        }

        $this->tearDown();

        return ['passed' => $passed, 'failed' => $failed, 'errors' => $this->errors];
    }
}
