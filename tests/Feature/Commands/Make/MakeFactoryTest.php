<?php

// TestCase applied via Pest.php
use Illuminate\Database\Eloquent\Factories\Factory;
use Symfony\Component\Console\Input\ArrayInput;
use Zen\Modulr\Console\Commands\Make\MakeFactory;
use Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(TestsMakeCommands::class);

uses(WritesToAppFilesystem::class);

test('it overrides the default command', function (): void {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:factory', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a factory in the module when module option is set', function (): void {
  $command = MakeFactory::class;
  $arguments = ['name' => 'TestFactory'];
  $expected_path = 'database/factories/TestFactory.php';

  $expected_substrings = [
    'use Illuminate\Database\Eloquent\Factories\Factory;',
    'namespace Modules\TestModule\Database\Factories;',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a factory in the app when module option is missing', function (): void {
  $command = MakeFactory::class;
  $arguments = ['name' => 'TestFactory'];
  $expected_path = 'database/factories/TestFactory.php';

  $expected_substrings = [
    Factory::class,
    'namespace Database\Factories;',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a factory with model option in module', function (): void {
  $module = $this->makeModule();

  // Create a model first
  $this->artisan('make:model', ['name' => 'Widget', '--module' => $module->name]);

  // Now create a factory for that model
  $this->artisan('make:factory', [
    'name' => 'WidgetFactory',
    '--model' => 'Widget',
    '--module' => $module->name,
  ])->assertExitCode(0);

  $factoryPath = $module->path('database/factories/WidgetFactory.php');
  expect($factoryPath)->toBeFile();

  $contents = file_get_contents($factoryPath);
  expect($contents)->toContain('namespace Modules\TestModule\Database\Factories;');
  expect($contents)->toContain('Modules\TestModule\Models\Widget');
});

test('it uses default namespace when model is not in Models namespace', function (): void {
  $module = $this->makeModule();

  // Create a factory with a model that doesn't exist in Models namespace
  $this->artisan('make:factory', [
    'name' => 'CustomFactory',
    '--model' => 'CustomModel',
    '--module' => $module->name,
  ])->assertExitCode(0);

  $factoryPath = $module->path('database/factories/CustomFactory.php');
  expect($factoryPath)->toBeFile();

  $contents = file_get_contents($factoryPath);
  expect($contents)->toContain('namespace Modules\TestModule\Database\Factories;');
});

test('it guesses model name when model class exists', function (): void {
  $module = $this->makeModule();

  // Create a model first
  $this->artisan('make:model', ['name' => 'Invoice', '--module' => $module->name]);

  // Require the model so class_exists returns true
  require_once $module->path('src/Models/Invoice.php');

  // Verify the class exists
  expect(class_exists('Modules\\TestModule\\Models\\Invoice'))->toBeTrue();

  // Test guessModelName directly via reflection
  $command = $this->app->make(MakeFactory::class);
  $command->setLaravel($this->app);

  $reflection = new ReflectionClass($command);
  $inputProperty = $reflection->getProperty('input');

  $input = new ArrayInput([
    'name' => 'InvoiceFactory',
    '--module' => $module->name,
  ], $command->getDefinition());
  $inputProperty->setValue($command, $input);

  $guessModelNameMethod = $reflection->getMethod('guessModelName');

  // This should return the existing model class (line 70)
  $result = $guessModelNameMethod->invoke($command, 'InvoiceFactory');
  expect($result)->toBe('Modules\\TestModule\\Models\\Invoice');
});

test('it uses default factories namespace when model not in Models namespace', function (): void {
  $module = $this->makeModule();

  // Create a factory with a fully qualified model that starts with module namespace but not Models
  // This tests line 40 - the else branch
  $this->artisan('make:factory', [
    'name' => 'ServiceFactory',
    '--model' => 'Modules\\TestModule\\Services\\PaymentService',
    '--module' => $module->name,
  ])->assertExitCode(0);

  $factoryPath = $module->path('database/factories/ServiceFactory.php');
  expect($factoryPath)->toBeFile();

  $contents = file_get_contents($factoryPath);
  // Should use the default Database\Factories namespace since model is not in module's Models namespace
  expect($contents)->toContain('namespace Modules\TestModule\Database\Factories;');
});
