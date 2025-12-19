<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Foundation\Console\MailMakeCommand;
use Zen\Modulr\Concerns\ConfiguresCommands;
use Zen\Modulr\Support\ConfigStore;

class MakeMail extends MailMakeCommand
{
  use ConfiguresCommands;

  /**
   * Write the Markdown template for the mailable.
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
    $path = $viewPath.DIRECTORY_SEPARATOR.str_replace('.', $separator, $this->getView()).'.blade.php';

    if ($this->files->exists($path)) {
      $this->components->error(sprintf('%s [%s] already exists.', 'Markdown view', $path));

      return;
    }

    $this->files->ensureDirectoryExists(dirname($path));

    $stubPath = __DIR__.'/../../../../vendor/laravel/framework/src/Illuminate/Foundation/Console/stubs/markdown.stub';
    $contents = file_get_contents($stubPath);

    if ($contents === false) { // @codeCoverageIgnore
      $this->components->error('Could not read markdown stub file.'); // @codeCoverageIgnore

      return; // @codeCoverageIgnore
    }

    $this->files->put($path, $contents);

    $this->components->info(sprintf('%s [%s] created successfully.', 'Markdown view', $path));
  }
}
