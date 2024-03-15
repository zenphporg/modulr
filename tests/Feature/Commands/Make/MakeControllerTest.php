<?php

use Symfony\Component\Console\Exception\InvalidOptionException;
use Zen\Modulr\Console\Commands\Make\MakeController;

uses(Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);
uses(Zen\Modulr\Tests\Concerns\TestsMakeCommands::class);

test('it overrides the default command', function () {
  $this->requiresLaravelVersion('9.2.0');

  $this->artisan('make:controller', ['--help' => true])
      ->expectsOutputToContain('--module')
      ->assertExitCode(0);
});

test('it produces an error if the module does not exist', function () {
  $this->expectException(InvalidOptionException::class);
  $this->expectExceptionMessage('The "does-not-exist" module does not exist.');

  $this->artisan('make:controller', ['name' => 'Test', '--module' => 'does-not-exist']);
});

test('it scaffolds a controller in the module when module option is set', function () {
  $command = MakeController::class;
  $arguments = ['name' => 'TestController'];
  $expected_path = 'src/Http/Controllers/TestController.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Http\Controllers',
    'use App\Http\Controllers\Controller',
    'class TestController extends Controller',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a controller in the app when module option is missing', function () {
  $command = MakeController::class;
  $arguments = ['name' => 'TestController'];
  $expected_path = 'app/Http/Controllers/TestController.php';
  $expected_substrings = [
    'namespace App\Http\Controllers',
    'class TestController extends Controller',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
