<?php

// TestCase applied via Pest.php
use Zen\Modulr\Console\Commands\Make\MakeMail;
use Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(TestsMakeCommands::class);

uses(WritesToAppFilesystem::class);

test('it overrides the default command', function (): void {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:mail', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a mail in the module when module option is set', function (): void {
  $command = MakeMail::class;
  $arguments = ['name' => 'TestMail'];
  $expected_path = 'src/Mail/TestMail.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Mail',
    'class TestMail',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a mail in the app when module option is missing', function (): void {
  $command = MakeMail::class;
  $arguments = ['name' => 'TestMail'];
  $expected_path = 'app/Mail/TestMail.php';
  $expected_substrings = [
    'namespace App\Mail',
    'class TestMail',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
