<?php

namespace Zen\Modulr\Console\Commands\Make;

use Composer\Factory;
use Composer\Json\JsonFile;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Terminal;
use Zen\Modulr\Console\Commands\ClearCommand;
use Zen\Modulr\Support\Registry;

class MakeModule extends Command
{
  /**
   * @var string
   */
  protected $signature = 'modules:make
		{name : The name of the module}
		{--accept-namespace : Skip default namespace confirmation}
    {--empty : Create an empty module directory and namespace}';

  /**
   * @var string
   */
  protected $description = 'Create a new Laravel module';

  /**
   * This is the base path of the module
   */
  protected string $base_path;

  /**
   * This is the PHP namespace for all modules
   */
  protected string $module_namespace;

  /**
   * This is the composer namespace for all modules
   */
  protected string $composer_namespace;

  /**
   * This is the name of the module
   */
  protected string $module_name;

  /**
   * This is the module name as a StudlyCased name
   */
  protected string $class_name_prefix;

  /**
   * This is the name of the module as a composer package
   * i.e. modules/my-module
   */
  protected string $composer_name;

  protected Filesystem $filesystem;

  protected Registry $module_registry;

  public function __construct(Filesystem $filesystem, Registry $module_registry)
  {
    parent::__construct();

    $this->filesystem = $filesystem;
    $this->module_registry = $module_registry;
  }

  /**
   * @throws \Seld\JsonLint\ParsingException
   */
  public function handle(): int
  {
    $this->module_name = Str::kebab($this->argument('name'));
    $this->class_name_prefix = Str::studly($this->argument('name'));
    $this->module_namespace = config('modulr.modules_namespace', 'Modules');
    $this->composer_namespace = config('modulr.modules_vendor') ?? Str::kebab($this->module_namespace);
    $this->composer_name = "$this->composer_namespace/$this->module_name";
    $this->base_path = $this->module_registry->getModulesPath().'/'.$this->module_name;

    $this->setUpStyles();

    $this->newLine();

    $this->ensureModulesDirectoryExists();

    if ($this->shouldAbortToPublishConfig()) {
      return 0;
    }

    if ($this->option('empty')) {
      $this->ensureModulesDirectoryExists();
      $this->updateCoreComposerConfig();

      $this->call(ClearCommand::class);

      return 0;
    } else {
      $this->writeStubs();
    }

    $this->updateCoreComposerConfig();

    $this->call(ClearCommand::class);

    $this->newLine();
    $this->line("Please run <kbd>composer update $this->composer_name</kbd>");
    $this->newLine();

    $this->module_registry->reload();

    return 0;
  }

  protected function shouldAbortToPublishConfig(): bool
  {
    if (
      $this->module_namespace !== 'Modules'
      || $this->option('accept-namespace') === true
      || $this->module_registry->modules()->isNotEmpty()
    ) {
      return false;
    }

    $this->title('Welcome');

    $message = "You're about to create your first module in the <info>$this->module_namespace</info> "
        .'namespace. This is the default namespace, and will work for many use-cases. However, '
        .'if you ever choose to extract a module into its own package, you will '
        ."likely want to use a custom namespace (like your organization name).\n\n"
        .'If you would like to use a custom namespace, please publish the config '
        ."and customize it first. You can do this by calling:\n\n"
        .'<kbd>php artisan vendor:publish --tag=modular-config</kbd>';

    $width = min((new Terminal)->getWidth(), 100) - 1;
    $messages = explode(PHP_EOL, wordwrap($message, $width, PHP_EOL));
    foreach ($messages as $message) {
      $this->line(" $message");
    }

    return $this->confirm('Would you like to cancel and configure your module namespace first?', true);
  }

  /**
   * @return void
   */
  protected function ensureModulesDirectoryExists()
  {
    if (! $this->filesystem->isDirectory($this->base_path)) {
      $this->filesystem->makeDirectory($this->base_path, 0777, true);
      $this->line(" - Created <info>$this->base_path</info>");
    }
  }

  /**
   * @return void
   */
  protected function writeStubs()
  {
    $this->title('Creating initial module files');

    $tests_base = config('modulr.tests_base', 'Tests\TestCase');

    $placeholders = [
      'StubBasePath' => $this->base_path,
      'StubModuleNamespace' => $this->module_namespace,
      'StubComposerNamespace' => $this->composer_namespace,
      'StubModuleNameSingular' => Str::singular($this->module_name),
      'StubModuleNamePlural' => Str::plural($this->module_name),
      'StubModuleName' => $this->module_name,
      'StubClassNamePrefix' => $this->class_name_prefix,
      'StubComposerName' => $this->composer_name,
      'StubMigrationPrefix' => date('Y_m_d_His'),
      'StubFullyQualifiedTestCaseBase' => $tests_base,
      'StubTestCaseBase' => class_basename($tests_base),
    ];

    $search = array_keys($placeholders);
    $replace = array_values($placeholders);

    foreach ($this->getStubs() as $destination => $stub_file) {
      $contents = file_get_contents($stub_file);
      $destination = str_replace($search, $replace, $destination);
      $filename = "$this->base_path/$destination";

      $output = str_replace($search, $replace, $contents);

      if ($this->filesystem->exists($filename)) {
        $this->line(" - Skipping <info>$destination</info> (already exists)");

        continue;
      }

      $this->filesystem->ensureDirectoryExists($this->filesystem->dirname($filename));
      $this->filesystem->put($filename, $output);

      $this->line(" - Wrote to <info>$destination</info>");
    }

    $this->newLine();
  }

