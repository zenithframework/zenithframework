<?php

declare(strict_types=1);

namespace Tests;

class ExampleTest extends TestCase
{
    public function test_true_is_true(): void
    {
        $this->assertTrue(true);
    }

    public function test_false_is_false(): void
    {
        $this->assertFalse(false);
    }

    public function test_addition(): void
    {
        $this->assertEquals(4, 2 + 2);
    }

    public function test_array_has_key(): void
    {
        $arr = ['name' => 'John', 'age' => 30];
        $this->assertArrayHasKey('name', $arr);
    }

    public function test_assert_null(): void
    {
        $this->assertNull(null);
    }
}
