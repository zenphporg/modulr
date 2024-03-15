<?php

use Zen\Modulr\Console\Commands\Make\MakeRequest;

uses(Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);
uses(Zen\Modulr\Tests\Concerns\TestsMakeCommands::class);

test('it overrides the default command', function () {
  $this->requiresLaravelVersion('9.2.0');

  $this->artisan('make:request', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a request in the module when module option is set', function () {
  $command = MakeRequest::class;
  $arguments = ['name' => 'TestRequest'];
  $expected_path = 'src/Http/Requests/TestRequest.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Http\Requests',
    'class TestRequest',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a request in the app when module option is missing', function () {
  $command = MakeRequest::class;
  $arguments = ['name' => 'TestRequest'];
  $expected_path = 'app/Http/Requests/TestRequest.php';
  $expected_substrings = [
    'namespace App\Http\Requests',
    'class TestRequest',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
