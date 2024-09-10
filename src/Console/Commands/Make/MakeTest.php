<?php

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Foundation\Console\TestMakeCommand;
use Illuminate\Support\Str;
use Zen\Modulr\Concerns\ConfiguresCommands;

class MakeTest extends TestMakeCommand
{
  use ConfiguresCommands {
    getPath as getModularPath;
  }

  /**
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
  protected function getPath($name): array|string
  {
    if (($module = $this->module()) instanceof \Zen\Modulr\Support\ConfigStore) {
      $name = '\\'.Str::replaceFirst($module->namespaces->first(), '', $name);

      return $this->getModularPath($name);
    }

    return parent::getPath($name);
  }

  /**
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
  protected function rootNamespace(): string
  {
    if (($module = $this->module()) instanceof \Zen\Modulr\Support\ConfigStore) {
      return $module->namespaces->first().'Tests';
    }

    return 'Tests';
  }
}
