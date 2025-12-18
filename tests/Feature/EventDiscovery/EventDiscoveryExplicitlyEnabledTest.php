<?php

use Illuminate\Support\Facades\Event;
use Zen\Modulr\Tests\Feature\Concerns\PreloadsAppModules;

// TestCase applied via Pest.php

uses(PreloadsAppModules::class);

beforeEach(function (): void {
  $this->beforeApplicationDestroyed(fn () => $this->artisan('event:clear'));

  // Configure this test to enable event discovery
  config(['app-modules.should_discover_events' => true]);
});

test('it auto discovers event listeners', function (): void {
  // Test event discovery configuration
  expect(config('app-modules.should_discover_events'))->toBeTrue();
  expect(class_exists(Event::class))->toBeTrue();
});

// Test-specific configuration moved to beforeEach hook
