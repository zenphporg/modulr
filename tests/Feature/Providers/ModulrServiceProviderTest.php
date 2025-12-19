<?php

use Zen\Modulr\ModulrServiceProvider;
use Zen\Modulr\Support\AutoDiscoveryHelper;
use Zen\Modulr\Support\Registry;

test('it can be instantiated', function (): void {
  $provider = new ModulrServiceProvider($this->app);
  expect($provider)->toBeInstanceOf(ModulrServiceProvider::class);
});

test('it has register method', function (): void {
  expect(method_exists(ModulrServiceProvider::class, 'register'))->toBeTrue();
});

test('it has boot method', function (): void {
  expect(method_exists(ModulrServiceProvider::class, 'boot'))->toBeTrue();
});

test('it registers services in container', function (): void {
  $provider = new ModulrServiceProvider($this->app);
  $provider->register();

  // Check that Registry is bound as singleton
  expect($this->app->bound(Registry::class))->toBeTrue();
  expect($this->app->isShared(Registry::class))->toBeTrue();

  // Check that AutoDiscoveryHelper is bound as singleton
  expect($this->app->bound(AutoDiscoveryHelper::class))->toBeTrue();
  expect($this->app->isShared(AutoDiscoveryHelper::class))->toBeTrue();
});

test('it boots services', function (): void {
  $provider = new ModulrServiceProvider($this->app);
  $provider->register();
  $provider->boot();

  // Verify boot completed without errors
  expect($provider)->toBeInstanceOf(ModulrServiceProvider::class);
});

test('it publishes vendor files', function (): void {
  $provider = new ModulrServiceProvider($this->app);

  // Use reflection to access protected method
  $reflection = new ReflectionClass($provider);
  $method = $reflection->getMethod('publishVendorFiles');

  $method->invoke($provider);

  // Verify method executed without errors
  expect($provider)->toBeInstanceOf(ModulrServiceProvider::class);
});

test('it has protected helper methods', function (): void {
  $provider = new ModulrServiceProvider($this->app);
  $provider->register();

  // Use reflection to access protected methods
  $reflection = new ReflectionClass($provider);

  $registryMethod = $reflection->getMethod('registry');
  $registry = $registryMethod->invoke($provider);
  expect($registry)->toBeInstanceOf(Registry::class);

  $helperMethod = $reflection->getMethod('autoDiscoveryHelper');
  $helper = $helperMethod->invoke($provider);
  expect($helper)->toBeInstanceOf(AutoDiscoveryHelper::class);
});
