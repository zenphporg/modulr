<?php

use Illuminate\Support\Collection;
use Zen\Modulr\Exceptions\CannotFindModuleForPathException;
use Zen\Modulr\Support\ConfigStore;
use Zen\Modulr\Support\Registry;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(WritesToAppFilesystem::class);

test('it can be instantiated', function (): void {
  $registry = new Registry('/path/to/modules', '/path/to/cache');
  expect($registry)->toBeInstanceOf(Registry::class);
});

test('it can get modules path', function (): void {
  $registry = new Registry('/path/to/modules', '/path/to/cache');
  expect($registry->getModulesPath())->toBe('/path/to/modules');
});

test('it can get cache path', function (): void {
  $registry = new Registry('/path/to/modules', '/path/to/cache');
  expect($registry->getCachePath())->toBe('/path/to/cache');
});

test('it returns null for null module name', function (): void {
  $registry = new Registry('/path/to/modules', '/path/to/cache');
  expect($registry->module())->toBeNull();
});

test('it returns null for empty module name', function (): void {
  $registry = new Registry('/path/to/modules', '/path/to/cache');
  expect($registry->module(''))->toBeNull();
});

test('it returns null for non-existent module', function (): void {
  $registry = new Registry('/path/to/modules', '/path/to/cache');
  expect($registry->module('non-existent'))->toBeNull();
});

test('it can get module for path', function (): void {
  $this->makeModule('test-module');

  $registry = $this->app->make(Registry::class);
  $modulePath = $this->getModulePath('test-module', '/src/SomeClass.php');
  $module = $registry->moduleForPath($modulePath);

  expect($module)->toBeInstanceOf(ConfigStore::class);
  expect($module->name)->toBe('test-module');
});

test('it returns null for path not in modules', function (): void {
  $registry = new Registry('/path/to/modules', '/path/to/cache');
  expect($registry->moduleForPath('/some/other/path'))->toBeNull();
});

test('it throws exception for path not in modules when using or fail', function (): void {
  $registry = new Registry('/path/to/modules', '/path/to/cache');

  expect(fn (): ConfigStore => $registry->moduleForPathOrFail('/some/other/path'))
    ->toThrow(CannotFindModuleForPathException::class);
});

test('it can get module for class', function (): void {
  $this->makeModule('test-module');

  $registry = $this->app->make(Registry::class);
  $module = $registry->moduleForClass('Modules\\TestModule\\SomeClass');

  expect($module)->toBeInstanceOf(ConfigStore::class);
  expect($module->name)->toBe('test-module');
});

test('it returns null for class not in modules', function (): void {
  $registry = new Registry('/path/to/modules', '/path/to/cache');
  expect($registry->moduleForClass('App\\SomeClass'))->toBeNull();
});

test('it can reload modules', function (): void {
  $this->makeModule('test-module');

  $registry = $this->app->make(Registry::class);
  $modules = $registry->reload();

  expect($modules)->toBeInstanceOf(Collection::class);
});

test('it returns empty collection when modules path does not exist', function (): void {
  $registry = new Registry('/non/existent/path', '/path/to/cache');
  expect($registry->modules())->toBeEmpty();
});

test('it handles symlinked modules path', function (): void {
  $this->makeModule('test-module');

  // Create a symlink to the modules directory
  $modulesPath = $this->getModulePath('test-module', '/');
  $modulesPath = dirname($modulesPath); // Get the modules directory
  $symlinkPath = sys_get_temp_dir().'/modulr_symlink_'.uniqid();

  if (! @symlink($modulesPath, $symlinkPath)) {
    $this->markTestSkipped('Could not create symlink');
  }

  try {
    // Create a registry with the symlink path
    $registry = new Registry($symlinkPath, '');

    // Get the real path to a file in the module
    $realFilePath = realpath($modulesPath.'/test-module/src');

    // Try to get the module using the real path (not the symlink path)
    $module = $registry->moduleForPath($realFilePath);

    expect($module)->toBeInstanceOf(ConfigStore::class);
    expect($module->name)->toBe('test-module');
  } finally {
    unlink($symlinkPath);
  }
});
