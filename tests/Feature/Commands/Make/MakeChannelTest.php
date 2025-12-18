<?php

// TestCase applied via Pest.php
use Zen\Modulr\Console\Commands\Make\MakeChannel;
use Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(TestsMakeCommands::class);

uses(WritesToAppFilesystem::class);

test('it overrides the default command', function (): void {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:channel', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a channel in the module when module option is set', function (): void {
  $command = MakeChannel::class;
  $arguments = ['name' => 'TestChannel'];
  $expected_path = 'src/Broadcasting/TestChannel.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Broadcasting',
    'class TestChannel',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a channel in the app when module option is missing', function (): void {
  $command = MakeChannel::class;
  $arguments = ['name' => 'TestChannel'];
  $expected_path = 'app/Broadcasting/TestChannel.php';
  $expected_substrings = [
    'namespace App\Broadcasting',
    'class TestChannel',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
