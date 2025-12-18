<?php

// TestCase applied via Pest.php
use Zen\Modulr\Console\Commands\Make\MakeMiddleware;
use Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(TestsMakeCommands::class);

uses(WritesToAppFilesystem::class);

test('it overrides the default command', function (): void {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:middleware', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a middleware in the module when module option is set', function (): void {
  $command = MakeMiddleware::class;
  $arguments = ['name' => 'TestMiddleware'];
  $expected_path = 'src/Http/Middleware/TestMiddleware.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Http\Middleware',
    'class TestMiddleware',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a middleware in the app when module option is missing', function (): void {
  $command = MakeMiddleware::class;
  $arguments = ['name' => 'TestMiddleware'];
  $expected_path = 'app/Http/Middleware/TestMiddleware.php';
  $expected_substrings = [
    'namespace App\Http\Middleware',
    'class TestMiddleware',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
