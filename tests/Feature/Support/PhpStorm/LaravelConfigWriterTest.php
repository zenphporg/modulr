<?php

use Zen\Modulr\Support\PhpStorm\ConfigWriter;
use Zen\Modulr\Support\PhpStorm\LaravelConfigWriter;
use Zen\Modulr\Support\Registry;

test('it can be instantiated', function (): void {
  $registry = new Registry('/path/to/modules', '');
  $writer = new LaravelConfigWriter('/path/to/config', $registry);

  expect($writer)->toBeInstanceOf(LaravelConfigWriter::class);
});

test('it extends config writer', function (): void {
  $reflection = new ReflectionClass(LaravelConfigWriter::class);
  expect($reflection->getParentClass()->getName())->toBe(ConfigWriter::class);
});

test('it has write method', function (): void {
  expect(method_exists(LaravelConfigWriter::class, 'write'))->toBeTrue();
});

test('it has handle method', function (): void {
  expect(method_exists(LaravelConfigWriter::class, 'handle'))->toBeTrue();
});

test('it can write config with valid xml file', function (): void {
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

test('it can handle method execution', function (): void {
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

test('it throws exception when file cannot be read', function (): void {
  $registry = new Registry('/path/to/modules', '');
  $writer = new LaravelConfigWriter('/non/existent/path.xml', $registry);

  $thrown = false;

  try {
    @$writer->write();
  } catch (RuntimeException $e) {
    $thrown = true;
    expect($e->getMessage())->toContain('Could not read config file');
  }

  expect($thrown)->toBeTrue();
});

test('it throws exception when xml cannot be parsed', function (): void {
  // Create a file with invalid XML
  $tempFile = sys_get_temp_dir().'/invalid_xml_'.uniqid().'.xml';
  file_put_contents($tempFile, 'not valid xml content');

  try {
    $registry = new Registry('/path/to/modules', '');
    $writer = new LaravelConfigWriter($tempFile, $registry);

    $thrown = false;

    try {
      @$writer->write();
    } catch (RuntimeException $e) {
      $thrown = true;
      expect($e->getMessage())->toContain('Could not parse XML');
    }

    expect($thrown)->toBeTrue();
  } finally {
    unlink($tempFile);
  }
});
