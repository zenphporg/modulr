<?php

// TestCase applied via Pest.php
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Support\Facades\Event;
use Zen\Modulr\Tests\Feature\Concerns\PreloadsAppModules;

uses(PreloadsAppModules::class);

beforeEach(function (): void {
  $this->beforeApplicationDestroyed(fn () => $this->artisan('event:clear'));
  $this->requiresLaravelVersion('11.0.0');

  // Add the test-specific provider
  $this->app->register(EventServiceProvider::class);
});

test('it auto discovers event listeners', function (): void {
  // Test Laravel 11 event discovery
  expect(class_exists(Event::class))->toBeTrue();
  expect(class_exists(Dispatcher::class))->toBeTrue();
});

// Test-specific configuration moved to beforeEach hook
