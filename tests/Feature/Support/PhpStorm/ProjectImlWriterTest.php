<?php

use Zen\Modulr\Support\PhpStorm\ConfigWriter;
use Zen\Modulr\Support\PhpStorm\ProjectImlWriter;
use Zen\Modulr\Support\Registry;

test('it can be instantiated', function (): void {
  $registry = new Registry('/path/to/modules', '');
  $writer = new ProjectImlWriter('/path/to/config', $registry);

  expect($writer)->toBeInstanceOf(ProjectImlWriter::class);
});

test('it extends config writer', function (): void {
  $reflection = new ReflectionClass(ProjectImlWriter::class);
  expect($reflection->getParentClass()->getName())->toBe(ConfigWriter::class);
});

test('it has write method', function (): void {
  expect(method_exists(ProjectImlWriter::class, 'write'))->toBeTrue();
});

test('it has handle method', function (): void {
  expect(method_exists(ProjectImlWriter::class, 'handle'))->toBeTrue();
});

test('it can write iml config with valid xml file', function (): void {
  // Create a temporary XML config file
  $tempFile = tempnam(sys_get_temp_dir(), 'project_iml_');
  $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>
<module type="WEB_MODULE" version="4">
</module>';
  file_put_contents($tempFile, $xmlContent);

  try {
    $registry = new Registry('/path/to/modules', '');
    $writer = new ProjectImlWriter($tempFile, $registry);

    $result = $writer->write();
    expect($result)->toBeTrue();

    // Verify the file was written
    expect(file_exists($tempFile))->toBeTrue();
    $content = file_get_contents($tempFile);
    expect($content)->toContain('NewModuleRootManager');
  } finally {
    unlink($tempFile);
  }
});

test('it can handle method execution', function (): void {
  // Create a temporary XML config file
  $tempFile = tempnam(sys_get_temp_dir(), 'project_iml_');
  $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>
<module type="WEB_MODULE" version="4">
</module>';
  file_put_contents($tempFile, $xmlContent);

  try {
    $registry = new Registry('/path/to/modules', '');
    $writer = new ProjectImlWriter($tempFile, $registry);

    $result = $writer->handle();
    expect($result)->toBeTrue();
  } finally {
    unlink($tempFile);
  }
});
