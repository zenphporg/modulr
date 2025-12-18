<?php

// TestCase applied via Pest.php
use Illuminate\Database\Eloquent\Factories\Factory;
use Zen\Modulr\Console\Commands\Make\MakeFactory;
use Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(TestsMakeCommands::class);

uses(WritesToAppFilesystem::class);

test('it overrides the default command', function (): void {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:factory', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a factory in the module when module option is set', function (): void {
  $command = MakeFactory::class;
  $arguments = ['name' => 'TestFactory'];
  $expected_path = 'database/factories/TestFactory.php';

  $expected_substrings = [
    'use Illuminate\Database\Eloquent\Factories\Factory;',
    'namespace Modules\TestModule\Database\Factories;',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a factory in the app when module option is missing', function (): void {
  $command = MakeFactory::class;
  $arguments = ['name' => 'TestFactory'];
  $expected_path = 'database/factories/TestFactory.php';

  $expected_substrings = [
    Factory::class,
    'namespace Database\Factories;',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
