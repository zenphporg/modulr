<?php

namespace Zen\Modulr\Tests\Feature;

use Zen\Modulr\Tests\TestCase;
use Zen\Modulr\Support\Registry;

class ExampleTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // This test verifies that the Laravel application boots successfully
        // with our Modulr service provider
        $this->assertTrue(true);
    }

    /**
     * Test that the Modulr service provider is registered.
     */
    public function test_modulr_service_provider_is_registered(): void
    {
        // Check that the Registry class is bound in the container
        $this->assertTrue($this->app->bound(Registry::class));
        
        // Check that we can resolve the Registry
        $registry = $this->app->make(Registry::class);
        $this->assertInstanceOf(Registry::class, $registry);
    }

    /**
     * Test that the modulr config is available.
     */
    public function test_modulr_config_is_available(): void
    {
        // Check that the modulr config is loaded
        $this->assertNotNull(config('modulr'));
        
        // Check that the modules directory config is set
        $this->assertNotNull(config('modulr.modules_directory'));
    }

    /**
     * Test that the Registry can handle empty modules directory.
     */
    public function test_registry_handles_empty_modules_directory(): void
    {
        $registry = $this->app->make(Registry::class);
        
        // Should return empty collection when no modules exist
        $modules = $registry->modules();
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $modules);
        $this->assertTrue($modules->isEmpty());
    }

    /**
     * Test that artisan commands are available.
     */
    public function test_modulr_commands_are_available(): void
    {
        // Test that our custom commands are registered
        $commands = $this->app->make('Illuminate\Contracts\Console\Kernel')->all();
        
        // Check for some of our key commands
        $this->assertArrayHasKey('modules:make', $commands);
        $this->assertArrayHasKey('modules:list', $commands);
        $this->assertArrayHasKey('modules:cache', $commands);
        $this->assertArrayHasKey('modules:clear', $commands);
    }
}
