<?php

use Zen\Modulr\Console\Commands\Make\MakeChannel;

uses(Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);
uses(Zen\Modulr\Tests\Concerns\TestsMakeCommands::class);

test('it overrides the default command', function () {
  $this->requiresLaravelVersion('9.2.0');

  $this->artisan('make:channel', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a channel in the module when module option is set', function () {
  $command = MakeChannel::class;
  $arguments = ['name' => 'TestChannel'];
  $expected_path = 'src/Broadcasting/TestChannel.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Broadcasting',
    'class TestChannel',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a channel in the app when module option is missing', function () {
  $command = MakeChannel::class;
  $arguments = ['name' => 'TestChannel'];
  $expected_path = 'app/Broadcasting/TestChannel.php';
  $expected_substrings = [
    'namespace App\Broadcasting',
    'class TestChannel',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
