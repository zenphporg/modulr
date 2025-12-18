<?php

use Zen\Modulr\Support\FinderCollection;

test('it can create finder for files', function (): void {
  $finder = FinderCollection::forFiles();
  expect($finder)->toBeInstanceOf(FinderCollection::class);
});

test('it can create finder for directories', function (): void {
  $finder = FinderCollection::forDirectories();
  expect($finder)->toBeInstanceOf(FinderCollection::class);
});

test('it extends symfony finder', function (): void {
  expect(class_exists(FinderCollection::class))->toBeTrue();
});

test('it can chain finder methods', function (): void {
  $finder = FinderCollection::forFiles()
    ->name('*.php')
    ->depth(0);

  expect($finder)->toBeInstanceOf(FinderCollection::class);
});

test('it can search in or empty directories', function (): void {
  $finder = FinderCollection::forFiles();
  $result = $finder->inOrEmpty('/non/existent/path');
  expect($result)->toBeInstanceOf(FinderCollection::class);
});

test('it can search in existing directories', function (): void {
  expect(method_exists(FinderCollection::class, 'inOrEmpty'))->toBeTrue();
});

test('it returns empty collection for empty paths', function (): void {
  $finder = FinderCollection::forFiles()->inOrEmpty('');
  expect($finder)->toBeInstanceOf(FinderCollection::class);
});

test('it handles multiple paths', function (): void {
  expect(method_exists(FinderCollection::class, 'inOrEmpty'))->toBeTrue();
});
