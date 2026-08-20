<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_true_is_true(): void
    {
        $this->assertTrue(true);
    }

    public function test_environment_is_testing(): void
    {
        $this->assertSame('testing', $_ENV['APP_ENV'] ?? 'testing');
    }
}
