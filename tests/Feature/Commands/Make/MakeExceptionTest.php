<?php

// TestCase applied via Pest.php
use Zen\Modulr\Console\Commands\Make\MakeException;
use Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(TestsMakeCommands::class);

uses(WritesToAppFilesystem::class);

test('it overrides the default command', function (): void {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:exception', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a exception in the module when module option is set', function (): void {
  $command = MakeException::class;
  $arguments = ['name' => 'TestException'];
  $expected_path = 'src/Exceptions/TestException.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Exceptions',
    'class TestException',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a exception in the app when module option is missing', function (): void {
  $command = MakeException::class;
  $arguments = ['name' => 'TestException'];
  $expected_path = 'app/Exceptions/TestException.php';
  $expected_substrings = [
    'namespace App\Exceptions',
    'class TestException',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
