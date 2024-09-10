<?php

namespace Zen\Modulr;

use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Factory as ViewFactory;
use ReflectionClass;
use Symfony\Component\Finder\SplFileInfo;
use Zen\Modulr\Console\Commands\CacheCommand;
use Zen\Modulr\Console\Commands\ClearCommand;
use Zen\Modulr\Console\Commands\InstallCommand;
use Zen\Modulr\Console\Commands\ListCommand;
use Zen\Modulr\Console\Commands\Make\MakeMigration;
use Zen\Modulr\Console\Commands\Make\MakeModule;
use Zen\Modulr\Console\Commands\SyncCommand;
use Zen\Modulr\Providers\CommandsServiceProvider;
use Zen\Modulr\Providers\EventServiceProvider;
use Zen\Modulr\Support\AutoDiscoveryHelper;
use Zen\Modulr\Support\DatabaseFactoryHelper;
use Zen\Modulr\Support\Registry;

class ModulrServiceProvider extends ServiceProvider
{
  protected ?Registry $registry = null;

  protected ?AutoDiscoveryHelper $auto_discovery_helper = null;

  protected string $base_dir;

  protected ?string $modules_path = null;

  /**
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
  public function register(): void
  {
    $this->mergeConfigFrom(dirname(__DIR__).'/config/modulr.php', 'modulr');

    App::macro('modulePath', function ($path = '') {
      $moduleDirectory = config('modulr.modules_directory', 'src');

      return $this->app->basePath($moduleDirectory.($path ? "/$path" : ''));
    });

    $this->app->register(CommandsServiceProvider::class);
    $this->app->register(EventServiceProvider::class);

    $this->app->singleton(Registry::class, function (): Registry {
      return new Registry(
        $this->getModulesBasePath(),
        $this->app->bootstrapPath('cache/modules.php')
      );
    });

    $this->app->singleton(AutoDiscoveryHelper::class);

    $this->app->singleton(MakeMigration::class, function (array $app): MigrateMakeCommand {
      return new MigrateMakeCommand($app['migration.creator'], $app['composer']);
    });

    $this->registerEloquentFactories();

    // Set up lazy registrations for things that only need to run if we're using
    // that functionality (e.g. we only need to look for and register migrations
    // if we're running the migrator)
    $this->registerLazily(Migrator::class, [$this, 'registerMigrations']);
    $this->registerLazily(Gate::class, [$this, 'registerPolicies']);

    // Look for and register all our commands in the CLI context
    Artisan::starting($this->registerCommands(...));
  }

  /**
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   * @throws \Zen\Modulr\Exceptions\CannotFindModuleForPathException
   */
  public function boot(): void
  {
    $this->publishVendorFiles();
    $this->bootPackageCommands();

    $this->bootRoutes();
    $this->bootViews();
    $this->bootBladeComponents();
    $this->bootTranslations();
  }

  /**
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
  protected function registry(): Registry
  {
    return $this->registry ??= $this->app->make(Registry::class);
  }

  /**
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
  protected function autoDiscoveryHelper(): AutoDiscoveryHelper
  {
    return $this->auto_discovery_helper ??= $this->app->make(AutoDiscoveryHelper::class);
  }

  protected function publishVendorFiles(): void
  {
    $this->publishes([
      dirname(__DIR__).'/config/modulr.php' => $this->app->configPath('modulr.php'),
    ], 'modulr-config');
  }

  protected function bootPackageCommands(): void
  {
    if (! $this->app->runningInConsole()) {
      return;
    }

    $this->commands([
      MakeModule::class,
      CacheCommand::class,
      ClearCommand::class,
      InstallCommand::class,
      SyncCommand::class,
      ListCommand::class,
    ]);
  }

  /**
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
  protected function bootRoutes(): void
  {
    if ($this->app->routesAreCached()) {
      return;
    }

    $this->autoDiscoveryHelper()
      ->routeFileFinder()
      ->each(function (SplFileInfo $file): void {
        require $file->getRealPath();
      });
  }

  /**
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   * @throws \Zen\Modulr\Exceptions\CannotFindModuleForPathException
   */
  protected function bootViews(): void
  {
    $this->callAfterResolving('view', function (ViewFactory $view_factory): void {
      $this->autoDiscoveryHelper()
        ->viewDirectoryFinder()
        ->each(function (SplFileInfo $directory) use ($view_factory): void {
          $module = $this->registry()->moduleForPathOrFail($directory->getPath());
          $view_factory->addNamespace($module->name, $directory->getRealPath());
        });
    });
  }

