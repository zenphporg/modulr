<?php

// TestCase applied via Pest.php
use Zen\Modulr\Console\Commands\Make\MakeEvent;
use Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(TestsMakeCommands::class);

uses(WritesToAppFilesystem::class);

test('it overrides the default command', function (): void {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:event', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a event in the module when module option is set', function (): void {
  $command = MakeEvent::class;
  $arguments = ['name' => 'TestEvent'];
  $expected_path = 'src/Events/TestEvent.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Events',
    'class TestEvent',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a event in the app when module option is missing', function (): void {
  $command = MakeEvent::class;
  $arguments = ['name' => 'TestEvent'];
  $expected_path = 'app/Events/TestEvent.php';
  $expected_substrings = [
    'namespace App\Events',
    'class TestEvent',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
