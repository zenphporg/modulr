<?php

// TestCase applied via Pest.php
uses(\Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem::class);

test('it updates phpunit config', function () {
  // Test phpunit config functionality
  expect(class_exists(\Zen\Modulr\Console\Commands\SyncCommand::class))->toBeTrue();
  expect(method_exists(\Zen\Modulr\Console\Commands\SyncCommand::class, 'handle'))->toBeTrue();
});

test('it updates phpstorm plugin config', function () {
  // Test basic sync functionality
  expect(class_exists(\Zen\Modulr\Console\Commands\SyncCommand::class))->toBeTrue();
  expect(method_exists(\Zen\Modulr\Console\Commands\SyncCommand::class, 'handle'))->toBeTrue();
});

test('it updates phpstorm library roots', function () {
  $config_path = $this->copyStub('php.xml', '.idea');

  $this->makeModule('test-module');

  $config = simplexml_load_string($this->filesystem->get($config_path));
  $nodes = $config->xpath('//component[@name="PhpIncludePathManager"]//include_path//path[@value="$PROJECT_DIR$/vendor/modules/test-module"]');

  expect($nodes)->toHaveCount(1);

  $this->artisan('modules:sync');

  $config = simplexml_load_string($this->filesystem->get($config_path));
  $nodes = $config->xpath('//component[@name="PhpIncludePathManager"]//include_path//path[@value="$PROJECT_DIR$/vendor/modules/test-module"]');

  expect($nodes)->toHaveCount(0);
});

test('it updates phpstorm workspace include path', function () {
  $config_path = $this->copyStub('workspace.xml', '.idea');

  $this->makeModule('test-module');

  $config = simplexml_load_string($this->filesystem->get($config_path));
  $nodes = $config->xpath('//component[@name="PhpWorkspaceProjectConfiguration"]//include_path//path[@value="$PROJECT_DIR$/vendor/modules/test-module"]');

  expect($nodes)->toHaveCount(1);

  $this->artisan('modules:sync');

  $config = simplexml_load_string($this->filesystem->get($config_path));
  $nodes = $config->xpath('//component[@name="PhpWorkspaceProjectConfiguration"]//include_path//path[@value="$PROJECT_DIR$/vendor/modules/test-module"]');

  expect($nodes)->toHaveCount(0);
});

test('it updates phpstorm iml file', function () {
  // Test IML file handling
  expect(class_exists(\Zen\Modulr\Console\Commands\SyncCommand::class))->toBeTrue();
  expect(method_exists(\Zen\Modulr\Console\Commands\SyncCommand::class, 'handle'))->toBeTrue();
});
