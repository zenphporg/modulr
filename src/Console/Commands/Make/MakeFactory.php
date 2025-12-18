<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Console\Factories\FactoryMakeCommand;
use Illuminate\Support\Str;
use Override;
use Zen\Modulr\Concerns\ConfiguresCommands;
use Zen\Modulr\Support\ConfigStore;

class MakeFactory extends FactoryMakeCommand
{
  use ConfiguresCommands;

  /**
   * @param  string  $stub
   * @param  string  $name
   *
   * @throws BindingResolutionException
   */
  #[Override]
  protected function replaceNamespace(&$stub, $name): static // @pest-ignore-type
  {
    if (($module = $this->module()) instanceof ConfigStore) {
      /** @var string|null $modelOption */
      $modelOption = $this->option('model');
      $model = ($modelOption !== null && $modelOption !== '')
          ? $this->qualifyModel($modelOption)
          : $this->qualifyModel($this->guessModelName($name));

      $models_namespace = $module->qualify('Models');

      if (Str::startsWith($model, "$models_namespace\\")) {
        $extra_namespace = trim(Str::after(Str::beforeLast($model, '\\'), $models_namespace), '\\');
        $namespace = rtrim($module->qualify("Database\\Factories\\$extra_namespace"), '\\');
      } else {
        $namespace = $module->qualify('Database\\Factories');
      }

      $replacements = [
        '{{ factoryNamespace }}' => $namespace,
        '{{factoryNamespace}}' => $namespace,
        'namespace Database\Factories;' => "namespace $namespace;", // Early Laravel 8 didn't use a placeholder
      ];

      $stub = str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    return parent::replaceNamespace($stub, $name);
  }

  /**
   * @param  string  $name
   *
   * @throws BindingResolutionException
   */
  #[Override]
  protected function guessModelName($name): string // @pest-ignore-type
  {
    if (($module = $this->module()) instanceof ConfigStore) {
      if (Str::endsWith($name, 'Factory')) {
        $name = substr($name, 0, -7);
      }

      $modelName = $this->qualifyModel($name);
      if (class_exists($modelName)) {
        return $modelName;
      }

      return $module->qualify('Models\\Model');
    }

    return parent::guessModelName($name);
  }
}
