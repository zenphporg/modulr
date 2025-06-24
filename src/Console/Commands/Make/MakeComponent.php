<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Console\ComponentMakeCommand;
use Override;
use Zen\Modulr\Concerns\ConfiguresCommands;
use Zen\Modulr\Support\ConfigStore;

class MakeComponent extends ComponentMakeCommand
{
  use ConfiguresCommands;

  /**
   * @param  string  $path
   *
   * @throws BindingResolutionException
   */
  #[Override]
  protected function viewPath($path = ''): string
  {
    if (($module = $this->module()) instanceof ConfigStore) {
      return $module->path("resources/views/$path");
    }

    return parent::viewPath($path);
  }
}
