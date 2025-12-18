<?php

// TestCase applied via Pest.php
use Zen\Modulr\Console\Commands\Make\MakeRequest;
use Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(TestsMakeCommands::class);

uses(WritesToAppFilesystem::class);

test('it overrides the default command', function (): void {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:request', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a request in the module when module option is set', function (): void {
  $command = MakeRequest::class;
  $arguments = ['name' => 'TestRequest'];
  $expected_path = 'src/Http/Requests/TestRequest.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Http\Requests',
    'class TestRequest',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a request in the app when module option is missing', function (): void {
  $command = MakeRequest::class;
  $arguments = ['name' => 'TestRequest'];
  $expected_path = 'app/Http/Requests/TestRequest.php';
  $expected_substrings = [
    'namespace App\Http\Requests',
    'class TestRequest',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
