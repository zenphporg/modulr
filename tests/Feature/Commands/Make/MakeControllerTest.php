<?php

// TestCase applied via Pest.php
use Symfony\Component\Console\Exception\InvalidOptionException;
use Zen\Modulr\Console\Commands\Make\MakeController;
use Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(TestsMakeCommands::class);

uses(WritesToAppFilesystem::class);

test('it overrides the default command', function (): void {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:controller', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it produces an error if the module does not exist', function (): void {
  $this->expectException(InvalidOptionException::class);
  $this->expectExceptionMessage('The "does-not-exist" module does not exist.');

  $this->artisan('make:controller', ['name' => 'Test', '--module' => 'does-not-exist']);
});

test('it scaffolds a controller in the module when module option is set', function (): void {
  $command = MakeController::class;
  $arguments = ['name' => 'TestController'];
  $expected_path = 'src/Http/Controllers/TestController.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Http\Controllers',
    'class TestController',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a controller in the app when module option is missing', function (): void {
  $command = MakeController::class;
  $arguments = ['name' => 'TestController'];
  $expected_path = 'app/Http/Controllers/TestController.php';
  $expected_substrings = [
    'namespace App\Http\Controllers',
    'class TestController',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
