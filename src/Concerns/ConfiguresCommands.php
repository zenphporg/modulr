<?php

declare(strict_types=1);

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace Zen\Modulr\Concerns;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait ConfiguresCommands
{
  use GeneratesModules;

  /**
   * Skip Laravel's interactive prompts when called programmatically.
   *
   * Laravel's make commands use afterPromptingForMissingArguments to prompt
   * for additional options. When called via callSilently() from MakeModule,
   * these prompts would hang. We skip them since MakeModule handles all
   * interactive prompts before calling the individual make commands.
   */
  protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
  {
    // Skip parent's interactive prompts - MakeModule handles prompts
  }

  /**
   * @param  string  $rootNamespace
   *
   * @throws BindingResolutionException
   */
  protected function getDefaultNamespace($rootNamespace): string
  {
    /** @var string $namespace */
    $namespace = parent::getDefaultNamespace($rootNamespace);
    $module = $this->module();

    if ($module === null) {
      return $namespace;
    }

    $rootNamespaceStr = (string) $rootNamespace;
    $firstNamespace = $module->namespaces->first();

    if (is_string($firstNamespace) && ! str_contains($rootNamespaceStr, $firstNamespace)) {
      $find = rtrim($rootNamespaceStr, '\\');
      $replace = rtrim($firstNamespace, '\\');
      $namespace = str_replace($find, $replace, $namespace);
    }

    return $namespace;
  }

  /**
   * @param  string  $name
   *
   * @throws BindingResolutionException
   */
  protected function qualifyClass($name): string
  {
    $name = ltrim((string) $name, '\\/');
    $module = $this->module();

    if ($module === null) {
      return parent::qualifyClass($name);
    }

    $firstNamespace = $module->namespaces->first();

    if (is_string($firstNamespace) && Str::startsWith($name, $firstNamespace)) {
      return $name;
    }

    return parent::qualifyClass($name);
  }

  /**
   * @throws BindingResolutionException
   */
  protected function qualifyModel(string $model): string
  {
    $module = $this->module();

    if ($module === null) {
      return parent::qualifyModel($model);
    }

    $model = str_replace('/', '\\', ltrim($model, '\\/'));

    if (Str::startsWith($model, $module->namespace())) {
      return $model;
    }

    return $module->qualify('Models\\'.$model);
  }

  /**
   * @param  string  $name
   *
   * @throws BindingResolutionException
   */
  protected function getPath($name): string
  {
    $module = $this->module();

    if ($module !== null) {
      $firstNamespace = $module->namespaces->first();
      if (is_string($firstNamespace)) {
        $name = Str::replaceFirst($firstNamespace, '', (string) $name);
      }
    }

    /** @var string $path */
    $path = parent::getPath($name);

    if ($module !== null) {
      $firstKey = $module->namespaces->keys()->first();
      // Set up our replacements as a [find -> replace] array
      $replacements = [
        $this->laravel->path() => is_string($firstKey) ? $firstKey : '',
        $this->laravel->basePath('tests/Tests') => $module->path('tests'),
        $this->laravel->databasePath() => $module->path('database'),
      ];

      // Normalize all our paths for compatibility's sake
      $normalize = fn (string $p): string => rtrim($p, '/').'/';

      $find = array_map($normalize, array_keys($replacements));
      $replace = array_map($normalize, array_values($replacements));

      // And finally apply the replacements
      $path = str_replace($find, $replace, $path);
    }

    return $path;
  }

  /**
   * @param  string  $command
   * @param  array<string, mixed>  $arguments
   */
  public function call($command, array $arguments = []): int
  {
    // Pass the --module flag on to subsequent commands
    if ($module = $this->option('module')) {
      $arguments['--module'] = $module;
    }

    return $this->runCommand($command, $arguments, $this->output);
  }
}
