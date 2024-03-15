<?php

use Zen\Modulr\Console\Commands\Make\MakeEvent;

uses(Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);
uses(Zen\Modulr\Tests\Concerns\TestsMakeCommands::class);

test('it overrides the default command', function () {
  $this->requiresLaravelVersion('9.2.0');

  $this->artisan('make:event', ['--help' => true])
      ->expectsOutputToContain('--module')
      ->assertExitCode(0);
});

test('it scaffolds a event in the module when module option is set', function () {
  $command = MakeEvent::class;
  $arguments = ['name' => 'TestEvent'];
  $expected_path = 'src/Events/TestEvent.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Events',
    'class TestEvent',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a event in the app when module option is missing', function () {
  $command = MakeEvent::class;
  $arguments = ['name' => 'TestEvent'];
  $expected_path = 'app/Events/TestEvent.php';
  $expected_substrings = [
    'namespace App\Events',
    'class TestEvent',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
