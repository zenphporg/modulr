<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands;

use Composer\Factory;
use Composer\Json\JsonFile;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Seld\JsonLint\ParsingException;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Process\Process;
use Zen\Modulr\Support\Registry;

class InstallCommand extends Command
{
  protected string $base_path;

  protected string $module_namespace;

  protected string $composer_namespace;

  protected string $module_name;

  protected string $package_name;

  protected string $composer_name;

  /**
   * @var string
   */
  protected $signature = 'modules:install {package : Composer package to modulize} {--name= : Optional module name}';

  /**
   * @var string
   */
  protected $description = 'Install a composer package as a module.';

  public function __construct(
    protected Filesystem $filesystem,
    protected Registry $module_registry
  ) {
    parent::__construct();
  }

  /**
   * Run our command function.
   *
   *
   * @throws ParsingException
   *
   * @codeCoverageIgnore
   */
  public function handle(): int
  {
    /** @var string $packageName */
    $packageName = $this->argument('package');
    $this->package_name = $packageName;

    /** @var string|true $nameOption */
    $nameOption = $this->option('name');
    $this->module_name = is_string($nameOption) ? $nameOption : basename($this->package_name);

    /** @var string $moduleNamespace */
    $moduleNamespace = config('modulr.modules_namespace', 'Modules');
    $this->module_namespace = $moduleNamespace;

    /** @var string|null $modulesVendor */
    $modulesVendor = config('modulr.modules_vendor');
    $this->composer_namespace = $modulesVendor ?? Str::kebab($this->module_namespace);
    $this->composer_name = "$this->composer_namespace/$this->module_name";
    $this->base_path = $this->module_registry->getModulesPath().'/'.$this->module_name;

    $this->setUpStyles();

    $this->installComposerPackage();
    $this->makeNewModule();
    $this->movePackageToModules();
    $this->updateModuleComposerFile();
    $this->updateCoreComposerConfig();
    $this->updateComposer();

    $this->module_registry->reload();

    $this->info('Package installed successfully as a module.');

    return 0;
  }

  /**
   * Fetch the composer package requested.
   *
   * @codeCoverageIgnore
   */
  protected function installComposerPackage(): void
  {
    $this->title("Running composer to require package: $this->package_name");

    $process = $this->createProcess(['composer', 'require', $this->package_name]);
    $process->setWorkingDirectory(base_path());
    $process->setTimeout(3600);

    $progressBar = $this->output->createProgressBar(100);
    $progressBar->start();

    $process->run(function () use ($progressBar): void {
      $progressBar->advance();
    });

    $progressBar->finish();

    $this->newLine(2);

    $process->isSuccessful()
      ? $this->line(" - $this->package_name was installed successfully.")
      : $this->error(' - The composer command failed.');

    $this->newLine();
  }

  /**
   * Make a new module for the requested package.
   */
  protected function makeNewModule(): void
  {
    $this->title("Installing new $this->composer_name module");

    $this->ensureModulesDirectoryExists();
  }

  /**
   * Copy all the package contents to the module.
   */
  protected function movePackageToModules(): ?int
  {
    $this->title("Migrating $this->composer_name to it's new module");

    $source = base_path('vendor/'.$this->package_name);

    if (! File::exists($source)) {
      $this->error(" - Source directory does not exist: $source");

      return 1;
    }

    File::ensureDirectoryExists($this->base_path);

    File::copyDirectory($source, $this->base_path);

    $this->filesystem->delete($this->module_registry->getCachePath());

    $this->line(' - Module cache cleared!');

    $this->newLine();

    $this->line(" - Migration of $this->package_name completed successfully.");

    $this->newLine();

    return null;
  }

  /**
   * Update composer to finalize.
   *
   * @codeCoverageIgnore
   */
  protected function updateComposer(): void
  {
    $this->title('Updating composer to finalize module install');

    $process = $this->createProcess(['composer', 'update']);
    $process->setWorkingDirectory(base_path());
    $process->setTimeout(3600);

    $progressBar = $this->output->createProgressBar(100);
    $progressBar->start();

    $process->run(function () use ($progressBar): void {
      $progressBar->advance();
    });

    $progressBar->finish();

    $this->newLine(2);

    $process->isSuccessful()
      ? $this->line(' - Composer updated successfully.')
      : $this->error(' - The composer command failed.');

    $this->newLine();
  }

  /**
   * Update our module composer file.
   *
   * @throws ParsingException
   * @throws Exception
   */
  protected function updateModuleComposerFile(): void
  {
    $this->title("Updating $this->composer_name composer.json file");

    $file = $this->base_path.'/'.'composer.json';

    $jsonFile = new JsonFile($file);
    /** @var array<string, mixed> $definition */
    $definition = $jsonFile->read();

    $keys = ['name', 'type', 'version', 'license', 'keywords', 'support', 'authors'];
    $definition = Arr::except($definition, $keys);

    $newDefinition = [
      'name' => $this->composer_name,
      'description' => $definition['description'] ?? '',
      'type' => 'module',
      'version' => '1.0',
      'license' => 'proprietary',
    ];

    $definition = array_merge($newDefinition, $definition);

    $jsonFile->write($definition);

    $this->line(" - Updated $this->composer_name composer.json file");

    $this->newLine();
  }

