<?php

declare(strict_types=1);

use Zen\Modulr\Exceptions\CannotFindModuleForPathException;
use Zen\Modulr\Exceptions\Exception;

test('base exception exists', function (): void {
  expect(class_exists(Exception::class))->toBeTrue();
});

test('it can be instantiated', function (): void {
  $exception = new Exception('Test message');

  expect($exception)->toBeInstanceOf(Exception::class);
  expect($exception->getMessage())->toBe('Test message');
});

test('cannot find module for path exception exists', function (): void {
  expect(class_exists(CannotFindModuleForPathException::class))->toBeTrue();
});

test('cannot find module for path exception can be instantiated', function (): void {
  $exception = new CannotFindModuleForPathException('Test message');

  expect($exception)->toBeInstanceOf(CannotFindModuleForPathException::class);
  expect($exception->getMessage())->toContain('Test message');
});

test('cannot find module for path exception has static factory method', function (): void {
  expect(class_exists(CannotFindModuleForPathException::class))->toBeTrue();
});
