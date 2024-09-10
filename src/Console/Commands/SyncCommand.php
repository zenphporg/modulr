<?php

namespace Zen\Modulr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
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

  /**
   * @var Filesystem
   */
  protected $filesystem;

  /**
   * @var Registry
   */
  protected $registry;

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

    $modules_directory = config('modulr.modules_directory', 'modules');

    $config = simplexml_load_string($this->filesystem->get($config_path));

    $existing_nodes = $config->xpath("//phpunit//testsuites//testsuite//directory[text()='./{$modules_directory}/*/tests']");

    if (count($existing_nodes) > 0) {
      $this->info('Modules test suite already exists in phpunit.xml');

      return;
    }

    $testsuites = $config->xpath('//phpunit//testsuites');
    if (! count($testsuites)) {
      $this->error('Cannot find <testsuites> node in phpunit.xml file. Skipping PHPUnit configuration.');

      return;
    }

    $testsuite = $testsuites[0]->addChild('testsuite');
    $testsuite->addAttribute('name', 'Modules');

    $directory = $testsuite->addChild('directory');
    $directory->addAttribute('suffix', 'Test.php');
    $directory[0] = "./{$modules_directory}/*/tests";

    $config->formatOutput = true;

    $this->filesystem->put($config_path, $config->asXML());
    $this->info('Added "Modules" PHPUnit test suite.');
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
    $writer = new LaravelConfigWriter($config_path, $this->registry);

    if ($writer->handle()) {
      $this->info('Updated PhpStorm/Laravel Plugin config file...');
    } else {
      $this->info('Did not find/update PhpStorm/Laravel Plugin config.');
      if ($this->getOutput()->isVerbose()) {
        $this->warn($writer->last_error);
      }
    }
  }

  protected function updatePhpStormPhpConfig(): void
  {
    $config_path = $this->getLaravel()->basePath('.idea/php.xml');
    $writer = new PhpFrameworkWriter($config_path, $this->registry);

    if ($writer->handle()) {
      $this->info('Updated PhpStorm PHP config file...');
    } else {
      $this->info('Did not find/update PhpStorm PHP config.');
      if ($this->getOutput()->isVerbose()) {
        $this->warn($writer->last_error);
      }
    }
  }

  protected function updatePhpStormWorkspaceConfig(): void
  {
    $config_path = $this->getLaravel()->basePath('.idea/workspace.xml');
    $writer = new WorkspaceWriter($config_path, $this->registry);

    if ($writer->handle()) {
      $this->info('Updated PhpStorm workspace library roots...');
    } else {
      $this->info('Did not find/update PhpStorm workspace config.');
      if ($this->getOutput()->isVerbose()) {
        $this->warn($writer->last_error);
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
        $writer = new ProjectImlWriter($config_path, $this->registry);

        if ($writer->handle()) {
          $this->info("Updated PhpStorm project source folders in '{$file->getBasename()}'");

          return true;
        }

        $this->info("Could not update PhpStorm project source folders in '{$file->getBasename()}'");

        if ($this->getOutput()->isVerbose()) {
          $this->warn($writer->last_error);
        }

        return false;
      });
  }
}
