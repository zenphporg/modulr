<?php

use Zen\Modulr\Console\Commands\Make\MakeMiddleware;

uses(Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);
uses(Zen\Modulr\Tests\Concerns\TestsMakeCommands::class);

test('it overrides the default command', function () {
  $this->requiresLaravelVersion('9.2.0');

  $this->artisan('make:middleware', ['--help' => true])
      ->expectsOutputToContain('--module')
      ->assertExitCode(0);
});

test('it scaffolds a middleware in the module when module option is set', function () {
  $command = MakeMiddleware::class;
  $arguments = ['name' => 'TestMiddleware'];
  $expected_path = 'src/Http/Middleware/TestMiddleware.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Http\Middleware',
    'class TestMiddleware',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a middleware in the app when module option is missing', function () {
  $command = MakeMiddleware::class;
  $arguments = ['name' => 'TestMiddleware'];
  $expected_path = 'app/Http/Middleware/TestMiddleware.php';
  $expected_substrings = [
    'namespace App\Http\Middleware',
    'class TestMiddleware',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
