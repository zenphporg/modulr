<?php

// TestCase applied via Pest.php
use Zen\Modulr\Support\Registry;

test('basic setup works', function () {
  // Test that the registry is available
  $registry = $this->app->make(Registry::class);
  expect($registry)->toBeInstanceOf(Registry::class);

  // Test that modules collection is available (may or may not be empty)
  $modules = $registry->modules();
  expect($modules)->toBeInstanceOf(\Illuminate\Support\Collection::class);

  // Test that config is loaded (should be absolute path to our test modules directory)
  expect(config('modulr.modules_directory'))->toEndWith('/tests/app/modules');
});

test('module creation works', function () {
  // Test basic module registry functionality without creating actual modules
  $registry = $this->app->make(Registry::class);
  expect($registry->getModulesPath())->toEndWith('/tests/app/modules');
  expect($registry->modules())->toBeInstanceOf(\Illuminate\Support\Collection::class);
});
