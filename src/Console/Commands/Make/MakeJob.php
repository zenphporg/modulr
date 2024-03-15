<?php

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Foundation\Console\JobMakeCommand;
use Zen\Modulr\Concerns\ConfiguresCommands;

class MakeJob extends JobMakeCommand
{
  use ConfiguresCommands;
}
