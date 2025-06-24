<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Console\ConsoleMakeCommand;
use Illuminate\Support\Str;
use Override;
use Zen\Modulr\Concerns\ConfiguresCommands;
use Zen\Modulr\Support\ConfigStore;

class MakeCommand extends ConsoleMakeCommand
{
  use ConfiguresCommands;

  /**
   * @throws BindingResolutionException
   */
  #[Override]
  protected function replaceClass($stub, $name): array|string
  {
    $stub = parent::replaceClass($stub, $name);

    $command = $this->option('command');
    $module = $this->module();

    if ($module instanceof ConfigStore) {
      if ($command) {
        $stub = str_replace('command:name', $command, $stub);
      } else {
        $cli_name = Str::of($name)->classBasename()->kebab();
        $stub = str_replace('command:name', "$module->name:$cli_name", $stub);
      }
    } else {
      // Module not found, use default Laravel behavior
      $cli_name = Str::of($name)->classBasename()->kebab();
      $stub = str_replace('command:name', "app:$cli_name", $stub);
    }

    return $stub;
  }
}
