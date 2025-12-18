<?php

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Seeder;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

// TestCase applied via Pest.php
uses(WritesToAppFilesystem::class);

test('it looks for seeders in module namespace when module option is set', function (): void {
  $app_seeder = createMockSeeder($this->app);
  $module_seeder = createMockSeeder($this->app);

  $this->app->instance('Modules\\TestModule\\Database\\Seeders\\DatabaseSeeder', $module_seeder);
  $this->app->instance('Modules\\TestModule\\DatabaseSeeder', $module_seeder);
  $this->app->instance('Database\\Seeders\\DatabaseSeeder', $app_seeder);
  $this->app->instance('DatabaseSeeder', $app_seeder);

  $this->makeModule('test-module');

  $this->artisan('db:seed', ['--module' => 'test-module']);

  expect($module_seeder->invoked)->toEqual(1);
  expect($app_seeder->invoked)->toEqual(0);
});

test('it looks for named seeders in module namespace when module option is set', function (): void {
  $app_seeder = createMockSeeder($this->app);
  $module_seeder = createMockSeeder($this->app);

  $this->app->instance('Modules\\TestModule\\Database\\Seeders\\Custom\\Seeder', $module_seeder);
  $this->app->instance('Database\\Seeders\\Custom\\Seeder', $app_seeder);
  $this->app->instance('Custom\\Seeder', $app_seeder);

  $this->makeModule('test-module');

  $this->artisan('db:seed', ['--module' => 'test-module', '--class' => 'Custom\\Seeder']);

  expect($module_seeder->invoked)->toEqual(1);
  expect($app_seeder->invoked)->toEqual(0);
});

test('it looks for seeders in app namespace when module option is missing', function (): void {
  $mock = createMockSeeder($this->app);

  $this->app->instance('Database\\Seeders\\DatabaseSeeder', $mock);
  $this->app->instance('DatabaseSeeder', $mock);

  $this->artisan('db:seed');

  expect($mock->invoked)->toEqual(1);
});

test('it looks for named seeders in app namespace when module option is missing', function (): void {
  $mock = createMockSeeder($this->app);

  $this->app->instance('Database\\Seeders\\CustomSeeder', $mock);
  $this->app->instance('CustomSeeder', $mock);

  $this->artisan('db:seed', ['--class' => 'CustomSeeder']);

  expect($mock->invoked)->toEqual(1);
});

function createMockSeeder(Application $app): Seeder
{
  return new class($app) extends Seeder
  {
    public int $invoked = 0;

    public function __invoke(array $parameters = []): void
    {
      $this->invoked++;
    }

    public function run(): void
    {
      $this->invoked++;
    }
  };
}
