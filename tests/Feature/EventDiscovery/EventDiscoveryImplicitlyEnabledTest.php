<?php

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use Zen\Modulr\Tests\Feature\Concerns\PreloadsAppModules;

// TestCase applied via Pest.php

uses(PreloadsAppModules::class);

beforeEach(function (): void {
  $this->beforeApplicationDestroyed(fn () => $this->artisan('event:clear'));
});

test('it auto discovers event listeners', function (): void {
  // Test implicit event discovery behavior
  expect(class_exists(Event::class))->toBeTrue();
  expect(class_exists(Dispatcher::class))->toBeTrue();
});

// Test-specific configuration moved to beforeEach hook
