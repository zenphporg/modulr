<?php

use Zen\Modulr\Support\ConfigStore;

test('it can be created from composer file', function () {
  // Test that the static method exists
  expect(method_exists(ConfigStore::class, 'fromComposerFile'))->toBeTrue();
});

test('it can be constructed manually', function () {
  $namespaces = collect([
    '/path/to/src' => 'Modules\\TestModule\\',
    '/path/to/tests' => 'Modules\\TestModule\\Tests\\',
  ]);

  $configStore = new ConfigStore('test-module', '/path/to/module', $namespaces);

  expect($configStore->name)->toBe('test-module');
  expect($configStore->base_path)->toBe('/path/to/module');
  expect($configStore->namespaces)->toBe($namespaces);
});

test('it returns correct paths', function () {
  $configStore = new ConfigStore('test-module', '/path/to/module');

  expect($configStore->path())->toBe('/path/to/module');
  expect($configStore->path('src'))->toBe('/path/to/module/src');
  expect($configStore->path('src/Models'))->toBe('/path/to/module/src/Models');
});

test('it returns first namespace', function () {
  $namespaces = collect([
    '/path/to/src' => 'Modules\\TestModule\\',
    '/path/to/tests' => 'Modules\\TestModule\\Tests\\',
  ]);

  $configStore = new ConfigStore('test-module', '/path/to/module', $namespaces);

  expect($configStore->namespace())->toBe('Modules\\TestModule\\');
});

test('it qualifies class names', function () {
  $namespaces = collect([
    '/path/to/src' => 'Modules\\TestModule\\',
  ]);

  $configStore = new ConfigStore('test-module', '/path/to/module', $namespaces);

  expect($configStore->qualify('Models\\User'))->toBe('Modules\\TestModule\\Models\\User');
  expect($configStore->qualify('\\Models\\User'))->toBe('Modules\\TestModule\\Models\\User');
});

test('it converts path to fully qualified class name', function () {
  $namespaces = collect([
    '/path/to/module/src' => 'Modules\\TestModule\\',
    '/path/to/module/tests' => 'Modules\\TestModule\\Tests\\',
  ]);

  $configStore = new ConfigStore('test-module', '/path/to/module', $namespaces);

  expect($configStore->pathToFullyQualifiedClassName('/path/to/module/src/Models/User.php'))
    ->toBe('Modules\\TestModule\\Models\\User');

  expect($configStore->pathToFullyQualifiedClassName('/path/to/module/tests/Feature/UserTest.php'))
    ->toBe('Modules\\TestModule\\Tests\\Feature\\UserTest');
});

test('it handles windows paths in path to class name conversion', function () {
  $namespaces = collect([
    '/path/to/module/src' => 'Modules\\TestModule\\',
  ]);

  $configStore = new ConfigStore('test-module', '/path/to/module', $namespaces);

  expect($configStore->pathToFullyQualifiedClassName('\\path\\to\\module\\src\\Models\\User.php'))
    ->toBe('Modules\\TestModule\\Models\\User');
});

test('it throws exception for invalid path', function () {
  $namespaces = collect([
    '/path/to/module/src' => 'Modules\\TestModule\\',
  ]);

  $configStore = new ConfigStore('test-module', '/path/to/module', $namespaces);

  expect(fn () => $configStore->pathToFullyQualifiedClassName('/invalid/path/User.php'))
    ->toThrow(RuntimeException::class, "Unable to infer qualified class name for '/invalid/path/User.php'");
});

test('it converts to array', function () {
  $namespaces = collect([
    '/path/to/src' => 'Modules\\TestModule\\',
  ]);

  $configStore = new ConfigStore('test-module', '/path/to/module', $namespaces);

  $array = $configStore->toArray();

  expect($array)->toBe([
    'name' => 'test-module',
    'base_path' => '/path/to/module',
    'namespaces' => [
      '/path/to/src' => 'Modules\\TestModule\\',
    ],
  ]);
});
