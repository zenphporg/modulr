<?php

// TestCase applied via Pest.php
use Zen\Modulr\Console\Commands\Make\MakeProvider;
use Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(TestsMakeCommands::class);

uses(WritesToAppFilesystem::class);

test('it overrides the default command', function (): void {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:provider', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a provider in the module when module option is set', function (): void {
  $command = MakeProvider::class;
  $arguments = ['name' => 'TestProvider'];
  $expected_path = 'src/Providers/TestProvider.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Providers',
    'class TestProvider',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a provider in the app when module option is missing', function (): void {
  $command = MakeProvider::class;
  $arguments = ['name' => 'TestProvider'];
  $expected_path = 'app/Providers/TestProvider.php';
  $expected_substrings = [
    'namespace App\Providers',
    'class TestProvider',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
