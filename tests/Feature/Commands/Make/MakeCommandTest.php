<?php

// TestCase applied via Pest.php
use Zen\Modulr\Console\Commands\Make\MakeCommand;

uses(\Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands::class);

uses(\Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem::class);

test('it overrides the default command', function () {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:command', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a command in the module when module option is set', function () {
  $command = MakeCommand::class;
  $arguments = ['name' => 'TestCommand'];
  $expected_path = 'src/Console/Commands/TestCommand.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Console\Commands',
    'use Illuminate\Console\Command',
    'class TestCommand extends Command',
    'app:test-command',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it uses the command option for name when set', function () {
  $command = MakeCommand::class;
  $arguments = ['name' => 'TestCommand', '--command' => 'foo:bar-baz'];
  $expected_path = 'src/Console/Commands/TestCommand.php';
  $expected_substrings = [
    "signature = 'foo:bar-baz'",
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a command in the app when module option is missing', function () {
  $command = MakeCommand::class;
  $arguments = ['name' => 'TestCommand'];
  $expected_path = 'app/Console/Commands/TestCommand.php';
  $expected_substrings = [
    'namespace App\Console\Commands',
    'use Illuminate\Console\Command',
    'class TestCommand extends Command',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
