<?php

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Foundation\Console\ModelMakeCommand;
use Zen\Modulr\Concerns\ConfiguresCommands;

class MakeModel extends ModelMakeCommand
{
  use ConfiguresCommands;

  protected function getDefaultNamespace($rootNamespace)
  {
    if ($module = $this->module()) {
      $rootNamespace = rtrim($module->namespaces->first(), '\\');
    }

    return $rootNamespace.'\Models';
  }
}
