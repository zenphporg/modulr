<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Foundation\Console\RuleMakeCommand;
use Zen\Modulr\Concerns\ConfiguresCommands;

class MakeRule extends RuleMakeCommand
{
  use ConfiguresCommands;
}
