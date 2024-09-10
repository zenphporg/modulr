<?php

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Foundation\Console\ListenerMakeCommand;
use Zen\Modulr\Concerns\ConfiguresCommands;
use Zen\Modulr\Support\Facades\Modulr;

class MakeListener extends ListenerMakeCommand
{
  use ConfiguresCommands;

  /**
   * @param  $name
   * @return array|string|string[]
   *
   * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
   */
  protected function buildClass($name)
  {
    $event = $this->option('event');

    if (Modulr::moduleForClass($name)) {
      $stub = str_replace(
        ['DummyEvent', '{{ event }}'],
        class_basename($event),
        GeneratorCommand::buildClass($name)
      );

      return str_replace(
        ['DummyFullEvent', '{{ eventNamespace }}'],
        trim($event, '\\'),
        $stub
      );
    }

    return parent::buildClass($name);
  }
}
