<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands\Database;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Override;
use Zen\Modulr\Concerns\GeneratesModules;
use Zen\Modulr\Support\ConfigStore;

class SeedCommand extends \Illuminate\Database\Console\Seeds\SeedCommand
{
  use GeneratesModules;

  /**
   * @return Seeder
   *
   * @throws BindingResolutionException
   */
  #[Override]
  protected function getSeeder()
  {
    if (($module = $this->module()) instanceof ConfigStore) {
      $default = $this->getDefinition()->getOption('class')->getDefault();
      $class = $this->input->getOption('class');

      if ($class === $default) {
        $class = $module->qualify($default);
      } elseif (! Str::contains($class, 'Database\\Seeders')) {
        $class = $module->qualify("Database\\Seeders\\$class");
      }

      return $this->laravel->make($class)
        ->setContainer($this->laravel)
        ->setCommand($this);
    }

    return parent::getSeeder();
  }
}
