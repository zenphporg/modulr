<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Console\TestMakeCommand;
use Illuminate\Support\Str;
use Override;
use Zen\Modulr\Concerns\ConfiguresCommands;
use Zen\Modulr\Support\ConfigStore;

class MakeTest extends TestMakeCommand
{
  use ConfiguresCommands {
    getPath as getModularPath;
  }

  /**
   * @throws BindingResolutionException
   */
  #[Override]
  protected function getPath($name): array|string
  {
    if (($module = $this->module()) instanceof ConfigStore) {
      $name = '\\'.Str::replaceFirst($module->namespaces->first(), '', $name);

      return $this->getModularPath($name);
    }

    return parent::getPath($name);
  }

  /**
   * @throws BindingResolutionException
   */
  #[Override]
  protected function rootNamespace(): string
  {
    if (($module = $this->module()) instanceof ConfigStore) {
      return $module->namespaces->first().'Tests';
    }

    return 'Tests';
  }
}
