<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Foundation\Console\ListenerMakeCommand;
use Override;
use Zen\Modulr\Concerns\ConfiguresCommands;
use Zen\Modulr\Support\Facades\Modulr;

class MakeListener extends ListenerMakeCommand
{
  use ConfiguresCommands;

  /**
   * @param  string  $name
   *
   * @throws FileNotFoundException
   */
  #[Override]
  protected function buildClass($name): string // @pest-ignore-type
  {
    /** @var string|null $event */
    $event = $this->option('event');

    if (Modulr::moduleForClass($name) && $event !== null && $event !== '') {
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
