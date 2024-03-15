<?php

use Zen\Modulr\Console\Commands\Make\MakeFactory;

uses(Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);
uses(Zen\Modulr\Tests\Concerns\TestsMakeCommands::class);

test('it overrides the default command', function () {
  $this->requiresLaravelVersion('9.2.0');

  $this->artisan('make:factory', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a factory in the module when module option is set', function () {
  $command = MakeFactory::class;
  $arguments = ['name' => 'TestFactory'];
  $expected_path = 'database/factories/TestFactory.php';

  $expected_substrings = [
    'use Illuminate\Database\Eloquent\Factories\Factory;',
    'namespace Modules\TestModule\Database\Factories;',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a factory in the app when module option is missing', function () {
  $command = MakeFactory::class;
  $arguments = ['name' => 'TestFactory'];
  $expected_path = 'database/factories/TestFactory.php';

  $expected_substrings = [
    'Illuminate\Database\Eloquent\Factories\Factory',
    'namespace Database\Factories;',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
