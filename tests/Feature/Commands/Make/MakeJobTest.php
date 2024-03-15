<?php

use Zen\Modulr\Console\Commands\Make\MakeJob;

uses(Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);
uses(Zen\Modulr\Tests\Concerns\TestsMakeCommands::class);

test('it overrides the default command', function () {
  $this->requiresLaravelVersion('9.2.0');

  $this->artisan('make:job', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a job in the module when module option is set', function () {
  $command = MakeJob::class;
  $arguments = ['name' => 'TestJob'];
  $expected_path = 'src/Jobs/TestJob.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Jobs',
    'class TestJob',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a job in the app when module option is missing', function () {
  $command = MakeJob::class;
  $arguments = ['name' => 'TestJob'];
  $expected_path = 'app/Jobs/TestJob.php';
  $expected_substrings = [
    'namespace App\Jobs',
    'class TestJob',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
