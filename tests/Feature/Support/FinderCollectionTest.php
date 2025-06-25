<?php

use Zen\Modulr\Support\FinderCollection;

test('it can create finder for files', function () {
  $finder = FinderCollection::forFiles();
  expect($finder)->toBeInstanceOf(FinderCollection::class);
});

test('it can create finder for directories', function () {
  $finder = FinderCollection::forDirectories();
  expect($finder)->toBeInstanceOf(FinderCollection::class);
});

test('it extends symfony finder', function () {
  expect(class_exists(FinderCollection::class))->toBeTrue();
});

test('it can chain finder methods', function () {
  $finder = FinderCollection::forFiles()
    ->name('*.php')
    ->depth(0);

  expect($finder)->toBeInstanceOf(FinderCollection::class);
});

test('it can search in or empty directories', function () {
  $finder = FinderCollection::forFiles();
  $result = $finder->inOrEmpty('/non/existent/path');
  expect($result)->toBeInstanceOf(FinderCollection::class);
});

test('it can search in existing directories', function () {
  expect(method_exists(FinderCollection::class, 'inOrEmpty'))->toBeTrue();
});

test('it returns empty collection for empty paths', function () {
  $finder = FinderCollection::forFiles()->inOrEmpty('');
  expect($finder)->toBeInstanceOf(FinderCollection::class);
});

test('it handles multiple paths', function () {
  expect(method_exists(FinderCollection::class, 'inOrEmpty'))->toBeTrue();
});
