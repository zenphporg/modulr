<?php

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Routing\Console\MiddlewareMakeCommand;
use Zen\Modulr\Concerns\ConfiguresCommands;

class MakeMiddleware extends MiddlewareMakeCommand
{
  use ConfiguresCommands;
}
