<?php

namespace Zen\Modulr\Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
  /**
   * A basic unit test example.
   */
  public function test_that_true_is_true(): void
  {
    $this->assertTrue(true);
  }

  /**
   * Test basic math operations.
   */
  public function test_basic_math(): void
  {
    $this->assertEquals(4, 2 + 2);
    $this->assertEquals(0, 2 - 2);
    $this->assertEquals(4, 2 * 2);
    $this->assertEquals(1, 2 / 2);
  }

  /**
   * Test string operations.
   */
  public function test_string_operations(): void
  {
    $this->assertEquals('Hello World', 'Hello'.' '.'World');
    $this->assertStringContainsString('World', 'Hello World');
    $this->assertStringStartsWith('Hello', 'Hello World');
    $this->assertStringEndsWith('World', 'Hello World');
  }
}
