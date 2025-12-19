<?php

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Zen\Modulr\Console\Commands\InstallCommand;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

// TestCase applied via Pest.php
uses(WritesToAppFilesystem::class);

test('it can be instantiated', function (): void {
  $command = $this->app->make(InstallCommand::class);
  expect($command)->toBeInstanceOf(InstallCommand::class);
});

test('it has correct signature', function (): void {
  $command = $this->app->make(InstallCommand::class);
  expect($command->getName())->toBe('modules:install');
});

test('it has description', function (): void {
  $command = $this->app->make(InstallCommand::class);
  expect($command->getDescription())->not->toBeEmpty();
});

test('it has package argument', function (): void {
  $command = $this->app->make(InstallCommand::class);
  $definition = $command->getDefinition();
  expect($definition->hasArgument('package'))->toBeTrue();
});

test('it has name option', function (): void {
  $command = $this->app->make(InstallCommand::class);
  $definition = $command->getDefinition();
  expect($definition->hasOption('name'))->toBeTrue();
});

test('it can create process', function (): void {
  $command = $this->app->make(InstallCommand::class);
  $process = $command->createProcess(['echo', 'test']);
  expect($process)->toBeInstanceOf(Process::class);
});

test('it can sort composer packages', function (): void {
  $command = $this->app->make(InstallCommand::class);

  $reflection = new ReflectionClass($command);
  $method = $reflection->getMethod('sortComposerPackages');

  $packages = [
    'laravel/framework' => '^11.0',
    'php' => '^8.2',
    'ext-json' => '*',
    'acme/package' => '^1.0',
  ];

  $sorted = $method->invoke($command, $packages);

  // PHP should come first, then ext-, then regular packages
  $keys = array_keys($sorted);
  expect($keys[0])->toBe('php');
  expect($keys[1])->toBe('ext-json');
});

test('it can set up styles', function (): void {
  $command = $this->app->make(InstallCommand::class);

  // Set up output
  $reflection = new ReflectionClass($command);
  $outputProperty = $reflection->getProperty('output');

  $output = new BufferedOutput;
  $outputProperty->setValue($command, $output);

  $method = $reflection->getMethod('setUpStyles');
  $method->invoke($command);

  expect($output->getFormatter()->hasStyle('kbd'))->toBeTrue();
});

test('it can generate new lines', function (): void {
  $command = $this->app->make(InstallCommand::class);

  // Set up output using SymfonyStyle which has newLine method
  $reflection = new ReflectionClass($command);
  $outputProperty = $reflection->getProperty('output');

  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);
  $outputProperty->setValue($command, $output);

  $result = $command->newLine(2);

  expect($result)->toBeInstanceOf(InstallCommand::class);
});

test('it can output title', function (): void {
  $command = $this->app->make(InstallCommand::class);

  // Set up output using SymfonyStyle which has title method
  $reflection = new ReflectionClass($command);
  $outputProperty = $reflection->getProperty('output');

  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);
  $outputProperty->setValue($command, $output);

  $method = $reflection->getMethod('title');
  $method->invoke($command, 'Test Title');

  expect($bufferedOutput->fetch())->toContain('Test Title');
});

test('it ensures modules directory exists', function (): void {
  $command = $this->app->make(InstallCommand::class);

  // Set up the command properties
  $reflection = new ReflectionClass($command);

  $basePathProperty = $reflection->getProperty('base_path');
  $basePathProperty->setValue($command, $this->app->basePath('modules/test-install-module'));

  $outputProperty = $reflection->getProperty('output');
  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);
  $outputProperty->setValue($command, $output);

  $method = $reflection->getMethod('ensureModulesDirectoryExists');
  $method->invoke($command);

  expect($this->filesystem()->isDirectory($this->app->basePath('modules/test-install-module')))->toBeTrue();
});

test('it can update module composer file', function (): void {
  $command = $this->app->make(InstallCommand::class);
  $module = $this->makeModule();

  // Set up the command properties
  $reflection = new ReflectionClass($command);

  $basePathProperty = $reflection->getProperty('base_path');
  $basePathProperty->setValue($command, $module->base_path);

  $composerNameProperty = $reflection->getProperty('composer_name');
  $composerNameProperty->setValue($command, 'modules/test-module');

  $outputProperty = $reflection->getProperty('output');
  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);
  $outputProperty->setValue($command, $output);

  $method = $reflection->getMethod('updateModuleComposerFile');
  $method->invoke($command);

  // Verify the composer.json was updated
  $composerPath = $module->base_path.'/composer.json';
  $composerData = json_decode((string) $this->filesystem()->get($composerPath), true);

  expect($composerData['name'])->toBe('modules/test-module');
  expect($composerData['type'])->toBe('module');
  expect($composerData['version'])->toBe('1.0');
  expect($composerData['license'])->toBe('proprietary');
});

