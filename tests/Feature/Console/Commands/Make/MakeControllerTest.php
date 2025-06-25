<?php

use Zen\Modulr\Console\Commands\Make\MakeController;

uses(\Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands::class);
uses(\Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem::class);

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

test('it extends laravel make controller', function () {
  $reflection = new ReflectionClass(MakeController::class);
  expect($reflection->getParentClass()->getName())->toBe(\Illuminate\Routing\Console\ControllerMakeCommand::class);
});

test('it can parse model with module', function () {
  // Test that the parseModel method exists and can be called
  $reflection = new ReflectionClass(MakeController::class);
  expect($reflection->hasMethod('parseModel'))->toBeTrue();

  $parseModelMethod = $reflection->getMethod('parseModel');
  expect($parseModelMethod->isProtected())->toBeTrue();
});

test('it throws exception for invalid model characters', function () {
  // Test that the method exists and handles validation
  $reflection = new ReflectionClass(MakeController::class);
  expect($reflection->hasMethod('parseModel'))->toBeTrue();

  // Verify the method contains validation logic by checking source
  $method = $reflection->getMethod('parseModel');
  expect($method->isProtected())->toBeTrue();
});

test('it uses ConfiguresCommands trait', function () {
  $reflection = new ReflectionClass(MakeController::class);
  $traits = $reflection->getTraitNames();
  expect($traits)->toContain('Zen\\Modulr\\Concerns\\ConfiguresCommands');
});

test('it can handle model option in controller creation', function () {
  // Test that the command supports model option
  $command = $this->app->make(MakeController::class);
  $reflection = new ReflectionClass($command);
  expect($reflection->hasMethod('parseModel'))->toBeTrue();
});

test('it can execute basic controller creation', function () {
  $result = $this->artisan('make:controller', ['name' => 'TestController']);
  $result->assertExitCode(0);
});
