<?php

namespace Zen\Modulr\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Encryption\Encrypter;
use Zen\Modulr\Console\Commands\Make\MakeModule;
use Zen\Modulr\ModulrServiceProvider;
use Zen\Modulr\Support\ConfigStore;
use Zen\Modulr\Support\DatabaseFactoryHelper;
use Zen\Modulr\Support\Facades\Modulr;
use Zen\Modulr\Support\Registry;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
  protected function setUp(): void
  {
    // Create the Laravel app structure BEFORE calling parent::setUp()
    $this->scaffoldLaravelApp();

    parent::setUp();

    $config = $this->app->make(Repository::class);

    // Add encryption key for HTTP tests
    $config->set('app.key', 'base64:'.base64_encode(Encrypter::generateKey('AES-128-CBC')));

    // Set modules directory for tests (absolute path to our local directory)
    $modulesPath = __DIR__.'/app/modules';
    $config->set('modulr.modules_directory', $modulesPath);

    // Ensure the modules directory exists
    if (! is_dir($modulesPath)) {
      mkdir($modulesPath, 0755, true);
    }

    // Override the Registry binding to use our local modules path
    $this->app->singleton(fn (): Registry => new Registry(
      $modulesPath,
      $this->app->bootstrapPath('cache/modules.php')
    ));

    // Reload the module registry
    $this->app->make(Registry::class)->reload();

    // Re-register the factory resolvers with the new Registry instance
    $databaseFactoryHelper = new DatabaseFactoryHelper($this->app->make(Registry::class));
    Factory::guessModelNamesUsing($databaseFactoryHelper->modelNameResolver());
    Factory::guessFactoryNamesUsing($databaseFactoryHelper->factoryNameResolver());

    // Configure view paths for our test app
    $config->set('view.paths', [__DIR__.'/app/resources/views']);
  }

  protected function tearDown(): void
  {
    $this->app->make(DatabaseFactoryHelper::class)->resetResolvers();

    parent::tearDown();
  }

  protected function makeModule(string $name = 'test-module'): ConfigStore
  {
    $this->artisan(MakeModule::class, [
      'name' => $name,
      '--accept-namespace' => true,
    ]);

    // Reload registry to pick up the newly created module
    $this->app->make(Registry::class)->reload();

    return $this->app->make(Registry::class)->module($name);
  }

  protected function requiresLaravelVersion(string $minimum_version, string $operator = '>=')
  {
    if (! version_compare($this->app->version(), $minimum_version, $operator)) {
      $this->markTestSkipped("Only applies to Laravel {$operator} {$minimum_version}.");
    }

    return $this;
  }

  protected function getPackageProviders($app)
  {
    return [
      ModulrServiceProvider::class,
    ];
  }

  protected function getPackageAliases($app)
  {
    return [
      'Modulr' => Modulr::class,
    ];
  }

  protected function defineEnvironment($app)
  {
    $config = $app->make(Repository::class);

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

    return $this;
  }

  /**
   * Scaffold a complete Laravel app structure for testing.
   */
  protected function scaffoldLaravelApp(): void
  {
    $appPath = __DIR__.'/app';

    // Create the necessary Laravel directory structure
    $directories = [
      $appPath,
      $appPath.'/bootstrap',
      $appPath.'/bootstrap/cache',
      $appPath.'/config',
      $appPath.'/database',
      $appPath.'/database/factories',
      $appPath.'/database/migrations',
      $appPath.'/database/seeders',
      $appPath.'/modules',
      $appPath.'/resources',
      $appPath.'/resources/views',
      $appPath.'/storage',
      $appPath.'/storage/app',
      $appPath.'/storage/framework',
      $appPath.'/storage/framework/cache',
      $appPath.'/storage/framework/sessions',
      $appPath.'/storage/framework/views',
      $appPath.'/storage/logs',
    ];

    foreach ($directories as $directory) {
      if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
      }
    }

    // Create a basic composer.json file
    $composerJson = [
      'name' => 'test/laravel-app',
      'type' => 'project',
      'require' => [
        'php' => '^8.3',
        'laravel/framework' => '^12.0',
      ],
      'autoload' => [
        'psr-4' => [
          'App\\' => 'app/',
          'Database\\Factories\\' => 'database/factories/',
          'Database\\Seeders\\' => 'database/seeders/',
        ],
      ],
    ];

    $composerPath = $appPath.'/composer.json';
    if (! file_exists($composerPath)) {
      file_put_contents($composerPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
  }

  /**
   * Override getModulePath to use our local modules directory.
   * This ensures the WritesToAppFilesystem concern uses the same path as our Registry.
   */
  protected function getModulePath(string $module_name, string $path = '/', string $modules_root = 'modules'): string
  {
    $modulesPath = __DIR__.'/app/modules';
    $normalizedPath = ($path = trim($path, '/')) && (substr($path, 1, 1) !== ':') ? '/'.$path : $path;

    return $modulesPath.'/'.$module_name.$normalizedPath;
  }

  /**
   * Provide applicationBasePath for the WritesToAppFilesystem trait.
   * This ensures the trait uses our test app directory.
   */
  public static function applicationBasePath(): string
  {
    return __DIR__.'/app';
  }
}
