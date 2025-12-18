<?php

// TestCase applied via Pest.php
use Zen\Modulr\Support\ConfigStore;
use Zen\Modulr\Support\Registry;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(WritesToAppFilesystem::class);

test('it resolves modules', function (): void {
  $this->makeModule('test-module');
  $this->makeModule('test-module-two');

  $registry = $this->app->make(Registry::class);

  expect($registry->module('test-module'))->toBeInstanceOf(ConfigStore::class);
  expect($registry->module('test-module-two'))->toBeInstanceOf(ConfigStore::class);
  expect($registry->module('non-existant-module'))->toBeNull();

  expect($registry->modules())->toHaveCount(2);

  // Use the actual modules path from the registry instead of getModulePath
  $modulesPath = $registry->getModulesPath();

  $module = $registry->moduleForPath($modulesPath.'/test-module/foo/bar');
  expect($module)->toBeInstanceOf(ConfigStore::class);
  expect($module->name)->toEqual('test-module');

  $module = $registry->moduleForPath($modulesPath.'/test-module-two/foo/bar');
  expect($module)->toBeInstanceOf(ConfigStore::class);
  expect($module->name)->toEqual('test-module-two');

  $module = $registry->moduleForClass('Modules\\TestModule\\Foo');
  expect($module)->toBeInstanceOf(ConfigStore::class);
  expect($module->name)->toEqual('test-module');

  $module = $registry->moduleForClass('Modules\\TestModuleTwo\\Foo');
  expect($module)->toBeInstanceOf(ConfigStore::class);
  expect($module->name)->toEqual('test-module-two');
});
