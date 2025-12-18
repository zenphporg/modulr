<?php

// TestCase applied via Pest.php
use Zen\Modulr\Console\Commands\Make\MakeJob;
use Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(TestsMakeCommands::class);

uses(WritesToAppFilesystem::class);

test('it overrides the default command', function (): void {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:job', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a job in the module when module option is set', function (): void {
  $command = MakeJob::class;
  $arguments = ['name' => 'TestJob'];
  $expected_path = 'src/Jobs/TestJob.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Jobs',
    'class TestJob',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a job in the app when module option is missing', function (): void {
  $command = MakeJob::class;
  $arguments = ['name' => 'TestJob'];
  $expected_path = 'app/Jobs/TestJob.php';
  $expected_substrings = [
    'namespace App\Jobs',
    'class TestJob',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
