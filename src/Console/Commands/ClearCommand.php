<?php

namespace Zen\Modulr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Zen\Modulr\Support\Registry;

class ClearCommand extends Command
{
  protected $signature = 'modules:clear';

  protected $description = 'Remove the module cache file';

  public function handle(Filesystem $filesystem, Registry $registry)
  {
    $filesystem->delete($registry->getCachePath());
    $this->info('Module cache cleared!');
  }
}
