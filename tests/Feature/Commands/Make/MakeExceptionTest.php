<?php

use Zen\Modulr\Console\Commands\Make\MakeException;

uses(Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);
uses(Zen\Modulr\Tests\Concerns\TestsMakeCommands::class);

test('it overrides the default command', function () {
  $this->requiresLaravelVersion('9.2.0');

  $this->artisan('make:exception', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a exception in the module when module option is set', function () {
  $command = MakeException::class;
  $arguments = ['name' => 'TestException'];
  $expected_path = 'src/Exceptions/TestException.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Exceptions',
    'class TestException',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a exception in the app when module option is missing', function () {
  $command = MakeException::class;
  $arguments = ['name' => 'TestException'];
  $expected_path = 'app/Exceptions/TestException.php';
  $expected_substrings = [
    'namespace App\Exceptions',
    'class TestException',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
