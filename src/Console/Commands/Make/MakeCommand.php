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
   * @param  string  $stub
   * @param  string  $name
   *
   * @throws BindingResolutionException
   */
  #[Override]
  protected function replaceClass($stub, $name): string // @pest-ignore-type
  {
    $stub = parent::replaceClass($stub, $name);

    /** @var string|null $command */
    $command = $this->option('command');
    $module = $this->module();

    if ($module instanceof ConfigStore) {
      if ($command !== null && $command !== '') {
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
