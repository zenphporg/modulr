<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Console\ControllerMakeCommand;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Override;
use Zen\Modulr\Concerns\ConfiguresCommands;
use Zen\Modulr\Support\ConfigStore;

class MakeController extends ControllerMakeCommand
{
  use ConfiguresCommands;

  /**
   * @throws BindingResolutionException
   */
  #[Override]
  protected function parseModel($model): string
  {
    if (! ($module = $this->module()) instanceof ConfigStore) {
      return parent::parseModel($model);
    }

    if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
      throw new InvalidArgumentException('Model name contains invalid characters.');
    }

    $model = trim(str_replace('/', '\\', $model), '\\');

    if (! Str::startsWith($model, $namespace = $module->namespaces->first())) {
      return $namespace.$model;
    }

    return $model;
  }
}
