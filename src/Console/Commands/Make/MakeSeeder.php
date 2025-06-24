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
   * @return array|string
   *
   * @throws BindingResolutionException
   */
  #[Override]
  protected function getPath($name)
  {
    if (($module = $this->module()) instanceof ConfigStore) {
      $name = Str::replaceFirst($module->qualify('Database\\Seeders\\'), '', $name);

      return $this->getModularPath($name);
    }

    return parent::getPath($name);
  }

  /**
   * @return \Zen\Modulr\Console\Commands\Make\MakeSeeder
   *
   * @throws BindingResolutionException
   */
  #[Override]
  protected function replaceNamespace(&$stub, $name)
  {
    if (($module = $this->module()) instanceof ConfigStore && version_compare($this->getLaravel()->version(), '9.6.0', '<')) {
      $namespace = $module->qualify('Database\Seeders');
      $stub = str_replace('namespace Database\Seeders;', "namespace $namespace;", $stub);
    }

    return parent::replaceNamespace($stub, $name);
  }

  /**
   * @return string
   *
   * @throws BindingResolutionException
   */
  #[Override]
  protected function rootNamespace()
  {
    if (($module = $this->module()) instanceof ConfigStore && version_compare($this->getLaravel()->version(), '9.6.0', '>=')) {
      return $module->qualify('Database\Seeders');
    }

    return parent::rootNamespace();
  }
}
