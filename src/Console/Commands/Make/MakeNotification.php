<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Foundation\Console\NotificationMakeCommand;
use Zen\Modulr\Concerns\ConfiguresCommands;

class MakeNotification extends NotificationMakeCommand
{
  use ConfiguresCommands;
}
