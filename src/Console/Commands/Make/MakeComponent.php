<?php

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Foundation\Console\ComponentMakeCommand;
use Zen\Modulr\Concerns\ConfiguresCommands;

class MakeComponent extends ComponentMakeCommand
{
  use ConfiguresCommands;

  /**
   * @param  string  $path
   *
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
  protected function viewPath($path = ''): string
  {
    if (($module = $this->module()) instanceof \Zen\Modulr\Support\ConfigStore) {
      return $module->path("resources/views/$path");
    }

    return parent::viewPath($path);
  }
}
