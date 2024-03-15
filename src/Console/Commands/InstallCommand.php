<?php

namespace Zen\Modulr\Console\Commands;

use Composer\Factory;
use Composer\Json\JsonFile;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Process\Process;
use Zen\Modulr\Support\Registry;

class InstallCommand extends Command
{
  protected $base_path;

  protected $module_namespace;

  protected $composer_namespace;

  protected $module_name;

  protected $package_name;

  protected $composer_name;

  protected $signature = 'modules:install {package : Composer package to modulize} {--name= : Optional module name}';

  protected $description = 'Install a composer package as a module.';

  /**
   * Instantiate the class.
   *
   * @param  Filesystem  $filesystem
   * @param  Registry  $module_registry
   */
  public function __construct(
      protected Filesystem $filesystem,
      protected Registry $module_registry
    ) {
    parent::__construct();
  }

  /**
   * Run our command function.
   *
   * @return void
   */
  public function handle()
  {
    $this->package_name = $this->argument('package');

    $this->module_name = $this->option('name')
      ? $this->option('name')
      : basename($this->package_name);

    $this->module_namespace = config('modulr.modules_namespace', 'Modules');
    $this->composer_namespace = config('modulr.modules_vendor') ?? Str::kebab($this->module_namespace);
    $this->composer_name = "{$this->composer_namespace}/{$this->module_name}";
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
   * @return void
   */
  protected function installComposerPackage(): void
  {
    $this->title("Running composer to require package: {$this->package_name}");

    $process = $this->createProcess(['composer', 'require', $this->package_name]);
    $process->setWorkingDirectory(base_path());
    $process->setTimeout(3600);

    $bar = $this->output->createProgressBar(100);
    $bar->start();

    $process->run(function () use ($bar) {
      $bar->advance();
    });

    $bar->finish();

    $this->newLine(2);

    $process->isSuccessful()
      ? $this->line(" - {$this->package_name} was installed successfully.")
      : $this->error(' - The composer command failed.');

    $this->newLine();
  }

  /**
   * Make a new module for the requested package.
   *
   * @return void
   */
  protected function makeNewModule(): void
  {
    $this->title("Installing new {$this->composer_name} module");

    $this->ensureModulesDirectoryExists();
  }

  /**
   * Copy all of the package contents to the module.
   */
  protected function movePackageToModules()
  {
    $this->title("Migrating {$this->composer_name} to it's new module");

    $source = base_path('vendor/'.$this->package_name);

    if (! File::exists($source)) {
      $this->error(" - Source directory does not exist: {$source}");

      return 1;
    }

    File::ensureDirectoryExists($this->base_path);

    File::copyDirectory($source, $this->base_path);

    $this->filesystem->delete($this->module_registry->getCachePath());

    $this->line(' - Module cache cleared!');

    $this->newLine();

    $this->line(" - Migration of {$this->package_name} completed successfully.");

    $this->newLine();
  }

  /**
   * Update composer to finalize.
   *
   * @return void
   */
  protected function updateComposer(): void
  {
    $this->title('Updating composer to finalize module install');

    $process = $this->createProcess(['composer', 'update']);
    $process->setWorkingDirectory(base_path());
    $process->setTimeout(3600);

    $bar = $this->output->createProgressBar(100);
    $bar->start();

    $process->run(function () use ($bar) {
      $bar->advance();
    });

    $bar->finish();

    $this->newLine(2);

    $process->isSuccessful()
      ? $this->line(' - Composer updated successfully.')
      : $this->error(' - The composer command failed.');

    $this->newLine();
  }

  /**
   * Update our module composer file.
   *
   * @return void
   */
  protected function updateModuleComposerFile(): void
  {
    $this->title("Updating {$this->composer_name} composer.json file");

    $file = $this->base_path.'/'.'composer.json';

    $json_file = new JsonFile($file);
    $definition = $json_file->read();

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

    $json_file->write($definition);

    $this->line(" - Updated {$this->composer_name} composer.json file");

    $this->newLine();
  }

  /**
   * Create the modules directory if needed.
   *
   * @return void
   */
  protected function ensureModulesDirectoryExists(): void
  {
    if (! $this->filesystem->isDirectory($this->base_path)) {
      $this->filesystem->makeDirectory($this->base_path, 0777, true);
      $this->line(" - Created <info>{$this->base_path}</info>");
    }

    $this->newLine();
  }

  /**
   * Update the project composer file.
   *
   * @return void
   */
  protected function updateCoreComposerConfig(): void
  {
    $this->title('Updating application composer.json file');

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
        ->contains(function ($repository) use ($module_config) {
          return $repository['url'] === $module_config['url'];
        });

    if (false === $repository_already_exists) {
      $this->line(" - Adding path repository for <info>{$module_config['url']}</info>");
      $has_changes = true;

      if (Arr::isAssoc($definition['repositories'])) {
        $definition['repositories'][$this->module_name] = $module_config;
      } else {
        $definition['repositories'][] = $module_config;
      }
    }

    if (! isset($definition['require'][$this->composer_name])) {
      $this->line(" - Adding require statement for <info>{$this->composer_name}:*</info>");
      $has_changes = true;

      $definition['require']["{$this->composer_namespace}/{$this->module_name}"] = '^1.0';
      $definition['require'] = $this->sortComposerPackages($definition['require']);
    }

    if (isset($definition['require'][$this->package_name])) {
      $this->line(" - Removing require statement for <info>{$this->package_name}</info>");
      $has_changes = true;

      unset($definition['require'][$this->package_name]);
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

  /**
   * Sort composer packages.
   *
   * @param  array  $packages
   * @return array
   */
  protected function sortComposerPackages(array $packages): array
  {
    $prefix = function ($requirement) {
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

    uksort($packages, function ($a, $b) use ($prefix) {
      return strnatcmp($prefix($a), $prefix($b));
    });

    return $packages;
  }

  /**
   * Set up our terminal colors.
   *
   * @return void
   */
  protected function setUpStyles(): void
  {
    $formatter = $this->getOutput()->getFormatter();

    if (! $formatter->hasStyle('kbd')) {
      $formatter->setStyle('kbd', new OutputFormatterStyle('cyan'));
    }
  }

  /**
   * Set the output title.
   *
   * @param  string  $title
   * @return void
   */
  protected function title(string $title): void
  {
    $this->getOutput()->title($title);
  }

  /**
   * Generate one or more new lines in the terminal.
   *
   * @param  int  $count
   * @return void
   */
  public function newLine($count = 1): void
  {
    $this->getOutput()->newLine($count);
  }

  /**
   * Create a process to return to the various methods and for testing.
   *
   * @param  array  $command
   * @return Process
   */
  public function createProcess(array $command): Process
  {
    return new Process($command);
  }
}
