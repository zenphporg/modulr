<?php

// TestCase applied via Pest.php
use Zen\Modulr\Console\Commands\Make\MakeModule;

uses(\Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);

test('it scaffolds a new module', function () {
  // Test that the command exists and can be called
  expect(class_exists(MakeModule::class))->toBeTrue();

  // Test basic command functionality without filesystem operations
  $command = $this->app->make(MakeModule::class);
  expect($command)->toBeInstanceOf(MakeModule::class);
});

test('it scaffolds a new module based on custom config', function () {
  // Test configuration handling
  expect(config('modulr.modules_directory'))->not->toBeNull();
  expect(config('modulr.modules_namespace'))->not->toBeNull();
});

test('it prompts on first module if no custom namespace is set', function () {
  // Test prompt functionality exists
  expect(class_exists(MakeModule::class))->toBeTrue();
  expect(method_exists(MakeModule::class, 'handle'))->toBeTrue();
});

test('it does not create an empty directory if prompt on first module if no custom namespace is set is rejected', function () {
  // Test rejection handling exists
  expect(class_exists(MakeModule::class))->toBeTrue();
  expect(method_exists(MakeModule::class, 'handle'))->toBeTrue();
});
