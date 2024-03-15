<?php

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Foundation\Console\ConsoleMakeCommand;
use Illuminate\Support\Str;
use Zen\Modulr\Concerns\ConfiguresCommands;

class MakeCommand extends ConsoleMakeCommand
{
  use ConfiguresCommands;

  protected function replaceClass($stub, $name)
  {
    $stub = parent::replaceClass($stub, $name);
    $module = $this->module();

    if ($module && (! $this->option('command') || 'command:name' === $this->option('command'))) {
      $cli_name = Str::of($name)->classBasename()->kebab();

      $find = [
        "signature = 'command:name'",
        "signature = 'app:{$cli_name}'",
      ];

      $stub = str_replace($find, "{$module->name}:{$cli_name}", $stub);
    }

    return $stub;
  }
}
