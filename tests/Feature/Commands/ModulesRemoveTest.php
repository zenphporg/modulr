<?php

use Zen\Modulr\Support\Registry;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

// TestCase applied via Pest.php
uses(WritesToAppFilesystem::class);

test('it removes a module when given a name', function (): void {
  $this->makeModule('test-module');
  $this->makeModule('test-module-two');

  /** @var Registry $registry */
  $registry = $this->app->make(Registry::class);
  $registry->reload();

  expect($registry->modules())->toHaveCount(2);

  $modulePath = $registry->module('test-module')?->base_path;
  expect($modulePath)->not->toBeNull();
  expect($modulePath)->toBeDirectory();

  $this->artisan('modules:remove', ['name' => 'test-module'])
    ->assertSuccessful();

  $registry->reload();

  expect($registry->modules())->toHaveCount(1);
  expect($registry->module('test-module'))->toBeNull();
  expect($registry->module('test-module-two'))->not->toBeNull();
  $this->assertDirectoryDoesNotExist($modulePath);
});

test('it returns error when module does not exist', function (): void {
  $this->makeModule('test-module');

  $this->artisan('modules:remove', ['name' => 'non-existent-module'])
    ->assertFailed()
    ->expectsOutput("Module 'non-existent-module' not found.");
});

test('it returns error when no modules exist', function (): void {
  $this->artisan('modules:remove')
    ->assertFailed()
    ->expectsOutput('No modules found.');
});

test('it returns error when no name provided during tests', function (): void {
  $this->makeModule('test-module');

  $this->artisan('modules:remove')
    ->assertFailed()
    ->expectsOutput('Module name is required.');
});

test('it removes module with force flag', function (): void {
  $this->makeModule('test-module');

  /** @var Registry $registry */
  $registry = $this->app->make(Registry::class);
  $registry->reload();

  $modulePath = $registry->module('test-module')?->base_path;

  $this->artisan('modules:remove', ['name' => 'test-module', '--force' => true])
    ->assertSuccessful()
    ->expectsOutput("Module 'test-module' has been removed.");

  $this->assertDirectoryDoesNotExist($modulePath);
});
