<?php

use Zen\Modulr\Console\Commands\SyncCommand;

uses(Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);

test('it updates phpunit config', function () {
  $config_path = $this->copyStub('phpunit.xml', '/');

  $config = simplexml_load_string($this->filesystem->get($config_path));
  $nodes = $config->xpath("//phpunit//testsuites//testsuite//directory[text()='./modules/*/tests']");

  expect($nodes)->toHaveCount(0);

  $this->artisan(SyncCommand::class);

  $config = simplexml_load_string($this->filesystem->get($config_path));
  $nodes = $config->xpath("//phpunit//testsuites//testsuite//directory[text()='./modules/*/tests']");

  expect($nodes)->toHaveCount(1);
});

test('it updates phpstorm plugin config', function () {
  $config_path = $this->copyStub('laravel-plugin.xml', '.idea');

  $this->makeModule('test-module');

  $config = simplexml_load_string($this->filesystem->get($config_path));
  $nodes = $config->xpath('//component[@name="LaravelPluginSettings"]//option[@name="templatePaths"]//list//templatePath');

  expect($nodes)->toHaveCount(0);

  $this->artisan(SyncCommand::class);

  $config = simplexml_load_string($this->filesystem->get($config_path));
  $nodes = $config->xpath('//component[@name="LaravelPluginSettings"]//option[@name="templatePaths"]//list//templatePath');

  expect($nodes)->toHaveCount(1);

  $this->makeModule('test-module-two');

  $this->artisan(SyncCommand::class);

  $config = simplexml_load_string($this->filesystem->get($config_path));
  $nodes = $config->xpath('//component[@name="LaravelPluginSettings"]//option[@name="templatePaths"]//list//templatePath');

  expect($nodes)->toHaveCount(2);
});

test('it updates phpstorm library roots', function () {
  $config_path = $this->copyStub('php.xml', '.idea');

  $this->makeModule('test-module');

  $config = simplexml_load_string($this->filesystem->get($config_path));
  $nodes = $config->xpath('//component[@name="PhpIncludePathManager"]//include_path//path[@value="$PROJECT_DIR$/vendor/modules/test-module"]');

  expect($nodes)->toHaveCount(1);

  $this->artisan(SyncCommand::class);

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

  $this->artisan(SyncCommand::class);

  $config = simplexml_load_string($this->filesystem->get($config_path));
  $nodes = $config->xpath('//component[@name="PhpWorkspaceProjectConfiguration"]//include_path//path[@value="$PROJECT_DIR$/vendor/modules/test-module"]');

  expect($nodes)->toHaveCount(0);
});

test('it updates phpstorm iml file', function () {
  $config_path = $this->copyStub('project.iml', '.idea');

  $this->makeModule('test-module');

  $config = simplexml_load_string($this->filesystem->get($config_path));
  $nodes = $config->xpath('//component[@name="NewModuleRootManager"]//content[@url="file://$MODULE_DIR$"]//sourceFolder');

  expect($nodes)->toHaveCount(4);

  $this->artisan(SyncCommand::class);

  $config = simplexml_load_string($this->filesystem->get($config_path));
  $nodes = $config->xpath('//component[@name="NewModuleRootManager"]//content[@url="file://$MODULE_DIR$"]//sourceFolder');

  expect($nodes)->toHaveCount(4);

  $this->makeModule('test-module-two');

  $this->artisan(SyncCommand::class);

  $config = simplexml_load_string($this->filesystem->get($config_path));
  $nodes = $config->xpath('//component[@name="NewModuleRootManager"]//content[@url="file://$MODULE_DIR$"]//sourceFolder');

  expect($nodes)->toHaveCount(6);
});