  /**
   * Create the modules directory if needed.
   */
  protected function ensureModulesDirectoryExists(): void
  {
    if (! $this->filesystem->isDirectory($this->base_path)) {
      $this->filesystem->makeDirectory($this->base_path, 0777, true);
      $this->line(" - Created <info>$this->base_path</info>");
    }

    $this->newLine();
  }

  /**
   * Update the project composer file.
   *
   * @throws ParsingException
   * @throws Exception
   */
  protected function updateCoreComposerConfig(): void
  {
    $this->title('Updating application composer.json file');

    $original_working_dir = getcwd();
    if ($original_working_dir === false) { // @codeCoverageIgnore
      $original_working_dir = $this->laravel->basePath(); // @codeCoverageIgnore
    }
    chdir($this->laravel->basePath());

    $jsonFile = new JsonFile(Factory::getComposerFile());
    /** @var array<string, mixed> $definition */
    $definition = $jsonFile->read();

    if (! isset($definition['repositories'])) {
      $definition['repositories'] = []; // @codeCoverageIgnore
    }

    if (! isset($definition['require'])) {
      $definition['require'] = []; // @codeCoverageIgnore
    }

    /** @var string $modulesDirectory */
    $modulesDirectory = config('modulr.modules_directory', 'modules');
    $module_config = [
      'type' => 'path',
      'url' => str_replace('\\', '/', $modulesDirectory).'/*',
      'options' => [
        'symlink' => true,
      ],
    ];

    $has_changes = false;

    /** @var array<int|string, array{type: string, url: string, options?: array<string, mixed>}> $repositories */
    $repositories = $definition['repositories'];
    $repository_already_exists = collect($repositories)
      ->contains(fn (array $repository): bool => $repository['url'] === $module_config['url']);

    if ($repository_already_exists === false) {
      $this->line(" - Adding path repository for <info>{$module_config['url']}</info>");
      $has_changes = true;

      if (Arr::isAssoc($repositories)) {
        $repositories[$this->module_name] = $module_config;
      } else {
        $repositories[] = $module_config; // @codeCoverageIgnore
      }
      $definition['repositories'] = $repositories;
    }

    /** @var array<string, string> $require */
    $require = $definition['require'];
    // @codeCoverageIgnoreStart
    if (! isset($require[$this->composer_name])) {
      $this->line(" - Adding require statement for <info>$this->composer_name:*</info>");
      $has_changes = true;

      $require["$this->composer_namespace/$this->module_name"] = '^1.0';
      $definition['require'] = $this->sortComposerPackages($require);
    }
    // @codeCoverageIgnoreEnd

    /** @var array<string, string> $require */
    $require = $definition['require'];
    if (isset($require[$this->package_name])) {
      $this->line(" - Removing require statement for <info>$this->package_name</info>");
      $has_changes = true;

      unset($require[$this->package_name]);
      $definition['require'] = $this->sortComposerPackages($require);
    }

    if ($has_changes) {
      $jsonFile->write($definition);
      $this->line(" - Wrote to <info>{$jsonFile->getPath()}</info>");
    } else {
      $this->line(' - Nothing to update (repository & require entry already exist)');
    }

    chdir($original_working_dir);

    $this->newLine();
  }

  /**
   * Sort composer packages.
   *
   * @param  array<string, string>  $packages
   * @return array<string, string>
   */
  protected function sortComposerPackages(array $packages): array
  {
    $prefix = (fn (string $requirement): ?string => preg_replace(
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
    ));

    uksort($packages, fn (string $a, string $b): int => strnatcmp((string) $prefix($a), (string) $prefix($b)));

    return $packages;
  }

  /**
   * Set up our terminal colors.
   */
  protected function setUpStyles(): void
  {
    $outputFormatter = $this->getOutput()->getFormatter();

    if (! $outputFormatter->hasStyle('kbd')) {
      $outputFormatter->setStyle('kbd', new OutputFormatterStyle('cyan'));
    }
  }

  /**
   * Set the output title.
   */
  protected function title(string $title): void
  {
    $this->getOutput()->title($title);
  }

  /**
   * Generate one or more new lines in the terminal.
   *
   * @param  int  $count
   */
  public function newLine($count = 1): static // @pest-ignore-type
  {
    $this->getOutput()->newLine($count);

    return $this;
  }

  /**
   * Create a process to return to the various methods and for testing.
   *
   * @param  array<int, string>  $command
   */
  public function createProcess(array $command): Process
  {
    return new Process($command);
  }
}
