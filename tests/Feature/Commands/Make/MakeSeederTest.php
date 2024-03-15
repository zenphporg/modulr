<?php

use Zen\Modulr\Console\Commands\Make\MakeSeeder;

uses(Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);
uses(Zen\Modulr\Tests\Concerns\TestsMakeCommands::class);

test('it overrides the default command', function () {
  $this->requiresLaravelVersion('9.2.0');

  $this->artisan('make:seeder', ['--help' => true])
      ->expectsOutputToContain('--module')
      ->assertExitCode(0);
});

test('it scaffolds a seeder in the module when module option is set', function () {
  $command = MakeSeeder::class;
  $arguments = ['name' => 'TestSeeder'];
  $expected_path = version_compare($this->app->version(), '8.0.0', '>=')
      ? 'database/seeders/TestSeeder.php'
      : 'database/seeds/TestSeeder.php';
  $expected_substrings = [
    'use Illuminate\Database\Seeder',
    'class TestSeeder extends Seeder',
  ];

  if (version_compare($this->app->version(), '8.0.0', '>=')) {
    $expected_substrings[] = 'namespace Modules\TestModule\Database\Seeders;';
  }

  $this->filesystem()->deleteDirectory($this->getBasePath().$this->normalizeDirectorySeparators('database/seeds'));
  $this->filesystem()->deleteDirectory($this->getModulePath('test-module', 'database/seeds'));

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a seeder in the app when module option is missing', function () {
  $command = MakeSeeder::class;
  $arguments = ['name' => 'TestSeeder'];
  $expected_path = version_compare($this->app->version(), '8.0.0', '>=')
      ? 'database/seeders/TestSeeder.php'
      : 'database/seeds/TestSeeder.php';
  $expected_substrings = [
    'use Illuminate\Database\Seeder',
    'class TestSeeder extends Seeder',
  ];

  if (version_compare($this->app->version(), '8.0.0', '>=')) {
    $expected_substrings[] = 'namespace Database\Seeders;';
  }

  $this->filesystem()->deleteDirectory($this->getBasePath().$this->normalizeDirectorySeparators('database/seeds'));

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});
