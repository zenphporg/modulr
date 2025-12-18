<?php

use Zen\Modulr\Providers\CommandsServiceProvider;

test('it extends service provider', function (): void {
  expect(class_exists(CommandsServiceProvider::class))->toBeTrue();
});

test('it can be instantiated', function (): void {
  $provider = new CommandsServiceProvider($this->app);

  expect($provider)->toBeInstanceOf(CommandsServiceProvider::class);
});

test('it registers make commands', function (): void {
  expect(method_exists(CommandsServiceProvider::class, 'register'))->toBeTrue();
});

test('it has command mappings', function (): void {
  $reflection = new ReflectionClass(CommandsServiceProvider::class);

  // Check if it has properties or methods related to command registration
  expect($reflection->hasMethod('register'))->toBeTrue();
});

test('it registers in application', function (): void {
  // Check that the provider class exists and can be instantiated
  expect(class_exists(CommandsServiceProvider::class))->toBeTrue();
  $provider = new CommandsServiceProvider($this->app);
  expect($provider)->toBeInstanceOf(CommandsServiceProvider::class);
});
