<?php

namespace Zen\Modulr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LogicException;
use Throwable;
use Zen\Modulr\Support\ConfigStore;
use Zen\Modulr\Support\Registry;

class CacheCommand extends Command
{
  /**
   * @var string
   */
  protected $signature = 'modules:cache';

  /**
   * @var string
   */
  protected $description = 'Create a cache file for faster module loading';

  public function handle(Registry $registry, Filesystem $filesystem): void
  {
    $this->call(ClearCommand::class);

    $export = $registry->modules()
      ->map(function (ConfigStore $module_config): array {
        return $module_config->toArray();
      })
      ->toArray();

    $cache_path = $registry->getCachePath();
    $cache_contents = '<?php return '.var_export($export, true).';'.PHP_EOL;

    $filesystem->put($cache_path, $cache_contents);

    try {
      require $cache_path;
    } catch (Throwable $e) {
      $filesystem->delete($cache_path);
      throw new LogicException('Unable to cache module configuration.', 0, $e);
    }

    $this->info('Modules cached successfully!');
  }
}
