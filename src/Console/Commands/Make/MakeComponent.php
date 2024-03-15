<?php

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Foundation\Console\ComponentMakeCommand;
use Zen\Modulr\Concerns\ConfiguresCommands;

class MakeComponent extends ComponentMakeCommand
{
  use ConfiguresCommands;

  protected function viewPath($path = '')
  {
    if ($module = $this->module()) {
      return $module->path("resources/views/{$path}");
    }

    return parent::viewPath($path);
  }
}
