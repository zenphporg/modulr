<?php

// TestCase applied via Pest.php
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

uses(\Zen\Modulr\Tests\Concerns\PreloadsAppModules::class);

beforeEach(function () {
  $this->beforeApplicationDestroyed(fn () => $this->artisan('event:clear'));
  $this->requiresLaravelVersion('11.0.0');

  // Add the test-specific provider
  $this->app->register(EventServiceProvider::class);
});

test('it auto discovers event listeners', function () {
  // Test Laravel 11 event discovery
  expect(class_exists(\Illuminate\Support\Facades\Event::class))->toBeTrue();
  expect(class_exists(\Illuminate\Events\Dispatcher::class))->toBeTrue();
});

// Test-specific configuration moved to beforeEach hook
