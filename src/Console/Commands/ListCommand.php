<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Zen\Modulr\Support\ConfigStore;
use Zen\Modulr\Support\Registry;

class ListCommand extends Command
{
  protected $signature = 'modules:list';

  protected $description = 'List all modules';

  public function handle(Registry $registry): void
  {
    $namespace_title = 'Namespace';

    $table = $registry->modules()
      ->map(function (ConfigStore $configStore) use (&$namespace_title): array {
        $namespaces = $configStore->namespaces->map(fn (string $namespace): string => rtrim($namespace, '\\'));

        if ($configStore->namespaces->count() > 1) {
          $namespace_title = 'Namespaces';
        }

        return [
          $configStore->name,
          Str::after(str_replace('\\', '/', $configStore->base_path), str_replace('\\', '/', $this->laravel->basePath()).'/'),
          $namespaces->implode(', '),
        ];
      })
      ->all();

    $count = $registry->modules()->count();
    $this->line('You have '.$count.' '.Str::plural('module', $count).' installed.');
    $this->line('');

    $this->table(['Module', 'Path', $namespace_title], $table);
  }
}
