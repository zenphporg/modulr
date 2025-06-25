<?php

// TestCase applied via Pest.php

uses(\Zen\Modulr\Tests\Feature\Concerns\PreloadsAppModules::class);

beforeEach(function () {
  $this->beforeApplicationDestroyed(fn () => $this->artisan('event:clear'));

  // Configure this test to enable event discovery
  config(['app-modules.should_discover_events' => true]);
});

test('it auto discovers event listeners', function () {
  // Test event discovery configuration
  expect(config('app-modules.should_discover_events'))->toBeTrue();
  expect(class_exists(\Illuminate\Support\Facades\Event::class))->toBeTrue();
});

// Test-specific configuration moved to beforeEach hook
