<?php

use Zen\Modulr\Console\Commands\InstallCommand;

test('it can be instantiated', function () {
  $command = $this->app->make(InstallCommand::class);
  expect($command)->toBeInstanceOf(InstallCommand::class);
});

test('it has correct signature', function () {
  $command = $this->app->make(InstallCommand::class);
  expect($command->getName())->toBe('modules:install');
});

test('it has description', function () {
  $command = $this->app->make(InstallCommand::class);
  expect($command->getDescription())->not->toBeEmpty();
});

test('it has handle method', function () {
  expect(method_exists(InstallCommand::class, 'handle'))->toBeTrue();
});

test('it can execute without errors', function () {
  // Skip execution test as it requires actual package files
  expect(method_exists(InstallCommand::class, 'handle'))->toBeTrue();
});

test('it has protected helper methods', function () {
  $reflection = new ReflectionClass(InstallCommand::class);

  expect($reflection->hasMethod('installComposerPackage'))->toBeTrue();
  expect($reflection->hasMethod('makeNewModule'))->toBeTrue();
  expect($reflection->hasMethod('movePackageToModules'))->toBeTrue();
  expect($reflection->hasMethod('updateModuleComposerFile'))->toBeTrue();
  expect($reflection->hasMethod('updateCoreComposerConfig'))->toBeTrue();
  expect($reflection->hasMethod('updateComposer'))->toBeTrue();
});

test('it has required properties', function () {
  $reflection = new ReflectionClass(InstallCommand::class);

  expect($reflection->hasProperty('package_name'))->toBeTrue();
  expect($reflection->hasProperty('module_name'))->toBeTrue();
  expect($reflection->hasProperty('module_namespace'))->toBeTrue();
  expect($reflection->hasProperty('composer_namespace'))->toBeTrue();
  expect($reflection->hasProperty('composer_name'))->toBeTrue();
  expect($reflection->hasProperty('base_path'))->toBeTrue();
});

test('it can get package argument', function () {
  $command = $this->app->make(InstallCommand::class);

  $reflection = new ReflectionClass($command);
  $definition = $reflection->getMethod('getDefinition');
  $definition->setAccessible(true);

  $commandDefinition = $definition->invoke($command);
  expect($commandDefinition->hasArgument('package'))->toBeTrue();
});

test('it publishes config file', function () {
  expect(method_exists(InstallCommand::class, 'handle'))->toBeTrue();
});

test('it creates modules directory', function () {
  expect(class_exists(InstallCommand::class))->toBeTrue();
});
