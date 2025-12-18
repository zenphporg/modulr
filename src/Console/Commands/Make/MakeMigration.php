<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Filesystem\Filesystem;
use Override;
use Zen\Modulr\Concerns\GeneratesModules;
use Zen\Modulr\Support\ConfigStore;

class MakeMigration extends MigrateMakeCommand
{
  use GeneratesModules;

  /**
   * @return array<int, string>|string
   *
   * @throws BindingResolutionException
   */
  #[Override]
  protected function getMigrationPath(): array|string
  {
    $path = parent::getMigrationPath();

    if (($module = $this->module()) instanceof ConfigStore) {
      $app_directory = $this->laravel->databasePath('migrations');
      $module_directory = $module->path('database/migrations');

      $path = str_replace($app_directory, $module_directory, $path);

      $filesystem = $this->getLaravel()->make(Filesystem::class);
      if (! $filesystem->isDirectory($module_directory)) {
        $filesystem->makeDirectory($module_directory, 0755, true);
      }
    }

    return $path;
  }
}
