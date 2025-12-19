<?php

use Zen\Modulr\Support\PhpStorm\ConfigWriter;
use Zen\Modulr\Support\PhpStorm\PhpFrameworkWriter;
use Zen\Modulr\Support\Registry;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(WritesToAppFilesystem::class);

test('it can be instantiated', function (): void {
  $registry = $this->app->make(Registry::class);
  $writer = new PhpFrameworkWriter('/tmp/test.xml', $registry);
  expect($writer)->toBeInstanceOf(PhpFrameworkWriter::class);
});

test('it extends config writer', function (): void {
  $reflection = new ReflectionClass(PhpFrameworkWriter::class);
  expect($reflection->getParentClass()->getName())->toBe(ConfigWriter::class);
});

test('it has write method', function (): void {
  $reflection = new ReflectionClass(PhpFrameworkWriter::class);
  expect($reflection->hasMethod('write'))->toBeTrue();
});

test('it has handle method', function (): void {
  $reflection = new ReflectionClass(PhpFrameworkWriter::class);
  expect($reflection->hasMethod('handle'))->toBeTrue();
});

test('it throws exception when file cannot be read', function (): void {
  $registry = $this->app->make(Registry::class);
  $writer = new PhpFrameworkWriter('/non/existent/path.xml', $registry);

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
    $registry = $this->app->make(Registry::class);
    $writer = new PhpFrameworkWriter($tempFile, $registry);

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

test('it returns true when no include paths found', function (): void {
  // Create a valid XML file without include paths
  $tempFile = sys_get_temp_dir().'/no_paths_'.uniqid().'.xml';
  file_put_contents($tempFile, '<?xml version="1.0" encoding="UTF-8"?>
<project version="4">
  <component name="SomeOtherComponent">
  </component>
</project>');

  try {
    $registry = $this->app->make(Registry::class);
    $writer = new PhpFrameworkWriter($tempFile, $registry);

    expect($writer->write())->toBeTrue();
  } finally {
    unlink($tempFile);
  }
});

test('it can write config with valid xml file', function (): void {
  $config_path = $this->copyStub('php.xml', '.idea');

  $this->makeModule('test-module');

  $registry = $this->app->make(Registry::class);
  $writer = new PhpFrameworkWriter($config_path, $registry);

  expect($writer->write())->toBeTrue();
});

test('it can handle method execution', function (): void {
  $config_path = $this->copyStub('php.xml', '.idea');

  $this->makeModule('test-module');

  $registry = $this->app->make(Registry::class);
  $writer = new PhpFrameworkWriter($config_path, $registry);

  expect($writer->handle())->toBeTrue();
});
