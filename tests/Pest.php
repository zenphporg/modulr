<?php

use Illuminate\Filesystem\Filesystem;
use Zen\Modulr\ModulrServiceProvider;
use Zen\Modulr\Support\Facades\Modulr;
use Zen\Modulr\Tests\TestCase;

/**
 * TEST CASE
 *
 * The closure you provide to your test functions is always bound to a specific PHPUnit test
 * case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
 * need to change it using the "pest()" function to bind a different classes or traits.
 */
pest()->extend(TestCase::class)->in('Feature');

/**
 * EXPECTATIONS
 *
 * When you're writing tests, you often need to check that values meet certain conditions. The
 * "expect()" function gives you access to a set of "expectations" methods that you can use
 * to assert different things. Of course, you may extend the Expectation API at any time.
 */
expect()->extend('toBeOne', fn () => $this->toBe(1));

/**
 * SHARED PACKAGE PROVIDERS
 *
 * Base package providers that most tests need
 */
function getPackageProviders($app): array
{
  return [
    ModulrServiceProvider::class,
  ];
}

/**
 * SHARED PACKAGE ALIASES
 */
function getPackageAliases($app): array
{
  return [
    'Modulr' => Modulr::class,
  ];
}

/**
 * SHARED ENVIRONMENT CONFIGURATION
 */
function defineEnvironment(array $app): array
{
  $config = $app['config'];

  $config->set('modulr.modules_directory', 'modules');
  $config->set('modulr.modules_namespace', 'Modules');

  // Configure view paths for our test app
  $config->set('view.paths', [__DIR__.'/app/resources/views']);

  // Configure auth for policy tests
  $config->set('auth.defaults.guard', 'web');
  $config->set('auth.guards.web', [
    'driver' => 'session',
    'provider' => 'users',
  ]);
  $config->set('auth.providers.users', [
    'driver' => 'eloquent',
    'model' => 'App\Models\User',
  ]);

  return $app;
}

/**
 * SHARED APPLICATION CONFIGURATION
 */
function resolveApplicationConfiguration(array $app): void
{
  defineEnvironment($app);
}

/**
 * HELPER FUNCTIONS
 */
function makeModule(string $name = 'test-module')
{
  return test()->makeModule($name);
}

function requiresLaravelVersion(string $minimum_version, string $operator = '>=')
{
  return test()->requiresLaravelVersion($minimum_version, $operator);
}

/**
 * GLOBAL TEARDOWN
 *
 * Clean up the tests/app directory after all tests complete
 */
register_shutdown_function(function (): void {
  $testsAppDir = __DIR__.'/app';

  if (is_dir($testsAppDir)) {
    try {
      $filesystem = new Filesystem;
      $filesystem->deleteDirectory($testsAppDir);

      // Only show message if we're in a terminal (not during CI/automated runs)
      if (php_sapi_name() === 'cli' && isset($_SERVER['TERM'])) {
        echo "\n✓ Cleaned up tests/app directory\n";
      }
    } catch (Exception $e) {
      // Silently fail - don't break the test run over cleanup issues
      error_log("Could not clean up tests/app directory: {$e->getMessage()}");
    }
  }
});
