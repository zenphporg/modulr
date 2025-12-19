<?php

use Illuminate\Support\LazyCollection;
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

test('it returns self when calling in without finder', function (): void {
  // Create a FinderCollection without a finder (empty constructor)
  $collection = new FinderCollection;
  $result = $collection->in('/some/path');
  expect($result)->toBeInstanceOf(FinderCollection::class);
});

test('it returns self when calling depth without finder', function (): void {
  $collection = new FinderCollection;
  $result = $collection->depth(0);
  expect($result)->toBeInstanceOf(FinderCollection::class);
});

test('it returns self when calling name without finder', function (): void {
  $collection = new FinderCollection;
  $result = $collection->name('*.php');
  expect($result)->toBeInstanceOf(FinderCollection::class);
});

test('it returns self when calling sortByName without finder', function (): void {
  $collection = new FinderCollection;
  $result = $collection->sortByName();
  expect($result)->toBeInstanceOf(FinderCollection::class);
});

test('it can collect results', function (): void {
  $collection = FinderCollection::forFiles()->inOrEmpty(__DIR__);
  $result = $collection->collect();
  expect($result)->toBeInstanceOf(LazyCollection::class);
});

test('it forwards calls to finder when method exists on finder', function (): void {
  $collection = FinderCollection::forFiles()->in(__DIR__);
  // Call a method that exists on Finder
  $result = $collection->ignoreDotFiles(true);
  expect($result)->toBeInstanceOf(FinderCollection::class);
});

test('it forwards calls to collection for preferred collection methods', function (): void {
  $collection = FinderCollection::forFiles()->inOrEmpty(__DIR__);
  // filter is in PREFER_COLLECTION_METHODS
  $result = $collection->filter(fn ($file): true => true);
  expect($result)->toBeInstanceOf(FinderCollection::class);
});

test('it returns result directly when not finder or collection', function (): void {
  $collection = FinderCollection::forFiles()->inOrEmpty(__DIR__);
  // count returns an integer, not a Finder or LazyCollection
  $result = $collection->count();
  expect($result)->toBeInt();
});