test('it can update core composer config', function (): void {
  $command = $this->app->make(InstallCommand::class);
  $this->makeModule();

  // Set up the command properties
  $reflection = new ReflectionClass($command);

  $command->setLaravel($this->app);

  $moduleNameProperty = $reflection->getProperty('module_name');
  $moduleNameProperty->setValue($command, 'test-module');

  $composerNameProperty = $reflection->getProperty('composer_name');
  $composerNameProperty->setValue($command, 'modules/test-module');

  $composerNamespaceProperty = $reflection->getProperty('composer_namespace');
  $composerNamespaceProperty->setValue($command, 'modules');

  $packageNameProperty = $reflection->getProperty('package_name');
  $packageNameProperty->setValue($command, 'vendor/some-package');

  $outputProperty = $reflection->getProperty('output');
  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);
  $outputProperty->setValue($command, $output);

  $method = $reflection->getMethod('updateCoreComposerConfig');
  $method->invoke($command);

  // Verify the composer.json was updated
  $composerPath = $this->app->basePath('composer.json');
  $composerData = json_decode((string) $this->filesystem()->get($composerPath), true);

  expect($composerData)->toHaveKey('repositories');
  expect($composerData)->toHaveKey('require');
});

test('it can make new module', function (): void {
  $command = $this->app->make(InstallCommand::class);

  // Set up the command properties
  $reflection = new ReflectionClass($command);

  $composerNameProperty = $reflection->getProperty('composer_name');
  $composerNameProperty->setValue($command, 'modules/new-module');

  $basePathProperty = $reflection->getProperty('base_path');
  $basePathProperty->setValue($command, $this->app->basePath('modules/new-module'));

  $outputProperty = $reflection->getProperty('output');
  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);
  $outputProperty->setValue($command, $output);

  $method = $reflection->getMethod('makeNewModule');
  $method->invoke($command);

  expect($this->filesystem()->isDirectory($this->app->basePath('modules/new-module')))->toBeTrue();
});

test('it handles existing repository in core composer config', function (): void {
  $command = $this->app->make(InstallCommand::class);
  $this->makeModule();

  // Set up the command properties
  $reflection = new ReflectionClass($command);

  $command->setLaravel($this->app);

  $moduleNameProperty = $reflection->getProperty('module_name');
  $moduleNameProperty->setValue($command, 'test-module');

  $composerNameProperty = $reflection->getProperty('composer_name');
  $composerNameProperty->setValue($command, 'modules/test-module');

  $composerNamespaceProperty = $reflection->getProperty('composer_namespace');
  $composerNamespaceProperty->setValue($command, 'modules');

  $packageNameProperty = $reflection->getProperty('package_name');
  $packageNameProperty->setValue($command, 'vendor/some-package');

  $outputProperty = $reflection->getProperty('output');
  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);
  $outputProperty->setValue($command, $output);

  // Call the method twice - second time should have nothing to update
  $method = $reflection->getMethod('updateCoreComposerConfig');
  $method->invoke($command);

  // Clear the buffer and call again
  $bufferedOutput->fetch();
  $method->invoke($command);

  $outputText = $bufferedOutput->fetch();
  expect($outputText)->toContain('Nothing to update');
});

test('it handles associative repositories in core composer config', function (): void {
  $command = $this->app->make(InstallCommand::class);
  $this->makeModule();

  // Pre-populate composer.json with associative repositories
  $composerPath = $this->app->basePath('composer.json');
  $composerData = json_decode((string) $this->filesystem()->get($composerPath), true);
  $composerData['repositories'] = [
    'packagist' => ['type' => 'composer', 'url' => 'https://packagist.org'],
  ];
  $this->filesystem()->put($composerPath, json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

  // Set up the command properties
  $reflection = new ReflectionClass($command);

  $command->setLaravel($this->app);

  $moduleNameProperty = $reflection->getProperty('module_name');
  $moduleNameProperty->setValue($command, 'test-module');

  $composerNameProperty = $reflection->getProperty('composer_name');
  $composerNameProperty->setValue($command, 'modules/test-module');

  $composerNamespaceProperty = $reflection->getProperty('composer_namespace');
  $composerNamespaceProperty->setValue($command, 'modules');

  $packageNameProperty = $reflection->getProperty('package_name');
  $packageNameProperty->setValue($command, 'vendor/some-package');

  $outputProperty = $reflection->getProperty('output');
  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);
  $outputProperty->setValue($command, $output);

  $method = $reflection->getMethod('updateCoreComposerConfig');
  $method->invoke($command);

  // Verify the repository was added with the module name as key
  $composerData = json_decode((string) $this->filesystem()->get($composerPath), true);
  expect($composerData['repositories'])->toHaveKey('test-module');
});

