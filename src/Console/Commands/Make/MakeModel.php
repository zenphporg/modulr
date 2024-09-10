<?php

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Foundation\Console\ModelMakeCommand;
use Zen\Modulr\Concerns\ConfiguresCommands;

class MakeModel extends ModelMakeCommand
{
  use ConfiguresCommands;

  /**
   * @param  $rootNamespace
   * @return string
   *
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
  protected function getDefaultNamespace($rootNamespace)
  {
    if (($module = $this->module()) instanceof \Zen\Modulr\Support\ConfigStore) {
      $rootNamespace = rtrim($module->namespaces->first(), '\\');
    }

    return $rootNamespace.'\Models';
  }
}
