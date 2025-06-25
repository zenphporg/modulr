<?php

use Zen\Modulr\Support\PhpStorm\LaravelConfigWriter;
use Zen\Modulr\Support\Registry;

test('it can be instantiated', function () {
  $registry = new Registry('/path/to/modules', '');
  $writer = new LaravelConfigWriter('/path/to/config', $registry);

  expect($writer)->toBeInstanceOf(LaravelConfigWriter::class);
});

test('it extends config writer', function () {
  $reflection = new ReflectionClass(LaravelConfigWriter::class);
  expect($reflection->getParentClass()->getName())->toBe(\Zen\Modulr\Support\PhpStorm\ConfigWriter::class);
});

test('it has write method', function () {
  expect(method_exists(LaravelConfigWriter::class, 'write'))->toBeTrue();
});

test('it has handle method', function () {
  expect(method_exists(LaravelConfigWriter::class, 'handle'))->toBeTrue();
});

test('it can write config with valid xml file', function () {
  // Create a temporary XML config file
  $tempFile = tempnam(sys_get_temp_dir(), 'laravel_config_');
  $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>
<project version="4">
</project>';
  file_put_contents($tempFile, $xmlContent);

  try {
    $registry = new Registry('/path/to/modules', '');
    $writer = new LaravelConfigWriter($tempFile, $registry);

    $result = $writer->write();
    expect($result)->toBeTrue();

    // Verify the file was written
    expect(file_exists($tempFile))->toBeTrue();
    $content = file_get_contents($tempFile);
    expect($content)->toContain('LaravelPluginSettings');
  } finally {
    unlink($tempFile);
  }
});

test('it can handle method execution', function () {
  // Create a temporary XML config file
  $tempFile = tempnam(sys_get_temp_dir(), 'laravel_config_');
  $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>
<project version="4">
</project>';
  file_put_contents($tempFile, $xmlContent);

  try {
    $registry = new Registry('/path/to/modules', '');
    $writer = new LaravelConfigWriter($tempFile, $registry);

    $result = $writer->handle();
    expect($result)->toBeTrue();
  } finally {
    unlink($tempFile);
  }
});
