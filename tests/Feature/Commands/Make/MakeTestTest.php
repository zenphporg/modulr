<?php

// TestCase applied via Pest.php
use Zen\Modulr\Console\Commands\Make\MakeTest;
use Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(TestsMakeCommands::class);

uses(WritesToAppFilesystem::class);

test('it overrides the default command', function (): void {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:test', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a test in the module when module option is set', function (): void {
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

test('it scaffolds a test in the app when module option is missing', function (): void {
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
