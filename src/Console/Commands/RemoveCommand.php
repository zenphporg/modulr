<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands;

use Composer\Factory;
use Composer\Json\JsonFile;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Seld\JsonLint\ParsingException;
use Zen\Modulr\Support\ConfigStore;
use Zen\Modulr\Support\Registry;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class RemoveCommand extends Command
{
  /**
   * @var string
   */
  protected $signature = 'modules:remove
    {name? : The name of the module to remove}
    {--force : Skip confirmation prompt}';

  /**
   * @var string
   */
  protected $description = 'Remove a module and all its files';

  protected Registry $registry;

  protected Filesystem $filesystem;

  protected string $module_name;

  protected string $composer_name;

  protected bool $is_last_module = false;

  /**
   * @throws ParsingException
   * @throws Exception
   */
  public function handle(Registry $registry, Filesystem $filesystem): int
  {
    $this->registry = $registry;
    $this->filesystem = $filesystem;

    $modules = $registry->modules();

    if ($modules->isEmpty()) {
      $this->error('No modules found.');

      return 1;
    }

    $this->module_name = $this->getModuleName();

    if ($this->module_name === '') {
      return 1;
    }

    $module = $registry->module($this->module_name);

    if (! $module instanceof ConfigStore) {
      $this->error("Module '{$this->module_name}' not found.");

      return 1;
    }

    if (! $this->confirmRemoval()) {
      $this->info('Module removal cancelled.');

      return 0;
    }

    // Determine composer package name from module's composer.json
    $this->composer_name = $this->getComposerName($module);
    $this->is_last_module = $modules->count() === 1;

    // Delete the module directory
    $this->filesystem->deleteDirectory($module->base_path);
    $this->info("Deleted module directory: {$module->base_path}");

    // Update composer.json
    $this->updateCoreComposerConfig();

    // Reload registry
    $this->registry->reload();

    // Clear module cache
    $this->call(ClearCommand::class);

    // Run composer update
    $this->runComposerUpdate();

    $this->newLine();
    $this->info("Module '{$this->module_name}' has been removed.");

    return 0;
  }

  protected function getModuleName(): string
  {
    /** @var string|null $name */
    $name = $this->argument('name');

    if ($name !== null) {
      return $name;
    }

    // Skip interactive prompt when running unit tests
    if ($this->laravel->runningUnitTests()) {
      $this->error('Module name is required.');

      return '';
    }

    $modules = $this->registry->modules()->keys()->all();

    /** @var string $selected */
    $selected = select(
      label: 'Which module would you like to remove?',
      options: $modules,
    );

    return $selected;
  }

  protected function confirmRemoval(): bool
  {
    if ($this->option('force') === true) {
      return true;
    }

    // Skip interactive prompt when running unit tests
    if ($this->laravel->runningUnitTests()) {
      return true;
    }

    $this->newLine();
    $this->error('  ⚠️  WARNING: THIS ACTION IS IRREVERSIBLE!  ⚠️  ');
    $this->newLine();
    $this->warn("  You are about to permanently delete the module '{$this->module_name}'.");
    $this->warn('  This will remove ALL files in the module directory including:');
    $this->line('    • Models, Controllers, and all PHP classes');
    $this->line('    • Migrations, Seeders, and Factories');
    $this->line('    • Views, Components, and Assets');
    $this->line('    • Tests and Configuration files');
    $this->line('    • Any other files in the module directory');
    $this->newLine();

    return confirm(
      label: "Are you absolutely sure you want to delete the '{$this->module_name}' module?",
      default: false,
    );
  }

  protected function getComposerName(ConfigStore $module): string
  {
    $composerPath = $module->base_path.'/composer.json';

    if ($this->filesystem->exists($composerPath)) {
      /** @var array{name?: string} $composerData */
      $composerData = json_decode($this->filesystem->get($composerPath), true);

      if (isset($composerData['name'])) {
        return $composerData['name'];
      }
    }

    // Fallback: construct from config
    /** @var string $modulesVendor */
    $modulesVendor = config('modulr.modules_vendor', 'modules');

    return $modulesVendor.'/'.$this->module_name;
  }

  /**
   * @throws ParsingException
   * @throws Exception
   */
  protected function updateCoreComposerConfig(): void
  {
    $this->info('Updating application composer.json file...');

    $original_working_dir = getcwd();
    if ($original_working_dir === false) {
      $original_working_dir = $this->laravel->basePath();
    }
    chdir($this->laravel->basePath());

    $jsonFile = new JsonFile(Factory::getComposerFile());
    /** @var array<string, mixed> $definition */
    $definition = $jsonFile->read();

    $has_changes = false;

    // Remove from require array
    if (isset($definition['require']) && is_array($definition['require'])) {
      /** @var array<string, string> $require */
      $require = $definition['require'];

      if (isset($require[$this->composer_name])) {
        unset($require[$this->composer_name]);
        $definition['require'] = $require;
        $has_changes = true;
        $this->line(" - Removed <info>{$this->composer_name}</info> from require");
      }
    }

    // If this is the last module, remove the path repository
    if ($this->is_last_module && isset($definition['repositories']) && is_array($definition['repositories'])) {
      /** @var string $modulesDirectory */
      $modulesDirectory = config('modulr.modules_directory', 'modules');
      $moduleUrl = str_replace('\\', '/', $modulesDirectory).'/*';

      /** @var array<int|string, array{type?: string, url?: string}> $repositories */
      $repositories = $definition['repositories'];

      // Filter out the modules path repository
      $filteredRepositories = [];
      foreach ($repositories as $key => $repository) {
        if (! isset($repository['url']) || $repository['url'] !== $moduleUrl) {
          if (Arr::isAssoc($repositories)) {
            $filteredRepositories[$key] = $repository;
          } else {
            $filteredRepositories[] = $repository;
          }
        } else {
          $has_changes = true;
          $this->line(" - Removed path repository for <info>{$moduleUrl}</info>");
        }
      }

      $definition['repositories'] = $filteredRepositories;

      // Remove empty repositories array
      if ($filteredRepositories === []) {
        unset($definition['repositories']);
      }
    }

    if ($has_changes) {
      $jsonFile->write($definition);
      $this->line(" - Wrote to <info>{$jsonFile->getPath()}</info>");
    } else {
      $this->line(' - Nothing to update in composer.json');
    }

    chdir($original_working_dir);
  }

  protected function runComposerUpdate(): void
  {
    // Skip in unit tests
    if ($this->laravel->runningUnitTests()) {
      return;
    }

    $this->info('Running composer update...');
    $this->newLine();

    // Use passthru style output
    $process = proc_open(
      'composer update',
      [
        0 => STDIN,
        1 => STDOUT,
        2 => STDERR,
      ],
      $pipes,
      $this->laravel->basePath()
    );

    if (is_resource($process)) {
      proc_close($process);
    }
  }
}
