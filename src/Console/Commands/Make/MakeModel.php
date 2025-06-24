<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Console\ModelMakeCommand;
use Illuminate\Support\Str;
use Override;
use Zen\Modulr\Concerns\ConfiguresCommands;
use Zen\Modulr\Support\ConfigStore;

class MakeModel extends ModelMakeCommand
{
  use ConfiguresCommands;

  /**
   * @return string
   *
   * @throws BindingResolutionException
   */
  #[Override]
  protected function getDefaultNamespace($rootNamespace)
  {
    if (($module = $this->module()) instanceof ConfigStore) {
      $rootNamespace = rtrim((string) $module->namespaces->first(), '\\');
    }

    return $rootNamespace.'\Models';
  }

  /**
   * Build the replacements for a factory.
   *
   * @return array<string, string>
   *
   * @throws BindingResolutionException
   */
  #[Override]
  protected function buildFactoryReplacements()
  {
    $replacements = [];

    if ($this->option('factory') || $this->option('all')) {
      $modelPath = Str::of($this->argument('name'))->studly()->replace('/', '\\')->toString();

      // Use module factory namespace if we're in a module
      if (($module = $this->module()) instanceof ConfigStore) {
        $factoryNamespace = $module->qualify("Database\\Factories\\{$modelPath}Factory");
      } else {
        $factoryNamespace = "\\Database\\Factories\\{$modelPath}Factory";
      }

      $factoryCode = <<<EOT
      /** @use HasFactory<$factoryNamespace> */
          use HasFactory;
      EOT;

      $replacements['{{ factory }}'] = $factoryCode;
      $replacements['{{ factoryImport }}'] = 'use Illuminate\Database\Eloquent\Factories\HasFactory;';
    } else {
      $replacements['{{ factory }}'] = '//';
      $replacements["{{ factoryImport }}\n"] = '';
      $replacements["{{ factoryImport }}\r\n"] = '';
    }

    return $replacements;
  }
}
