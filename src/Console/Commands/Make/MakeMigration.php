<?php

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Filesystem\Filesystem;
use Zen\Modulr\Concerns\ConfiguresCommands;

class MakeMigration extends MigrateMakeCommand
{
  use ConfiguresCommands;

  /**
   * @return array|string|string[]
   *
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
  protected function getMigrationPath()
  {
    $path = parent::getMigrationPath();

    if (($module = $this->module()) instanceof \Zen\Modulr\Support\ConfigStore) {
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
