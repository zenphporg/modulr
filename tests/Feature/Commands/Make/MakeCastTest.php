<?php

// TestCase applied via Pest.php
use Zen\Modulr\Console\Commands\Make\MakeCast;
use Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(TestsMakeCommands::class);

uses(WritesToAppFilesystem::class);

test('it overrides the default command', function (): void {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:cast', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a cast in the module when module option is set', function (): void {
  $command = MakeCast::class;
  $arguments = ['name' => 'JsonCast'];
  $expected_path = '/src/Casts/JsonCast.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Casts',
    'class JsonCast',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a cast in the app when module option is missing', function (): void {
  $command = MakeCast::class;
  $arguments = ['name' => 'JsonCast'];
  $expected_path = 'app/Casts/JsonCast.php';
  $expected_substrings = [
    'namespace App\Casts',
    'class JsonCast',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
