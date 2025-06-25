<?php

// TestCase applied via Pest.php
use Zen\Modulr\Console\Commands\Make\MakeRule;

uses(\Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands::class);

uses(\Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem::class);

test('it overrides the default command', function () {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:rule', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a rule in the module when module option is set', function () {
  $command = MakeRule::class;
  $arguments = ['name' => 'TestRule'];
  $expected_path = 'src/Rules/TestRule.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Rules',
    'class TestRule',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a rule in the app when module option is missing', function () {
  $command = MakeRule::class;
  $arguments = ['name' => 'TestRule'];
  $expected_path = 'app/Rules/TestRule.php';
  $expected_substrings = [
    'namespace App\Rules',
    'class TestRule',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