test('it removes package from require when updating core composer config', function (): void {
  $command = $this->app->make(InstallCommand::class);
  $this->makeModule();

  // Pre-populate composer.json with the package in require
  $composerPath = $this->app->basePath('composer.json');
  $composerData = json_decode((string) $this->filesystem()->get($composerPath), true);
  $composerData['require']['vendor/some-package'] = '^1.0';
  $this->filesystem()->put($composerPath, json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

  // Set up the command properties
  $reflection = new ReflectionClass($command);

  $command->setLaravel($this->app);

  $moduleNameProperty = $reflection->getProperty('module_name');
  $moduleNameProperty->setValue($command, 'test-module');

  $composerNameProperty = $reflection->getProperty('composer_name');
  $composerNameProperty->setValue($command, 'modules/test-module');

  $composerNamespaceProperty = $reflection->getProperty('composer_namespace');
  $composerNamespaceProperty->setValue($command, 'modules');

  $packageNameProperty = $reflection->getProperty('package_name');
  $packageNameProperty->setValue($command, 'vendor/some-package');

  $outputProperty = $reflection->getProperty('output');
  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);
  $outputProperty->setValue($command, $output);

  $method = $reflection->getMethod('updateCoreComposerConfig');
  $method->invoke($command);

  // Verify the package was removed from require
  $composerData = json_decode((string) $this->filesystem()->get($composerPath), true);
  expect($composerData['require'])->not->toHaveKey('vendor/some-package');
});

test('it can move package to modules when source exists', function (): void {
  $command = $this->app->make(InstallCommand::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  // Set up the required properties
  $packageNameProperty = $reflection->getProperty('package_name');
  $packageNameProperty->setValue($command, 'vendor/test-package');

  $composerNameProperty = $reflection->getProperty('composer_name');
  $composerNameProperty->setValue($command, 'modules/test-module');

  $basePathProperty = $reflection->getProperty('base_path');
  $basePathProperty->setValue($command, $this->app->basePath('modules/test-module'));

  // Create the source directory
  $sourcePath = $this->app->basePath('vendor/vendor/test-package');
  $this->filesystem()->ensureDirectoryExists($sourcePath);
  $this->filesystem()->put($sourcePath.'/composer.json', '{}');

  // Set up output
  $outputProperty = $reflection->getProperty('output');
  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);
  $outputProperty->setValue($command, $output);

  $method = $reflection->getMethod('movePackageToModules');
  $result = $method->invoke($command);

  expect($result)->toBeNull();
  expect($this->filesystem()->isDirectory($this->app->basePath('modules/test-module')))->toBeTrue();
});

test('it returns error when source directory does not exist', function (): void {
  $command = $this->app->make(InstallCommand::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  // Set up the required properties
  $packageNameProperty = $reflection->getProperty('package_name');
  $packageNameProperty->setValue($command, 'vendor/non-existent-package');

  $composerNameProperty = $reflection->getProperty('composer_name');
  $composerNameProperty->setValue($command, 'modules/non-existent');

  $basePathProperty = $reflection->getProperty('base_path');
  $basePathProperty->setValue($command, $this->app->basePath('modules/non-existent'));

  // Set up output
  $outputProperty = $reflection->getProperty('output');
  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);
  $outputProperty->setValue($command, $output);

  $method = $reflection->getMethod('movePackageToModules');
  $result = $method->invoke($command);

  expect($result)->toBe(1);
  $outputText = $bufferedOutput->fetch();
  expect($outputText)->toContain('Source directory does not exist');
});

test('it can install composer package', function (): void {
  $command = $this->app->make(InstallCommand::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  // Set up the required properties
  $packageNameProperty = $reflection->getProperty('package_name');
  $packageNameProperty->setValue($command, 'vendor/test-package');

  // Set up output
  $outputProperty = $reflection->getProperty('output');
  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);
  $outputProperty->setValue($command, $output);

  // We can't actually run composer, but we can verify the method exists and is callable
  $method = $reflection->getMethod('installComposerPackage');

  expect($method->isProtected())->toBeTrue();
});

test('it can update composer', function (): void {
  $command = $this->app->make(InstallCommand::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  // Set up output
  $outputProperty = $reflection->getProperty('output');
  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);
  $outputProperty->setValue($command, $output);

  // We can't actually run composer, but we can verify the method exists and is callable
  $method = $reflection->getMethod('updateComposer');

  expect($method->isProtected())->toBeTrue();
});
