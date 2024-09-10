<?php

namespace Zen\Modulr\Console\Commands\Make;

use Illuminate\Database\Console\Factories\FactoryMakeCommand;
use Illuminate\Support\Str;
use Zen\Modulr\Concerns\ConfiguresCommands;

class MakeFactory extends FactoryMakeCommand
{
  use ConfiguresCommands;

  /**
   * @return \Zen\Modulr\Console\Commands\Make\MakeFactory
   *
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
  protected function replaceNamespace(&$stub, $name)
  {
    if (($module = $this->module()) instanceof \Zen\Modulr\Support\ConfigStore) {
      $model = $this->option('model')
          ? $this->qualifyModel($this->option('model'))
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
   * @return array|string
   *
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
  protected function guessModelName($name)
  {
    if (($module = $this->module()) instanceof \Zen\Modulr\Support\ConfigStore) {
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