  /**
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   * @throws \Zen\Modulr\Exceptions\CannotFindModuleForPathException
   */
  protected function bootBladeComponents(): void
  {
    $this->callAfterResolving(BladeCompiler::class, function (BladeCompiler $blade): void {
      // Boot individual Blade components (old syntax: `<x-module-* />`)
      $this->autoDiscoveryHelper()
        ->bladeComponentFileFinder()
        ->each(function (SplFileInfo $component) use ($blade): void {
          $module = $this->registry()->moduleForPathOrFail($component->getPath());
          $fully_qualified_component = $module->pathToFullyQualifiedClassName($component->getPathname());
          $blade->component($fully_qualified_component, null, $module->name);
        });

      // Boot Blade component namespaces (new syntax: `<x-module::* />`)
      $this->autoDiscoveryHelper()
        ->bladeComponentDirectoryFinder()
        ->each(function (SplFileInfo $component) use ($blade): void {
          $module = $this->registry()->moduleForPathOrFail($component->getPath());
          $blade->componentNamespace($module->qualify('View\\Components'), $module->name);
        });
    });
  }

  /**
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   * @throws \Zen\Modulr\Exceptions\CannotFindModuleForPathException
   */
  protected function bootTranslations(): void
  {
    $this->callAfterResolving('translator', function (TranslatorContract $translator): void {

      $this->autoDiscoveryHelper()
        ->langDirectoryFinder()
        ->each(function (SplFileInfo $directory) use ($translator): void {
          $module = $this->registry()->moduleForPathOrFail($directory->getPath());
          $path = $directory->getRealPath();

          $translator->addNamespace($module->name, $path);
          $translator->addJsonPath($path);
        });
    });
  }

  /**
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
  protected function registerMigrations(Migrator $migrator): void
  {
    $this->autoDiscoveryHelper()
      ->migrationDirectoryFinder()
      ->each(function (SplFileInfo $path) use ($migrator): void {
        $migrator->path($path->getRealPath());
      });
  }

  /**
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
  protected function registerEloquentFactories(): void
  {
    $helper = new DatabaseFactoryHelper($this->registry());

    EloquentFactory::guessModelNamesUsing($helper->modelNameResolver());
    EloquentFactory::guessFactoryNamesUsing($helper->factoryNameResolver());
  }

  /**
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   * @throws \Zen\Modulr\Exceptions\CannotFindModuleForPathException
   */
  protected function registerPolicies(Gate $gate): void
  {
    $this->autoDiscoveryHelper()
      ->modelFileFinder()
      ->each(function (SplFileInfo $file) use ($gate): void {
        $module = $this->registry()->moduleForPathOrFail($file->getPath());
        $fully_qualified_model = $module->pathToFullyQualifiedClassName($file->getPathname());

        // First, check for a policy that maps to the full namespace of the model
        // i.e. Models/Foo/Bar -> Policies/Foo/BarPolicy
        $namespaced_model = Str::after($fully_qualified_model, 'Models\\');
        $namespaced_policy = rtrim($module->namespaces->first(), '\\').'\\Policies\\'.$namespaced_model.'Policy';
        if (class_exists($namespaced_policy)) {
          $gate->policy($fully_qualified_model, $namespaced_policy);
        }

        // If that doesn't match, try the simple mapping as well
        // i.e. Models/Foo/Bar -> Policies/BarPolicy
        if (str_contains($namespaced_model, '\\')) {
          $simple_model = Str::afterLast($fully_qualified_model, '\\');
          $simple_policy = rtrim($module->namespaces->first(), '\\').'\\Policies\\'.$simple_model.'Policy';

          if (class_exists($simple_policy)) {
            $gate->policy($fully_qualified_model, $simple_policy);
          }
        }
      });
  }

  /**
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   * @throws \Zen\Modulr\Exceptions\CannotFindModuleForPathException
   */
  protected function registerCommands(Artisan $artisan): void
  {
    $this->autoDiscoveryHelper()
      ->commandFileFinder()
      ->each(function (SplFileInfo $file) use ($artisan): void {
        $module = $this->registry()->moduleForPathOrFail($file->getPath());
        $class_name = $module->pathToFullyQualifiedClassName($file->getPathname());
        if ($this->isInstantiableCommand($class_name)) {
          $artisan->resolve($class_name);
        }
      });
  }

  /**
   * @return $this
   */
  protected function registerLazily(string $class_name, callable $callback): self
  {
    $this->app->resolving($class_name, $callback(...));

    return $this;
  }

  /**
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
  protected function getModulesBasePath(): string
  {
    if ($this->modules_path === null) {
      $directory_name = $this->app->make('config')->get('modulr.modules_directory', 'modules');
      $this->modules_path = str_replace('\\', '/', $this->app->basePath($directory_name));
    }

    return $this->modules_path;
  }

  protected function isInstantiableCommand($command): bool
  {
    return is_subclass_of($command, Command::class)
        && ! (new ReflectionClass($command))->isAbstract();
  }
}
