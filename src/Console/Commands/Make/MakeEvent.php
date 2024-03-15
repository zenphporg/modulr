<?php

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Foundation\Console\EventMakeCommand;
use Zen\Modulr\Concerns\ConfiguresCommands;

class MakeEvent extends EventMakeCommand
{
  use ConfiguresCommands;
}
