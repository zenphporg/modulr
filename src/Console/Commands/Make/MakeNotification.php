<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Foundation\Console\NotificationMakeCommand;
use Zen\Modulr\Concerns\ConfiguresCommands;
use Zen\Modulr\Support\ConfigStore;

class MakeNotification extends NotificationMakeCommand
{
  use ConfiguresCommands;

  /**
   * Write the Markdown template for the notification.
   */
  protected function writeMarkdownTemplate(): void
  {
    $module = $this->module();

    if (! $module instanceof ConfigStore) {
      parent::writeMarkdownTemplate();

      return;
    }

    $separator = '/';

    if (windows_os()) { // @codeCoverageIgnore
      $separator = '\\'; // @codeCoverageIgnore
    }

    $viewPath = $module->path('resources/views');
    /** @var string $markdownOption */
    $markdownOption = $this->option('markdown');
    $path = $viewPath.DIRECTORY_SEPARATOR.str_replace('.', $separator, $markdownOption).'.blade.php';

    if ($this->files->exists($path)) {
      $this->components->error(sprintf('%s [%s] already exists.', 'Markdown view', $path));

      return;
    }

    $this->files->ensureDirectoryExists(dirname($path));

    $stubPath = __DIR__.'/../../../../vendor/laravel/framework/src/Illuminate/Foundation/Console/stubs/markdown.stub';
    $contents = file_get_contents($stubPath);

    if ($contents === false) { // @codeCoverageIgnore
      $this->components->error('Could not read markdown notification stub file.'); // @codeCoverageIgnore

      return; // @codeCoverageIgnore
    }

    $this->files->put($path, $contents);

    $this->components->info(sprintf('%s [%s] created successfully.', 'Markdown view', $path));
  }
}
