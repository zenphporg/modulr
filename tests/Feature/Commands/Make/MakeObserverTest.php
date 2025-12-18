<?php

// TestCase applied via Pest.php
use Zen\Modulr\Console\Commands\Make\MakeObserver;
use Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(TestsMakeCommands::class);

uses(WritesToAppFilesystem::class);

test('it overrides the default command', function (): void {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:observer', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a observer in the module when module option is set', function (): void {
  $command = MakeObserver::class;
  $arguments = ['name' => 'TestObserver'];
  $expected_path = 'src/Observers/TestObserver.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Observers',
    'class TestObserver',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a observer in the app when module option is missing', function (): void {
  $command = MakeObserver::class;
  $arguments = ['name' => 'TestObserver'];
  $expected_path = 'app/Observers/TestObserver.php';
  $expected_substrings = [
    'namespace App\Observers',
    'class TestObserver',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
