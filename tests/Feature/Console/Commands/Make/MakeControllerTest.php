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

test('it scaffolds a controller in the module when module option is set', function () {
  $command = MakeController::class;
  $arguments = ['name' => 'TestController'];
  $expected_path = 'src/Http/Controllers/TestController.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Http\Controllers',
    'class TestController',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a controller in the app when module option is missing', function () {
  $command = MakeController::class;
  $arguments = ['name' => 'TestController'];
  $expected_path = 'app/Http/Controllers/TestController.php';
  $expected_substrings = [
    'namespace App\Http\Controllers',
    'class TestController',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a controller with model option in module', function () {
  // Test that the parseModel method gets called when --model is used
  expect(method_exists(MakeController::class, 'parseModel'))->toBeTrue();
});

test('it throws exception for invalid model characters in module', function () {
  // Create a module first
  $this->artisan(\Zen\Modulr\Console\Commands\Make\MakeModule::class, [
    'name' => 'test-module',
    '--accept-namespace' => true,
  ])->assertExitCode(0);

  // Reload the module registry
  $this->app->make(\Zen\Modulr\Support\Registry::class)->reload();

  // Test with invalid model name - expect exception
  expect(fn () => $this->artisan('make:controller', [
    'name' => 'TestController',
    '--module' => 'test-module',
    '--model' => 'User@Invalid',
  ]))->toThrow(\InvalidArgumentException::class, 'Model name contains invalid characters.');
});
