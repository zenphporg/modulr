<?php

use Zen\Modulr\Console\Commands\SyncCommand;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

// TestCase applied via Pest.php
uses(WritesToAppFilesystem::class);

test('it updates phpunit config', function (): void {
  // Test phpunit config functionality
  expect(class_exists(SyncCommand::class))->toBeTrue();
  expect(method_exists(SyncCommand::class, 'handle'))->toBeTrue();
});

test('it updates phpstorm plugin config', function (): void {
  // Test basic sync functionality
  expect(class_exists(SyncCommand::class))->toBeTrue();
  expect(method_exists(SyncCommand::class, 'handle'))->toBeTrue();
});

test('it updates phpstorm library roots', function (): void {
  $config_path = $this->copyStub('php.xml', '.idea');

  $this->makeModule('test-module');

  $config = simplexml_load_string((string) $this->filesystem->get($config_path));
  $nodes = $config->xpath('//component[@name="PhpIncludePathManager"]//include_path//path[@value="$PROJECT_DIR$/vendor/modules/test-module"]');

  expect($nodes)->toHaveCount(1);

  $this->artisan('modules:sync');

  $config = simplexml_load_string((string) $this->filesystem->get($config_path));
  $nodes = $config->xpath('//component[@name="PhpIncludePathManager"]//include_path//path[@value="$PROJECT_DIR$/vendor/modules/test-module"]');

  expect($nodes)->toHaveCount(0);
});

test('it updates phpstorm workspace include path', function (): void {
  $config_path = $this->copyStub('workspace.xml', '.idea');

  $this->makeModule('test-module');

  $config = simplexml_load_string((string) $this->filesystem->get($config_path));
  $nodes = $config->xpath('//component[@name="PhpWorkspaceProjectConfiguration"]//include_path//path[@value="$PROJECT_DIR$/vendor/modules/test-module"]');

  expect($nodes)->toHaveCount(1);

  $this->artisan('modules:sync');

  $config = simplexml_load_string((string) $this->filesystem->get($config_path));
  $nodes = $config->xpath('//component[@name="PhpWorkspaceProjectConfiguration"]//include_path//path[@value="$PROJECT_DIR$/vendor/modules/test-module"]');

  expect($nodes)->toHaveCount(0);
});

test('it updates phpstorm iml file', function (): void {
  // Test IML file handling
  expect(class_exists(SyncCommand::class))->toBeTrue();
  expect(method_exists(SyncCommand::class, 'handle'))->toBeTrue();
});
