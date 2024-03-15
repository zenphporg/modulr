<?php

use Zen\Modulr\Console\Commands\Make\MakeNotification;

uses(Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);
uses(Zen\Modulr\Tests\Concerns\TestsMakeCommands::class);

test('it overrides the default command', function () {
  $this->requiresLaravelVersion('9.2.0');

  $this->artisan('make:notification', ['--help' => true])
      ->expectsOutputToContain('--module')
      ->assertExitCode(0);
});

test('it scaffolds a notification in the module when module option is set', function () {
  $command = MakeNotification::class;
  $arguments = ['name' => 'TestNotification'];
  $expected_path = 'src/Notifications/TestNotification.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Notifications',
    'class TestNotification',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a notification in the app when module option is missing', function () {
  $command = MakeNotification::class;
  $arguments = ['name' => 'TestNotification'];
  $expected_path = 'app/Notifications/TestNotification.php';
  $expected_substrings = [
    'namespace App\Notifications',
    'class TestNotification',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
