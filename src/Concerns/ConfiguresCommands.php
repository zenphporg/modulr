<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace Zen\Modulr\Concerns;

use Illuminate\Support\Str;

trait ConfiguresCommands
{
  use GeneratesModules;

  /**
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
  protected function getDefaultNamespace($rootNamespace): array|string
  {
    $namespace = parent::getDefaultNamespace($rootNamespace);
    $module = $this->module();

    if ($module && ! str_contains($rootNamespace, $module->namespaces->first())) {
      $find = rtrim($rootNamespace, '\\');
      $replace = rtrim($module->namespaces->first(), '\\');
      $namespace = str_replace($find, $replace, $namespace);
    }

    return $namespace;
  }

  /**
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
  protected function qualifyClass($name): string
  {
    $name = ltrim($name, '\\/');

    if (($module = $this->module()) && Str::startsWith($name, $module->namespaces->first())) {
      return $name;
    }

    return parent::qualifyClass($name);
  }

  /**
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
  protected function qualifyModel(string $model): array|string
  {
    if ($module = $this->module()) {
      $model = str_replace('/', '\\', ltrim($model, '\\/'));

      if (Str::startsWith($model, $module->namespace())) {
        return $model;
      }

      return $module->qualify('Models\\'.$model);
    }

    return parent::qualifyModel($model);
  }

  /**
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
  protected function getPath($name): array|string
  {
    if ($module = $this->module()) {
      $name = Str::replaceFirst($module->namespaces->first(), '', $name);
    }

    $path = parent::getPath($name);

    if ($module) {
      // Set up our replacements as a [find -> replace] array
      $replacements = [
        $this->laravel->path() => $module->namespaces->keys()->first(),
        $this->laravel->basePath('tests/Tests') => $module->path('tests'),
        $this->laravel->databasePath() => $module->path('database'),
      ];

      // Normalize all our paths for compatibility's sake
      $normalize = function ($path): string {
        return rtrim($path, '/').'/';
      };

      $find = array_map($normalize, array_keys($replacements));
      $replace = array_map($normalize, array_values($replacements));

      // And finally apply the replacements
      $path = str_replace($find, $replace, $path);
    }

    return $path;
  }

  public function call($command, array $arguments = []): int
  {
    // Pass the --module flag on to subsequent commands
    if ($module = $this->option('module')) {
      $arguments['--module'] = $module;
    }

    return $this->runCommand($command, $arguments, $this->output);
  }
}
