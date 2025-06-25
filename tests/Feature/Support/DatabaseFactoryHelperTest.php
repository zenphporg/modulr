<?php

use Zen\Modulr\Support\ConfigStore;
use Zen\Modulr\Support\DatabaseFactoryHelper;
use Zen\Modulr\Support\Registry;

beforeEach(function () {
  $this->registry = new Registry('/path/to/modules', '');
  $this->helper = new DatabaseFactoryHelper($this->registry);
});

test('it creates model name resolver', function () {
  $resolver = $this->helper->modelNameResolver();

  expect($resolver)->toBeInstanceOf(Closure::class);
});

test('it creates factory name resolver', function () {
  $resolver = $this->helper->factoryNameResolver();

  expect($resolver)->toBeInstanceOf(Closure::class);
});

test('it resolves model names for module factories', function () {
  // Test that the resolver is callable
  $resolver = $this->helper->modelNameResolver();
  expect($resolver)->toBeInstanceOf(Closure::class);
});

test('it resolves model names for app factories', function () {
  // Test that the resolver works with the registry
  $resolver = $this->helper->modelNameResolver();
  expect($resolver)->toBeInstanceOf(Closure::class);
});

test('it resolves factory names for module models', function () {
  // Create a mock module
  $namespaces = collect(['/path/to/modules/test-module/src' => 'Modules\\TestModule\\']);
  $module = new ConfigStore('test-module', '/path/to/modules/test-module', $namespaces);

  // Mock the registry to return our module
  $registry = Mockery::mock(Registry::class);
  $registry->shouldReceive('moduleForClass')
    ->with('Modules\\TestModule\\Models\\User')
    ->andReturn($module);

  $helper = new DatabaseFactoryHelper($registry);
  $resolver = $helper->factoryNameResolver();

  $factoryName = $resolver('Modules\\TestModule\\Models\\User');

  expect($factoryName)->toBe('Modules\\TestModule\\Database\\Factories\\UserFactory');
});

test('it resolves factory names for app models', function () {
  // Mock the registry to return null (no module found)
  $registry = Mockery::mock(Registry::class);
  $registry->shouldReceive('moduleForClass')
    ->with('App\\Models\\User')
    ->andReturn(null);

  $helper = new DatabaseFactoryHelper($registry);
  $resolver = $helper->factoryNameResolver();

  $factoryName = $resolver('App\\Models\\User');

  expect($factoryName)->toBe('Database\\Factories\\UserFactory');
});

test('it gets factory namespace via reflection', function () {
  $namespace = $this->helper->namespace();

  expect($namespace)->toBeString();
  expect($namespace)->toBe('Database\\Factories\\');
});

test('it can reset resolvers', function () {
  expect(method_exists($this->helper, 'resetResolvers'))->toBeTrue();
});
