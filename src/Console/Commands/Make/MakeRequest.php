<?php

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Foundation\Console\RequestMakeCommand;
use Zen\Modulr\Concerns\ConfiguresCommands;

class MakeRequest extends RequestMakeCommand
{
  use ConfiguresCommands;
}
