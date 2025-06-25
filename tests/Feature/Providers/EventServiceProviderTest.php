<?php

use Zen\Modulr\Providers\EventServiceProvider;

test('it extends core event service provider', function () {
  expect(class_exists(EventServiceProvider::class))->toBeTrue();
});

test('it can be instantiated', function () {
  $provider = new EventServiceProvider($this->app);

  expect($provider)->toBeInstanceOf(EventServiceProvider::class);
});

test('it has discover events method', function () {
  $provider = new EventServiceProvider($this->app);

  expect(method_exists($provider, 'discoverEvents'))->toBeTrue();
  expect(method_exists($provider, 'shouldDiscoverEvents'))->toBeTrue();
});

test('it can discover events', function () {
  $provider = new EventServiceProvider($this->app);

  $events = $provider->discoverEvents();

  expect($events)->toBeArray();
});

test('it determines if should discover events', function () {
  $provider = new EventServiceProvider($this->app);

  $shouldDiscover = $provider->shouldDiscoverEvents();

  expect($shouldDiscover)->toBeBool();
});

test('it discovers events within module directories', function () {
  $provider = new EventServiceProvider($this->app);

  $reflection = new ReflectionClass($provider);
  $method = $reflection->getMethod('discoverEventsWithin');

  $directories = $method->invoke($provider);

  expect($directories)->toBeArray();
});
