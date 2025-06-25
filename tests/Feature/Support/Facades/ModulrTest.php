<?php

use Zen\Modulr\Support\Facades\Modulr;
use Zen\Modulr\Support\Registry;

test('it is a facade', function () {
  expect(class_exists(Modulr::class))->toBeTrue();
});

test('it returns correct facade accessor', function () {
  $reflection = new ReflectionClass(Modulr::class);
  $method = $reflection->getMethod('getFacadeAccessor');

  $accessor = $method->invoke(null);

  expect($accessor)->toBe(Registry::class);
});

test('it can access registry methods through facade', function () {
  $registry = $this->app->make(Registry::class);

  expect(Modulr::getModulesPath())->toBe($registry->getModulesPath());
  expect(Modulr::modules())->toEqual($registry->modules());
});

test('it can call module method through facade', function () {
  $result = Modulr::module('non-existent-module');

  expect($result)->toBeNull();
});

test('it can call moduleForPath method through facade', function () {
  $result = Modulr::moduleForPath('/some/path');

  expect($result)->toBeNull();
});

test('it can call moduleForClass method through facade', function () {
  $result = Modulr::moduleForClass('SomeClass');

  expect($result)->toBeNull();
});

test('it can call reload method through facade', function () {
  expect(class_exists(Modulr::class))->toBeTrue();
});
