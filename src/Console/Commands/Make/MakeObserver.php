<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Foundation\Console\ObserverMakeCommand;
use Zen\Modulr\Concerns\ConfiguresCommands;

class MakeObserver extends ObserverMakeCommand
{
  use ConfiguresCommands;
}