  protected function seedersDirectory(): string
  {
    return version_compare($this->getLaravel()->version(), '8.0.0', '>=')
        ? 'seeders'
        : 'seeds';
  }

  /**
   * @return void
   *
   * @throws \Seld\JsonLint\ParsingException
   * @throws \Exception
   */
  protected function updateCoreComposerConfig()
  {
    $this->title('Updating application composer.json file');

    // We're going to move into the Laravel base directory while
    // we're updating the composer file so that we're sure we update
    // the correct composer.json file (we'll restore CWD at the end)
    $original_working_dir = getcwd();
    chdir($this->laravel->basePath());

    $json_file = new JsonFile(Factory::getComposerFile());
    $definition = $json_file->read();

    if (! isset($definition['repositories'])) {
      $definition['repositories'] = [];
    }

    if (! isset($definition['require'])) {
      $definition['require'] = [];
    }

    $module_config = [
      'type' => 'path',
      'url' => str_replace('\\', '/', config('modulr.modules_directory', 'modules')).'/*',
      'options' => [
        'symlink' => true,
      ],
    ];

    $has_changes = false;

    $repository_already_exists = collect($definition['repositories'])
      ->contains(function (array $repository) use ($module_config): bool {
        return $repository['url'] === $module_config['url'];
      });

    if ($repository_already_exists === false) {
      $this->line(" - Adding path repository for <info>{$module_config['url']}</info>");
      $has_changes = true;

      if (Arr::isAssoc($definition['repositories'])) {
        $definition['repositories'][$this->module_name] = $module_config;
      } else {
        $definition['repositories'][] = $module_config;
      }
    }

    if (! isset($definition['require'][$this->composer_name])) {
      $this->line(" - Adding require statement for <info>$this->composer_name:*</info>");
      $has_changes = true;

      $definition['require']["$this->composer_namespace/$this->module_name"] = '*';
      $definition['require'] = $this->sortComposerPackages($definition['require']);
    }

    if ($has_changes) {
      $json_file->write($definition);
      $this->line(" - Wrote to <info>{$json_file->getPath()}</info>");
    } else {
      $this->line(' - Nothing to update (repository & require entry already exist)');
    }

    chdir($original_working_dir);

    $this->newLine();
  }

  protected function sortComposerPackages(array $packages): array
  {
    $prefix = function ($requirement): array|string|null {
      return preg_replace(
        [
          '/^php$/',
          '/^hhvm-/',
          '/^ext-/',
          '/^lib-/',
          '/^\D/',
          '/^(?!php$|hhvm-|ext-|lib-)/',
        ],
        [
          '0-$0',
          '1-$0',
          '2-$0',
          '3-$0',
          '4-$0',
          '5-$0',
        ],
        $requirement
      );
    };

    uksort($packages, function ($a, $b) use ($prefix): int {
      return strnatcmp($prefix($a), $prefix($b));
    });

    return $packages;
  }

  /**
   * @return void
   */
  protected function setUpStyles()
  {
    $formatter = $this->getOutput()->getFormatter();

    if (! $formatter->hasStyle('kbd')) {
      $formatter->setStyle('kbd', new OutputFormatterStyle('cyan'));
    }
  }

  protected function title(string $title): void
  {
    $this->getOutput()->title($title);
  }

  /**
   * @param  int  $count
   * @return void
   */
  public function newLine($count = 1)
  {
    $this->getOutput()->newLine($count);
  }

  protected function getStubs(): array
  {
    if (is_array($custom_stubs = config('modulr.stubs'))) {
      return $custom_stubs;
    }

    $composer_stub = version_compare($this->getLaravel()->version(), '8.0.0', '<')
        ? 'composer-stub-v7.json'
        : 'composer-stub-latest.json';

    return [
      'composer.json' => $this->pathToStub($composer_stub),
      'src/Providers/StubClassNamePrefixServiceProvider.php' => $this->pathToStub('ServiceProvider.php'),
      'tests/StubClassNamePrefixServiceProviderTest.php' => $this->pathToStub('ServiceProviderTest.php'),
      'database/migrations/StubMigrationPrefix_set_up_StubModuleName_module.php' => $this->pathToStub('migration.php'),
      'routes/StubModuleName-routes.php' => $this->pathToStub('web-routes.php'),
      'resources/views/index.blade.php' => $this->pathToStub('view.blade.php'),
      'resources/views/create.blade.php' => $this->pathToStub('view.blade.php'),
      'resources/views/show.blade.php' => $this->pathToStub('view.blade.php'),
      'resources/views/edit.blade.php' => $this->pathToStub('view.blade.php'),
      'database/factories/.gitkeep' => $this->pathToStub('.gitkeep'),
      'database/migrations/.gitkeep' => $this->pathToStub('.gitkeep'),
      'database/'.$this->seedersDirectory().'/.gitkeep' => $this->pathToStub('.gitkeep'),
    ];
  }

  protected function pathToStub($filename): string
  {
    return str_replace('\\', '/', dirname(__DIR__, 4))."/stubs/$filename";
  }
}
