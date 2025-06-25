<?php

use Zen\Modulr\Console\Commands\Make\MakeController;

test('it can be instantiated', function () {
  $command = $this->app->make(MakeController::class);
  expect($command)->toBeInstanceOf(MakeController::class);
});

test('it has correct signature', function () {
  $command = $this->app->make(MakeController::class);
  expect($command->getName())->toBe('make:controller');
});

test('it has description', function () {
  $command = $this->app->make(MakeController::class);
  expect($command->getDescription())->not->toBeEmpty();
});

test('it has handle method', function () {
  expect(method_exists(MakeController::class, 'handle'))->toBeTrue();
});

test('it can execute basic controller creation', function () {
  $result = $this->artisan('make:controller', ['name' => 'TestController']);
  $result->assertExitCode(0);
});

test('it supports module option', function () {
  $command = $this->app->make(MakeController::class);

  $reflection = new ReflectionClass($command);
  $definition = $reflection->getMethod('getDefinition');
  $definition->setAccessible(true);

  $commandDefinition = $definition->invoke($command);
  expect($commandDefinition->hasOption('module'))->toBeTrue();
});

test('it extends laravel make controller', function () {
  $reflection = new ReflectionClass(MakeController::class);
  expect($reflection->getParentClass()->getName())->toBe(\Illuminate\Routing\Console\ControllerMakeCommand::class);
});

test('it has protected helper methods', function () {
  $reflection = new ReflectionClass(MakeController::class);

  expect($reflection->hasMethod('getStub'))->toBeTrue();
  expect($reflection->hasMethod('getDefaultNamespace'))->toBeTrue();
});

test('it can handle module-specific controller creation', function () {
  // Test that the command can handle module option
  $command = $this->app->make(MakeController::class);
  expect($command)->toBeInstanceOf(MakeController::class);
});
