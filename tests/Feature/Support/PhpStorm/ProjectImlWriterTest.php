<?php

use Zen\Modulr\Support\PhpStorm\ProjectImlWriter;
use Zen\Modulr\Support\Registry;

test('it can be instantiated', function () {
  $registry = new Registry('/path/to/modules', '');
  $writer = new ProjectImlWriter('/path/to/config', $registry);

  expect($writer)->toBeInstanceOf(ProjectImlWriter::class);
});

test('it extends config writer', function () {
  $reflection = new ReflectionClass(ProjectImlWriter::class);
  expect($reflection->getParentClass()->getName())->toBe(\Zen\Modulr\Support\PhpStorm\ConfigWriter::class);
});

test('it has write method', function () {
  expect(method_exists(ProjectImlWriter::class, 'write'))->toBeTrue();
});

test('it has handle method', function () {
  expect(method_exists(ProjectImlWriter::class, 'handle'))->toBeTrue();
});

test('it can write iml config with valid xml file', function () {
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

test('it can handle method execution', function () {
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
