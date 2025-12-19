<?php

use Symfony\Component\Console\Input\ArrayInput;
use Zen\Modulr\Console\Commands\RemoveCommand;
use Zen\Modulr\Support\Registry;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

// TestCase applied via Pest.php
uses(WritesToAppFilesystem::class);

test('it removes a module when given a name', function (): void {
  $this->makeModule('test-module');
  $this->makeModule('test-module-two');

  /** @var Registry $registry */
  $registry = $this->app->make(Registry::class);
  $registry->reload();

  expect($registry->modules())->toHaveCount(2);

  $modulePath = $registry->module('test-module')?->base_path;
  expect($modulePath)->not->toBeNull();
  expect($modulePath)->toBeDirectory();

  $this->artisan('modules:remove', ['name' => 'test-module'])
    ->assertSuccessful();

  $registry->reload();

  expect($registry->modules())->toHaveCount(1);
  expect($registry->module('test-module'))->toBeNull();
  expect($registry->module('test-module-two'))->not->toBeNull();
  $this->assertDirectoryDoesNotExist($modulePath);
});

test('it returns error when module does not exist', function (): void {
  $this->makeModule('test-module');

  $this->artisan('modules:remove', ['name' => 'non-existent-module'])
    ->assertFailed()
    ->expectsOutput("Module 'non-existent-module' not found.");
});

test('it returns error when no modules exist', function (): void {
  $this->artisan('modules:remove')
    ->assertFailed()
    ->expectsOutput('No modules found.');
});

test('it returns error when no name provided during tests', function (): void {
  $this->makeModule('test-module');

  $this->artisan('modules:remove')
    ->assertFailed()
    ->expectsOutput('Module name is required.');
});

test('it removes module with force flag', function (): void {
  $this->makeModule('test-module');

  /** @var Registry $registry */
  $registry = $this->app->make(Registry::class);
  $registry->reload();

  $modulePath = $registry->module('test-module')?->base_path;

  $this->artisan('modules:remove', ['name' => 'test-module', '--force' => true])
    ->assertSuccessful()
    ->expectsOutput("Module 'test-module' has been removed.");

  $this->assertDirectoryDoesNotExist($modulePath);
});

test('it adds gitkeep when removing last module', function (): void {
  $this->makeModule('test-module');

  /** @var Registry $registry */
  $registry = $this->app->make(Registry::class);
  $registry->reload();

  $modulePath = $registry->module('test-module')?->base_path;
  $modulesDir = dirname((string) $modulePath);

  $this->artisan('modules:remove', ['name' => 'test-module', '--force' => true])
    ->assertSuccessful();

  expect($modulesDir.'/.gitkeep')->toBeFile();
});

test('it falls back to config vendor when composer name not found', function (): void {
  $this->makeModule('test-module');

  /** @var Registry $registry */
  $registry = $this->app->make(Registry::class);
  $registry->reload();

  // Delete the composer.json to force fallback
  $module = $registry->module('test-module');
  $composerPath = $module->base_path.'/composer.json';
  unlink($composerPath);

  $this->artisan('modules:remove', ['name' => 'test-module', '--force' => true])
    ->assertSuccessful();
});

