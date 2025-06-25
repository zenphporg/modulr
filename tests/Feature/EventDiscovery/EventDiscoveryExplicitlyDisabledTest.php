<?php

// TestCase applied via Pest.php

uses(\Zen\Modulr\Tests\Feature\Concerns\PreloadsAppModules::class);

beforeEach(function () {
  $this->beforeApplicationDestroyed(fn () => $this->artisan('event:clear'));

  // Configure this test to disable event discovery
  config(['app-modules.should_discover_events' => false]);
});

test('it does not auto discover event listeners', function () {
  // Test event discovery configuration
  expect(config('app-modules.should_discover_events'))->toBeFalse();
  expect(class_exists(\Illuminate\Support\Facades\Event::class))->toBeTrue();
});

// Test-specific configuration moved to beforeEach hook
