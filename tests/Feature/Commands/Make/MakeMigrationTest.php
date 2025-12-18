<?php

// TestCase applied via Pest.php
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Filesystem\Filesystem;
use Zen\Modulr\Console\Commands\Make\MakeMigration;
use Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(TestsMakeCommands::class);

uses(WritesToAppFilesystem::class);

beforeEach(function (): void {
  $this->app->singleton('migration.creator', fn (Application $app): MigrationCreator => new class($app->make(Filesystem::class), $app->basePath('stubs')) extends MigrationCreator
  {
    public function getDatePrefix(): string
    {
      return 'test';
    }
  });
});

test('it overrides the default command', function (): void {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:migration', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a migration in the module when module option is set', function (): void {
  $command = MakeMigration::class;
  $arguments = ['name' => 'test_migration'];
  $expected_path = 'database/migrations/test_test_migration.php';
  $expected_substrings = [
    Migration::class,
    'extends Migration',
    'function up',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a migration in the app when module option is missing', function (): void {
  $command = MakeMigration::class;
  $arguments = ['name' => 'test_migration'];
  $expected_path = 'database/migrations/test_test_migration.php';
  $expected_substrings = [
    Migration::class,
    'extends Migration',
    'function up',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
