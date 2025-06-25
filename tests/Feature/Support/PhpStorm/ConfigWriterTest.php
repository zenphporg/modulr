<?php

use Zen\Modulr\Support\PhpStorm\ConfigWriter;

test('it is abstract class', function () {
  $reflection = new ReflectionClass(ConfigWriter::class);

  expect($reflection->isAbstract())->toBeTrue();
});

test('it has required constructor parameters', function () {
  $reflection = new ReflectionClass(ConfigWriter::class);
  $constructor = $reflection->getConstructor();

  expect($constructor)->not->toBeNull();
  expect($constructor->getParameters())->toHaveCount(2);

  $params = $constructor->getParameters();
  expect($params[0]->getName())->toBe('config_path');
  expect($params[1]->getName())->toBe('module_registry');
});

test('it has abstract write method', function () {
  $reflection = new ReflectionClass(ConfigWriter::class);

  expect($reflection->hasMethod('write'))->toBeTrue();

  $writeMethod = $reflection->getMethod('write');
  expect($writeMethod->isAbstract())->toBeTrue();
});

test('it has handle method', function () {
  $reflection = new ReflectionClass(ConfigWriter::class);

  expect($reflection->hasMethod('handle'))->toBeTrue();

  $handleMethod = $reflection->getMethod('handle');
  expect($handleMethod->isPublic())->toBeTrue();
  expect($handleMethod->isAbstract())->toBeFalse();
});

test('it has protected helper methods', function () {
  $reflection = new ReflectionClass(ConfigWriter::class);

  expect($reflection->hasMethod('handle'))->toBeTrue();
});
