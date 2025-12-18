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
   * @param  string  $model
   *
   * @throws BindingResolutionException
   */
  #[Override]
  protected function parseModel($model): string // @pest-ignore-type
  {
    if (! ($module = $this->module()) instanceof ConfigStore) {
      return parent::parseModel($model);
    }

    throw_if(preg_match('([^A-Za-z0-9_/\\\\])', $model), InvalidArgumentException::class, 'Model name contains invalid characters.');

    $model = trim(str_replace('/', '\\', $model), '\\');

    /** @var string $namespace */
    $namespace = $module->namespaces->first() ?? '';
    if (! Str::startsWith($model, $namespace)) {
      return $namespace.$model;
    }

    return $model;
  }
}
