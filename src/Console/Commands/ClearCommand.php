<?php

namespace Zen\Modulr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Zen\Modulr\Support\Registry;

class ClearCommand extends Command
{
  /**
   * @var string
   */
  protected $signature = 'modules:clear';

  /**
   * @var string
   */
  protected $description = 'Remove the module cache file';

  /**
   * @param  \Illuminate\Filesystem\Filesystem  $filesystem
   * @param  \Zen\Modulr\Support\Registry  $registry
   * @return void
   */
  public function handle(Filesystem $filesystem, Registry $registry): void
  {
    $filesystem->delete($registry->getCachePath());
    $this->info('Module cache cleared!');
  }
}
