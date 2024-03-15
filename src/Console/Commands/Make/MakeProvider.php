<?php

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Foundation\Console\ProviderMakeCommand;
use Zen\Modulr\Concerns\ConfiguresCommands;

class MakeProvider extends ProviderMakeCommand
{
  use ConfiguresCommands;
}
