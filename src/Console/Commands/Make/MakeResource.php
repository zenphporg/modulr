<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Foundation\Console\ResourceMakeCommand;
use Zen\Modulr\Concerns\ConfiguresCommands;

class MakeResource extends ResourceMakeCommand
{
  use ConfiguresCommands;
}
