<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Console\Seeds\SeederMakeCommand;
use Illuminate\Support\Str;
use Override;
use Zen\Modulr\Concerns\ConfiguresCommands;
use Zen\Modulr\Support\ConfigStore;

class MakeSeeder extends SeederMakeCommand
{
  use ConfiguresCommands {
    getPath as getModularPath;
  }

  /**
   * @param  string  $name
   *
   * @throws BindingResolutionException
   */
  #[Override]
  protected function getPath($name): string // @pest-ignore-type
  {
    if (($module = $this->module()) instanceof ConfigStore) {
      $name = Str::replaceFirst($module->qualify('Database\\Seeders\\'), '', $name);

      return $this->getModularPath($name);
    }

    return parent::getPath($name);
  }

  /**
   * @param  string  $stub
   * @param  string  $name
   *
   * @throws BindingResolutionException
   */
  #[Override]
  protected function replaceNamespace(&$stub, $name): static // @pest-ignore-type
  {
    if (($module = $this->module()) instanceof ConfigStore && version_compare($this->getLaravel()->version(), '9.6.0', '<')) {
      $namespace = $module->qualify('Database\Seeders');
      $stub = str_replace('namespace Database\Seeders;', "namespace $namespace;", $stub);
    }

    return parent::replaceNamespace($stub, $name);
  }

  /**
   * @throws BindingResolutionException
   */
  #[Override]
  protected function rootNamespace(): string
  {
    if (($module = $this->module()) instanceof ConfigStore && version_compare($this->getLaravel()->version(), '9.6.0', '>=')) {
      return $module->qualify('Database\Seeders');
    }

    return parent::rootNamespace();
  }
}
