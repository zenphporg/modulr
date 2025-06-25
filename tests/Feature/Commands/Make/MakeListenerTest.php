<?php

// TestCase applied via Pest.php
use Zen\Modulr\Console\Commands\Make\MakeListener;

uses(\Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands::class);

uses(\Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem::class);

test('it overrides the default command', function () {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:listener', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a listener in the module when module option is set', function () {
  $command = MakeListener::class;
  $arguments = ['name' => 'TestListener'];
  $expected_path = 'src/Listeners/TestListener.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Listeners',
    'class TestListener',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a listener in the app when module option is missing', function () {
  $command = MakeListener::class;
  $arguments = ['name' => 'TestListener'];
  $expected_path = 'app/Listeners/TestListener.php';
  $expected_substrings = [
    'namespace App\Listeners',
    'class TestListener',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
