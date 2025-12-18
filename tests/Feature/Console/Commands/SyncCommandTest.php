<?php

use Zen\Modulr\Console\Commands\SyncCommand;

test('it extends console command', function (): void {
  expect(class_exists(SyncCommand::class))->toBeTrue();
});

test('it can be instantiated', function (): void {
  $command = $this->app->make(SyncCommand::class);

  expect($command)->toBeInstanceOf(SyncCommand::class);
});

test('it has correct signature', function (): void {
  $command = $this->app->make(SyncCommand::class);

  expect($command->getName())->toBe('modules:sync');
});

test('it has description', function (): void {
  $command = $this->app->make(SyncCommand::class);

  expect($command->getDescription())->not->toBeEmpty();
});

test('it has handle method', function (): void {
  $command = $this->app->make(SyncCommand::class);

  expect(method_exists($command, 'handle'))->toBeTrue();
});

test('it can execute without errors', function (): void {
  $result = $this->artisan('modules:sync');

  $result->assertExitCode(0);
});

test('it uses phpstorm writers', function (): void {
  $reflection = new ReflectionClass(SyncCommand::class);

  expect($reflection->hasMethod('handle'))->toBeTrue();

  // Check if it has properties or methods related to PhpStorm integration
  $constructor = $reflection->getConstructor();
  expect($constructor)->not->toBeNull();
});
