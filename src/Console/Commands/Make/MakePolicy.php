<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Foundation\Console\PolicyMakeCommand;
use Zen\Modulr\Concerns\ConfiguresCommands;

class MakePolicy extends PolicyMakeCommand
{
  use ConfiguresCommands;
}
