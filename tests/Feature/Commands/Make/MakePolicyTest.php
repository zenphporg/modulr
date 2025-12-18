<?php

// TestCase applied via Pest.php
use Zen\Modulr\Console\Commands\Make\MakePolicy;
use Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(TestsMakeCommands::class);

uses(WritesToAppFilesystem::class);

test('it overrides the default command', function (): void {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:policy', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a policy in the module when module option is set', function (): void {
  $command = MakePolicy::class;
  $arguments = ['name' => 'TestPolicy'];
  $expected_path = 'src/Policies/TestPolicy.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Policies',
    'class TestPolicy',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a policy in the app when module option is missing', function (): void {
  $command = MakePolicy::class;
  $arguments = ['name' => 'TestPolicy'];
  $expected_path = 'app/Policies/TestPolicy.php';
  $expected_substrings = [
    'namespace App\Policies',
    'class TestPolicy',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
