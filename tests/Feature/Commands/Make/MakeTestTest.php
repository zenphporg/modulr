<?php

use Zen\Modulr\Console\Commands\Make\MakeTest;

uses(Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);
uses(Zen\Modulr\Tests\Concerns\TestsMakeCommands::class);

test('it overrides the default command', function () {
  $this->requiresLaravelVersion('9.2.0');

  $this->artisan('make:test', ['--help' => true])
      ->expectsOutputToContain('--module')
      ->assertExitCode(0);
});

test('it scaffolds a test in the module when module option is set', function () {
  $command = MakeTest::class;
  $arguments = ['name' => 'TestTest'];
  $expected_path = 'tests/Feature/TestTest.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Tests',
    'use Tests\TestCase',
    'class TestTest extends TestCase',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a test in the app when module option is missing', function () {
  $command = MakeTest::class;
  $arguments = ['name' => 'TestTest'];
  $expected_path = 'tests/Feature/TestTest.php';
  $expected_substrings = [
    'namespace Tests\Feature',
    'use Tests\TestCase',
    'class TestTest extends TestCase',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
