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

  protected function getPath($name)
  {
    if ($module = $this->module()) {
      $name = '\\'.Str::replaceFirst($module->namespaces->first(), '', $name);

      return $this->getModularPath($name);
    }

    return parent::getPath($name);
  }

  protected function rootNamespace()
  {
    if ($module = $this->module()) {
      return $module->namespaces->first().'Tests';
    }

    return 'Tests';
  }
}
