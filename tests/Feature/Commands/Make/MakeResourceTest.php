<?php

// TestCase applied via Pest.php
use Zen\Modulr\Console\Commands\Make\MakeResource;
use Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(TestsMakeCommands::class);

uses(WritesToAppFilesystem::class);

test('it overrides the default command', function (): void {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:resource', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a resource in the module when module option is set', function (): void {
  $command = MakeResource::class;
  $arguments = ['name' => 'TestResource'];
  $expected_path = 'src/Http/Resources/TestResource.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Http\Resources',
    'class TestResource',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a resource in the app when module option is missing', function (): void {
  $command = MakeResource::class;
  $arguments = ['name' => 'TestResource'];
  $expected_path = 'app/Http/Resources/TestResource.php';
  $expected_substrings = [
    'namespace App\Http\Resources',
    'class TestResource',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
