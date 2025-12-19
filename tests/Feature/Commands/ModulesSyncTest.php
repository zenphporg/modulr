<?php

use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

// TestCase applied via Pest.php
uses(WritesToAppFilesystem::class);

test('it updates phpunit config', function (): void {
  // Create phpunit.xml if it doesn't exist
  $phpunitPath = $this->app->basePath('phpunit.xml');

  if (! $this->filesystem()->exists($phpunitPath)) {
    $this->filesystem()->put($phpunitPath, '<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
  <testsuites>
    <testsuite name="Feature">
      <directory suffix="Test.php">./tests/Feature</directory>
    </testsuite>
  </testsuites>
</phpunit>');
  }

  $this->artisan('modules:sync', ['--no-phpstorm' => true])
    ->assertExitCode(0);
});

test('it updates phpstorm plugin config', function (): void {
  // Create .idea directory and laravel-plugin.xml
  $ideaPath = $this->app->basePath('.idea');
  $this->filesystem()->ensureDirectoryExists($ideaPath);

  $laravelPluginPath = $ideaPath.'/laravel-plugin.xml';
  $this->filesystem()->put($laravelPluginPath, '<?xml version="1.0" encoding="UTF-8"?>
<project version="4">
  <component name="LaravelPluginSettings">
    <option name="templatePaths">
      <list />
    </option>
  </component>
</project>');

  $this->makeModule('test-module');

  $this->artisan('modules:sync')
    ->assertExitCode(0);
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
  // Create .idea directory and a .iml file
  $ideaPath = $this->app->basePath('.idea');
  $this->filesystem()->ensureDirectoryExists($ideaPath);

  $imlPath = $ideaPath.'/project.iml';
  $this->filesystem()->put($imlPath, '<?xml version="1.0" encoding="UTF-8"?>
<module type="WEB_MODULE" version="4">
  <component name="NewModuleRootManager">
    <content url="file://$MODULE_DIR$">
      <sourceFolder url="file://$MODULE_DIR$/app" isTestSource="false" packagePrefix="App\" />
    </content>
  </component>
</module>');

  $this->makeModule('test-module');

  $this->artisan('modules:sync')
    ->assertExitCode(0);
});

test('it handles no phpstorm option', function (): void {
  // Create phpunit.xml if it doesn't exist
  $phpunitPath = $this->app->basePath('phpunit.xml');

  if (! $this->filesystem()->exists($phpunitPath)) {
    $this->filesystem()->put($phpunitPath, '<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
  <testsuites>
    <testsuite name="Feature">
      <directory suffix="Test.php">./tests/Feature</directory>
    </testsuite>
  </testsuites>
</phpunit>');
  }

  $this->artisan('modules:sync', ['--no-phpstorm' => true])
    ->assertExitCode(0);
});

test('it warns when phpunit.xml is missing', function (): void {
  // Delete phpunit.xml if it exists
  $phpunitPath = $this->app->basePath('phpunit.xml');

  if ($this->filesystem()->exists($phpunitPath)) {
    $this->filesystem()->delete($phpunitPath);
  }

  $this->artisan('modules:sync', ['--no-phpstorm' => true])
    ->expectsOutputToContain('No phpunit.xml file found')
    ->assertExitCode(0);
});

test('it adds modules test suite to phpunit.xml', function (): void {
  // Create phpunit.xml without modules test suite
  $phpunitPath = $this->app->basePath('phpunit.xml');

  $this->filesystem()->put($phpunitPath, '<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
  <testsuites>
    <testsuite name="Feature">
      <directory suffix="Test.php">./tests/Feature</directory>
    </testsuite>
  </testsuites>
</phpunit>');

  $this->artisan('modules:sync', ['--no-phpstorm' => true])
    ->expectsOutputToContain('Added "Modules" PHPUnit test suite')
    ->assertExitCode(0);

  // Verify the modules test suite was added
  $content = $this->filesystem()->get($phpunitPath);
  expect($content)->toContain('Modules');
});

test('it handles missing idea directory for iml file', function (): void {
  // Delete .idea directory if it exists
  $ideaPath = $this->app->basePath('.idea');

  if ($this->filesystem()->isDirectory($ideaPath)) {
    $this->filesystem()->deleteDirectory($ideaPath);
  }

  // Create a minimal phpunit.xml
  $phpunitPath = $this->app->basePath('phpunit.xml');
  $this->filesystem()->put($phpunitPath, '<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
  <testsuites>
    <testsuite name="Feature">
      <directory suffix="Test.php">./tests/Feature</directory>
    </testsuite>
  </testsuites>
</phpunit>');

  $this->artisan('modules:sync')
    ->assertExitCode(0);
});

test('it handles invalid phpunit.xml', function (): void {
  // Create an invalid phpunit.xml
  $phpunitPath = $this->app->basePath('phpunit.xml');
  $this->filesystem()->put($phpunitPath, 'not valid xml');

  $this->artisan('modules:sync', ['--no-phpstorm' => true])
    ->expectsOutputToContain('Failed to parse phpunit.xml')
    ->assertExitCode(0);
});

test('it handles phpunit.xml without testsuites node', function (): void {
  // Create phpunit.xml without testsuites
  $phpunitPath = $this->app->basePath('phpunit.xml');
  $this->filesystem()->put($phpunitPath, '<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
</phpunit>');

  $this->artisan('modules:sync', ['--no-phpstorm' => true])
    ->expectsOutputToContain('Cannot find <testsuites> node')
    ->assertExitCode(0);
});

test('it shows verbose output for laravel plugin config errors', function (): void {
  // Create .idea directory with unreadable laravel-plugin.xml
  $ideaPath = $this->app->basePath('.idea');
  $this->filesystem()->ensureDirectoryExists($ideaPath);

  // Create a minimal phpunit.xml
  $phpunitPath = $this->app->basePath('phpunit.xml');
  $this->filesystem()->put($phpunitPath, '<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
  <testsuites>
    <testsuite name="Modules">
      <directory suffix="Test.php">./modules/*/tests</directory>
    </testsuite>
  </testsuites>
</phpunit>');

  $this->makeModule('test-module');

  // Run with verbose flag - no laravel-plugin.xml exists
  $this->artisan('modules:sync', ['-v' => true])
    ->expectsOutputToContain('Did not find/update PhpStorm/Laravel Plugin config')
    ->assertExitCode(0);
});

test('it handles iml file that cannot be updated', function (): void {
  // Create .idea directory with an unreadable iml file
  $ideaPath = $this->app->basePath('.idea');
  $this->filesystem()->ensureDirectoryExists($ideaPath);

  // Create an iml file that is not readable
  $imlPath = $ideaPath.'/project.iml';
  $this->filesystem()->put($imlPath, '<?xml version="1.0" encoding="UTF-8"?>
<module type="WEB_MODULE" version="4">
  <component name="NewModuleRootManager">
    <content url="file://$MODULE_DIR$">
    </content>
  </component>
</module>');

  // Make the file unreadable
  chmod($imlPath, 0000);

  // Create a minimal phpunit.xml
  $phpunitPath = $this->app->basePath('phpunit.xml');
  $this->filesystem()->put($phpunitPath, '<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
  <testsuites>
    <testsuite name="Modules">
      <directory suffix="Test.php">./modules/*/tests</directory>
    </testsuite>
  </testsuites>
</phpunit>');

  $this->makeModule('test-module');

  $this->artisan('modules:sync', ['-v' => true])
    ->expectsOutputToContain('Could not update PhpStorm project source folders')
    ->assertExitCode(0);

  // Restore permissions for cleanup
  chmod($imlPath, 0644);
});
