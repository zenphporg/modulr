<?php

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Routing\Console\ControllerMakeCommand;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Zen\Modulr\Concerns\ConfiguresCommands;

class MakeController extends ControllerMakeCommand
{
  use ConfiguresCommands;

  /**
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
  protected function parseModel($model): string
  {
    if (! ($module = $this->module()) instanceof \Zen\Modulr\Support\ConfigStore) {
      return parent::parseModel($model);
    }

    if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
      throw new InvalidArgumentException('Model name contains invalid characters.');
    }

    $model = trim(str_replace('/', '\\', $model), '\\');

    if (! Str::startsWith($model, $namespace = $module->namespaces->first())) {
      $model = $namespace.$model;
    }

    return $model;
  }
}
