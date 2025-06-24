<?php

// TestCase applied via Pest.php
use Zen\Modulr\Console\Commands\Make\MakeMigration;
use \Illuminate\Database\Migrations\MigrationCreator;

uses(\Zen\Modulr\Tests\Concerns\TestsMakeCommands::class);

uses(\Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);

beforeEach(function () {
    $this->app->singleton('migration.creator', function ($app) {
      return new class($app['files'], $app->basePath('stubs')) extends MigrationCreator
      {
        function getDatePrefix()
        {
            return 'test';
        }
      };
    });
});

test('it overrides the default command', function () {
    $this->requiresLaravelVersion('9.2.0');

    $this->artisan('make:migration', ['--help' => true])
      ->expectsOutputToContain('--module')
      ->assertExitCode(0);
});

test('it scaffolds a migration in the module when module option is set', function () {
    $command = MakeMigration::class;
    $arguments = ['name' => 'test_migration'];
    $expected_path = 'database/migrations/test_test_migration.php';
    $expected_substrings = [
      'Illuminate\Database\Migrations\Migration',
      'extends Migration',
      'function up',
    ];

    $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a migration in the app when module option is missing', function () {
    $command = MakeMigration::class;
    $arguments = ['name' => 'test_migration'];
    $expected_path = 'database/migrations/test_test_migration.php';
    $expected_substrings = [
      'Illuminate\Database\Migrations\Migration',
      'extends Migration',
      'function up',
    ];

    $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});