<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use SimpleXMLElement;
use Symfony\Component\Finder\SplFileInfo;
use Zen\Modulr\Support\FinderCollection;
use Zen\Modulr\Support\PhpStorm\LaravelConfigWriter;
use Zen\Modulr\Support\PhpStorm\PhpFrameworkWriter;
use Zen\Modulr\Support\PhpStorm\ProjectImlWriter;
use Zen\Modulr\Support\PhpStorm\WorkspaceWriter;
use Zen\Modulr\Support\Registry;

class SyncCommand extends Command
{
  protected $signature = 'modules:sync {--no-phpstorm : Do not update PhpStorm config files}';

  protected $description = 'Sync your project\'s configuration with your current modules';

  protected Filesystem $filesystem;

  protected Registry $registry;

  public function handle(Registry $registry, Filesystem $filesystem): void
  {
    $this->filesystem = $filesystem;
    $this->registry = $registry;

    $this->updatePhpUnit();

    if ($this->option('no-phpstorm') !== true) {
      $this->updatePhpStormConfig();
    }
  }

  protected function updatePhpUnit(): void
  {
    $config_path = $this->getLaravel()->basePath('phpunit.xml');

    if (! $this->filesystem->exists($config_path)) {
      $this->warn('No phpunit.xml file found. Skipping PHPUnit configuration.');

      return;
    }

    /** @var string $modules_directory */
    $modules_directory = config('modulr.modules_directory', 'modules');

    $config = @simplexml_load_string($this->filesystem->get($config_path));
    if ($config === false) {
      $this->error('Failed to parse phpunit.xml file. Skipping PHPUnit configuration.');

      return;
    }

    // Add modules Feature tests to Feature testsuite
    $featureUpdated = $this->addDirectoryToTestsuite($config, 'Feature', "{$modules_directory}/*/tests/Feature");

    // Add modules Unit tests to Unit testsuite
    $unitUpdated = $this->addDirectoryToTestsuite($config, 'Unit', "{$modules_directory}/*/tests/Unit");

    // Add modules source to coverage source/include
    $sourceUpdated = $this->addDirectoryToSource($config, $modules_directory);

    $updated = $featureUpdated || $unitUpdated || $sourceUpdated;

    if ($updated) {
      $xmlContent = $config->asXML();
      if ($xmlContent !== false) {
        $this->filesystem->put($config_path, $xmlContent);
      }
    } else {
      $this->info('PHPUnit configuration already up to date.');
    }
  }

  protected function addDirectoryToTestsuite(SimpleXMLElement $config, string $testsuiteName, string $directoryPath): bool
  {
    // Check if already exists
    $existingNodes = $config->xpath("//phpunit//testsuites//testsuite[@name='{$testsuiteName}']//directory[text()='{$directoryPath}']");
    if (is_array($existingNodes) && count($existingNodes) > 0) {
      return false;
    }

    // Find the testsuite
    $testsuites = $config->xpath("//phpunit//testsuites//testsuite[@name='{$testsuiteName}']");
    if (! is_array($testsuites) || count($testsuites) === 0) {
      $this->warn("Cannot find '{$testsuiteName}' testsuite in phpunit.xml. Skipping.");

      return false;
    }

    /** @var SimpleXMLElement $testsuite */
    $testsuite = $testsuites[0];
    $testsuite->addChild('directory', $directoryPath);

    $this->info("Added modules directory to '{$testsuiteName}' testsuite.");

    return true;
  }

  protected function addDirectoryToSource(SimpleXMLElement $config, string $modulesDirectory): bool
  {
    $directoryPath = "{$modulesDirectory}/*/src";

    // Check if already exists in source/include
    $existingNodes = $config->xpath("//phpunit//source//include//directory[text()='{$directoryPath}']");
    if (is_array($existingNodes) && count($existingNodes) > 0) {
      return false;
    }

    // Find the source/include element
    $includes = $config->xpath('//phpunit//source//include');
    if (! is_array($includes) || count($includes) === 0) {
      // No source/include section exists, skip
      return false;
    }

    /** @var SimpleXMLElement $include */
    $include = $includes[0];
    $include->addChild('directory', $directoryPath);

    $this->info('Added modules source directory to coverage configuration.');

    return true;
  }

  protected function updatePhpStormConfig(): void
  {
    $this->updatePhpStormLaravelPlugin();
    $this->updatePhpStormPhpConfig();
    $this->updatePhpStormWorkspaceConfig();
    $this->updatePhpStormProjectIml();
  }

  protected function updatePhpStormLaravelPlugin(): void
  {
    $config_path = $this->getLaravel()->basePath('.idea/laravel-plugin.xml');
    $laravelConfigWriter = new LaravelConfigWriter($config_path, $this->registry);

    if ($laravelConfigWriter->handle()) {
      $this->info('Updated PhpStorm/Laravel Plugin config file...');
    } else {
      $this->info('Did not find/update PhpStorm/Laravel Plugin config.');
      if ($this->getOutput()->isVerbose()) {
        $this->warn($laravelConfigWriter->last_error);
      }
    }
  }

  protected function updatePhpStormPhpConfig(): void
  {
    $config_path = $this->getLaravel()->basePath('.idea/php.xml');
    $phpFrameworkWriter = new PhpFrameworkWriter($config_path, $this->registry);

    if ($phpFrameworkWriter->handle()) {
      $this->info('Updated PhpStorm PHP config file...');
    } else {
      $this->info('Did not find/update PhpStorm PHP config.');
      if ($this->getOutput()->isVerbose()) {
        $this->warn($phpFrameworkWriter->last_error);
      }
    }
  }

  protected function updatePhpStormWorkspaceConfig(): void
  {
    $config_path = $this->getLaravel()->basePath('.idea/workspace.xml');
    $workspaceWriter = new WorkspaceWriter($config_path, $this->registry);

    if ($workspaceWriter->handle()) {
      $this->info('Updated PhpStorm workspace library roots...');
    } else {
      $this->info('Did not find/update PhpStorm workspace config.');
      if ($this->getOutput()->isVerbose()) {
        $this->warn($workspaceWriter->last_error);
      }
    }
  }

  protected function updatePhpStormProjectIml(): void
  {
    $idea_directory = $this->getLaravel()->basePath('.idea/');
    if (! $this->filesystem->isDirectory($idea_directory)) {
      return;
    }

    FinderCollection::forFiles()
      ->in($idea_directory)
      ->name('*.iml')
      ->first(function (SplFileInfo $file): bool {
        $config_path = $file->getPathname();
        $projectImlWriter = new ProjectImlWriter($config_path, $this->registry);

        if ($projectImlWriter->handle()) {
          $this->info("Updated PhpStorm project source folders in '{$file->getBasename()}'");

          return true;
        }

        $this->info("Could not update PhpStorm project source folders in '{$file->getBasename()}'");

        if ($this->getOutput()->isVerbose()) {
          $this->warn($projectImlWriter->last_error);
        }

        return false;
      });
  }
}
