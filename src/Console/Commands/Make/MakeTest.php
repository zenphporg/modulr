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
   * @param  string  $name
   *
   * @throws BindingResolutionException
   */
  #[Override]
  protected function getPath($name): string // @pest-ignore-type
  {
    if (($module = $this->module()) instanceof ConfigStore) {
      /** @var string $namespace */
      $namespace = $module->namespaces->first() ?? '';
      $name = '\\'.Str::replaceFirst($namespace, '', $name);

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
