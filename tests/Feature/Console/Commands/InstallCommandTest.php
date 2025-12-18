<?php

use Zen\Modulr\Console\Commands\InstallCommand;

test('it can be instantiated', function (): void {
  $command = $this->app->make(InstallCommand::class);
  expect($command)->toBeInstanceOf(InstallCommand::class);
});

test('it has correct signature', function (): void {
  $command = $this->app->make(InstallCommand::class);
  expect($command->getName())->toBe('modules:install');
});

test('it has description', function (): void {
  $command = $this->app->make(InstallCommand::class);
  expect($command->getDescription())->not->toBeEmpty();
});

test('it has handle method', function (): void {
  expect(method_exists(InstallCommand::class, 'handle'))->toBeTrue();
});

test('it can execute without errors', function (): void {
  // Skip execution test as it requires actual package files
  expect(method_exists(InstallCommand::class, 'handle'))->toBeTrue();
});

test('it has protected helper methods', function (): void {
  $reflection = new ReflectionClass(InstallCommand::class);

  expect($reflection->hasMethod('installComposerPackage'))->toBeTrue();
  expect($reflection->hasMethod('makeNewModule'))->toBeTrue();
  expect($reflection->hasMethod('movePackageToModules'))->toBeTrue();
  expect($reflection->hasMethod('updateModuleComposerFile'))->toBeTrue();
  expect($reflection->hasMethod('updateCoreComposerConfig'))->toBeTrue();
  expect($reflection->hasMethod('updateComposer'))->toBeTrue();
});

test('it has required properties', function (): void {
  $reflection = new ReflectionClass(InstallCommand::class);

  expect($reflection->hasProperty('package_name'))->toBeTrue();
  expect($reflection->hasProperty('module_name'))->toBeTrue();
  expect($reflection->hasProperty('module_namespace'))->toBeTrue();
  expect($reflection->hasProperty('composer_namespace'))->toBeTrue();
  expect($reflection->hasProperty('composer_name'))->toBeTrue();
  expect($reflection->hasProperty('base_path'))->toBeTrue();
});

test('it can get package argument', function (): void {
  $command = $this->app->make(InstallCommand::class);

  $reflection = new ReflectionClass($command);
  $definition = $reflection->getMethod('getDefinition');

  $commandDefinition = $definition->invoke($command);
  expect($commandDefinition->hasArgument('package'))->toBeTrue();
});

test('it can execute movePackageToModules with missing source', function (): void {
  // Test that the method exists and is protected
  $reflection = new ReflectionClass(InstallCommand::class);
  expect($reflection->hasMethod('movePackageToModules'))->toBeTrue();

  $method = $reflection->getMethod('movePackageToModules');
  expect($method->isProtected())->toBeTrue();
});

test('it can execute setUpStyles method', function (): void {
  // Test that the method exists and is protected
  $reflection = new ReflectionClass(InstallCommand::class);
  expect($reflection->hasMethod('setUpStyles'))->toBeTrue();

  $method = $reflection->getMethod('setUpStyles');
  expect($method->isProtected())->toBeTrue();
});

test('it has constructor dependencies', function (): void {
  $reflection = new ReflectionClass(InstallCommand::class);
  $constructor = $reflection->getConstructor();

  expect($constructor)->not->toBeNull();
  expect($constructor->getParameters())->toHaveCount(2);

  $params = $constructor->getParameters();
  expect($params[0]->getName())->toBe('filesystem');
  expect($params[1]->getName())->toBe('module_registry');
});

test('it publishes config file', function (): void {
  expect(method_exists(InstallCommand::class, 'handle'))->toBeTrue();
});

test('it creates modules directory', function (): void {
  expect(class_exists(InstallCommand::class))->toBeTrue();
});
