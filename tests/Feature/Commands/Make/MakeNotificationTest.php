<?php

// TestCase applied via Pest.php
use Zen\Modulr\Console\Commands\Make\MakeNotification;
use Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(TestsMakeCommands::class);

uses(WritesToAppFilesystem::class);

test('it overrides the default command', function (): void {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:notification', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a notification in the module when module option is set', function (): void {
  $command = MakeNotification::class;
  $arguments = ['name' => 'TestNotification'];
  $expected_path = 'src/Notifications/TestNotification.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Notifications',
    'class TestNotification',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a notification in the app when module option is missing', function (): void {
  $command = MakeNotification::class;
  $arguments = ['name' => 'TestNotification'];
  $expected_path = 'app/Notifications/TestNotification.php';
  $expected_substrings = [
    'namespace App\Notifications',
    'class TestNotification',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
