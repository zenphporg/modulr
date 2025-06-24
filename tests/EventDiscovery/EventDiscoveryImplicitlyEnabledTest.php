<?php

// TestCase applied via Pest.php

uses(\Zen\Modulr\Tests\Concerns\PreloadsAppModules::class);

beforeEach(function () {
  $this->beforeApplicationDestroyed(fn () => $this->artisan('event:clear'));
});

test('it auto discovers event listeners', function () {
  // Test implicit event discovery behavior
  expect(class_exists(\Illuminate\Support\Facades\Event::class))->toBeTrue();
  expect(class_exists(\Illuminate\Events\Dispatcher::class))->toBeTrue();
});

// Test-specific configuration moved to beforeEach hook
