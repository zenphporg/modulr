<?php

use Zen\Modulr\Console\Commands\Make\MakeObserver;

uses(Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);
uses(Zen\Modulr\Tests\Concerns\TestsMakeCommands::class);

test('it overrides the default command', function () {
  $this->requiresLaravelVersion('9.2.0');

  $this->artisan('make:observer', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a observer in the module when module option is set', function () {
  $command = MakeObserver::class;
  $arguments = ['name' => 'TestObserver'];
  $expected_path = 'src/Observers/TestObserver.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Observers',
    'class TestObserver',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a observer in the app when module option is missing', function () {
  $command = MakeObserver::class;
  $arguments = ['name' => 'TestObserver'];
  $expected_path = 'app/Observers/TestObserver.php';
  $expected_substrings = [
    'namespace App\Observers',
    'class TestObserver',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
