<?php

use Illuminate\Console\OutputStyle;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Zen\Modulr\Console\Commands\InstallCommand;
use Zen\Modulr\Support\Registry;

beforeEach(function () {
  $this->installer = new ReflectionClass(InstallCommand::class);
});

afterEach(function () {
  Mockery::close();
  $this->installer = null;
});

it('sets up terminal styles correctly', function () {
  $filesystem = Mockery::mock(Filesystem::class);
  $moduleRegistry = Mockery::mock(Registry::class);
  $command = new InstallCommand($filesystem, $moduleRegistry);

  $formatter = new OutputFormatter;
  $output = Mockery::mock(OutputStyle::class);
  $output->shouldReceive('getFormatter')->andReturn($formatter);
  $command->setOutput($output);

  $reflection = new ReflectionClass(InstallCommand::class);
  $method = $reflection->getMethod('setUpStyles');
  $method->setAccessible(true);

  $method->invoke($command);

  expect($formatter->hasStyle('kbd'))->toBeTrue();
});

it('handles the command execution', function () {
  // TODO:
  expect(true)->toBeTrue();
});

it('installs a composer package', function () {
  // TODO:
  expect(true)->toBeTrue();
});

it('updates the module composer file', function () {
  // TODO:
  expect(true)->toBeTrue();
});

it('ensures the modules directory exists', function () {
  // TODO:
  expect(true)->toBeTrue();
});

it('updates the application composer file', function () {
  // TODO:
  expect(true)->toBeTrue();
});

it('creates a title in the terminal', function () {
  // TODO:
  expect(true)->toBeTrue();
});

it('creates a new line in the terminal', function () {
  // TODO:
  expect(true)->toBeTrue();
});
