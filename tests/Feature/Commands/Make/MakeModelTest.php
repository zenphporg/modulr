<?php

use Zen\Modulr\Console\Commands\Make\MakeModel;

uses(Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);
uses(Zen\Modulr\Tests\Concerns\TestsMakeCommands::class);

test('it overrides the default command', function () {
  $this->requiresLaravelVersion('9.2.0');

  $this->artisan('make:model', ['--help' => true])
      ->expectsOutputToContain('--module')
      ->assertExitCode(0);
});

test('it scaffolds a model in the module when module option is set', function () {
  $command = MakeModel::class;
  $arguments = ['name' => 'TestModel'];
  $expected_path = 'src/Models/TestModel.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Models',
    'class TestModel',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a model in the app when module option is missing', function () {
  $command = MakeModel::class;
  $arguments = ['name' => 'TestModel'];
  $expected_path = 'app/Models/TestModel.php';
  $expected_substrings = [
    'namespace App\Models',
    'class TestModel',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
