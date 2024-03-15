<?php

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Foundation\Console\MailMakeCommand;
use Zen\Modulr\Concerns\ConfiguresCommands;

class MakeMail extends MailMakeCommand
{
  use ConfiguresCommands;
}