test('it removes require entry from composer json', function (): void {
  $this->makeModule('test-module');

  /** @var Registry $registry */
  $registry = $this->app->make(Registry::class);
  $registry->reload();

  // Add a require entry to composer.json
  $composerPath = $this->getApplicationBasePath().'/composer.json';
  $composerData = json_decode(file_get_contents($composerPath), true);
  $composerData['require']['modules/test-module'] = '*';
  file_put_contents($composerPath, json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

  $this->artisan('modules:remove', ['name' => 'test-module', '--force' => true])
    ->assertSuccessful();

  // Verify the require entry was removed
  $composerData = json_decode(file_get_contents($composerPath), true);
  expect($composerData['require'])->not->toHaveKey('modules/test-module');
});

test('it removes path repository when removing last module', function (): void {
  $this->makeModule('test-module');

  /** @var Registry $registry */
  $registry = $this->app->make(Registry::class);
  $registry->reload();

  // Verify this is the only module
  expect($registry->modules())->toHaveCount(1);

  // Add a path repository to composer.json
  $composerPath = $this->getApplicationBasePath().'/composer.json';
  $composerData = json_decode(file_get_contents($composerPath), true);
  $composerData['repositories'] = [
    ['type' => 'path', 'url' => 'modules/*'],
  ];
  file_put_contents($composerPath, json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

  // Verify the path matches what the app will use
  expect($composerPath)->toBe($this->app->basePath('composer.json'));

  $this->artisan('modules:remove', ['name' => 'test-module', '--force' => true])
    ->assertSuccessful();

  // Verify the repository was removed (empty array becomes unset)
  $composerData = json_decode(file_get_contents($composerPath), true);
  $hasRepositories = isset($composerData['repositories']) && ! empty($composerData['repositories']);
  expect($hasRepositories)->toBeFalse();
});

test('it handles associative repositories array', function (): void {
  $this->makeModule('test-module');

  /** @var Registry $registry */
  $registry = $this->app->make(Registry::class);
  $registry->reload();

  // Add an associative repositories array to composer.json
  $composerPath = $this->getApplicationBasePath().'/composer.json';
  $composerData = json_decode(file_get_contents($composerPath), true);
  $composerData['repositories'] = [
    'modules' => ['type' => 'path', 'url' => 'modules/*'],
    'other' => ['type' => 'vcs', 'url' => 'https://github.com/example/repo'],
  ];
  file_put_contents($composerPath, json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

  $this->artisan('modules:remove', ['name' => 'test-module', '--force' => true])
    ->assertSuccessful();

  // Verify the modules repository was removed but other remains with its key preserved
  $composerData = json_decode(file_get_contents($composerPath), true);
  expect($composerData['repositories'])->toHaveKey('other');
});

test('it reports nothing to update when composer has no changes', function (): void {
  $this->makeModule('test-module');
  $this->makeModule('test-module-two');

  /** @var Registry $registry */
  $registry = $this->app->make(Registry::class);
  $registry->reload();

  // Ensure composer.json has no module-related entries
  $composerPath = $this->getApplicationBasePath().'/composer.json';
  $composerData = json_decode(file_get_contents($composerPath), true);
  unset($composerData['require']['modules/test-module']);
  unset($composerData['repositories']);
  file_put_contents($composerPath, json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

  $this->artisan('modules:remove', ['name' => 'test-module', '--force' => true])
    ->assertSuccessful()
    ->expectsOutputToContain('Nothing to update in composer.json');
});

test('it preserves non-module repositories when removing last module', function (): void {
  $this->makeModule('test-module');

  /** @var Registry $registry */
  $registry = $this->app->make(Registry::class);
  $registry->reload();

  // Add multiple repositories including non-module ones
  $composerPath = $this->getApplicationBasePath().'/composer.json';
  $composerData = json_decode(file_get_contents($composerPath), true);
  $composerData['repositories'] = [
    ['type' => 'path', 'url' => 'modules/*'],
    ['type' => 'vcs', 'url' => 'https://github.com/example/repo'],
  ];
  file_put_contents($composerPath, json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

  $this->artisan('modules:remove', ['name' => 'test-module', '--force' => true])
    ->assertSuccessful();

  // Verify the modules repository was removed but other remains
  $composerData = json_decode(file_get_contents($composerPath), true);
  expect($composerData['repositories'])->toHaveCount(1);
  expect($composerData['repositories'][0]['url'])->toBe('https://github.com/example/repo');
});

test('it can get module name from argument', function (): void {
  $this->makeModule('test-module');

  $command = $this->app->make(RemoveCommand::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  // Set up input with name argument
  $input = new ArrayInput(['name' => 'test-module'], $command->getDefinition());
  $inputProperty = $reflection->getProperty('input');
  $inputProperty->setValue($command, $input);

  $method = $reflection->getMethod('getModuleName');
  $result = $method->invoke($command);

  expect($result)->toBe('test-module');
});

test('it can confirm removal with force option', function (): void {
  $command = $this->app->make(RemoveCommand::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  // Set up input with force option
  $input = new ArrayInput(['--force' => true], $command->getDefinition());
  $inputProperty = $reflection->getProperty('input');
  $inputProperty->setValue($command, $input);

  $method = $reflection->getMethod('confirmRemoval');
  $result = $method->invoke($command);

  expect($result)->toBeTrue();
});

test('it can confirm removal in unit tests', function (): void {
  $command = $this->app->make(RemoveCommand::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  // Set up input without force option
  $input = new ArrayInput([], $command->getDefinition());
  $inputProperty = $reflection->getProperty('input');
  $inputProperty->setValue($command, $input);

  $method = $reflection->getMethod('confirmRemoval');
  $result = $method->invoke($command);

  // In unit tests, confirmRemoval returns true
  expect($result)->toBeTrue();
});

test('it can run composer update method', function (): void {
  $command = $this->app->make(RemoveCommand::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $method = $reflection->getMethod('runComposerUpdate');

  // Should return early in unit tests without error
  $method->invoke($command);

  expect(true)->toBeTrue();
});
