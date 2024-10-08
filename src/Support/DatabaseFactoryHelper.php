<?php

namespace Zen\Modulr\Support;

use Closure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use ReflectionProperty;

class DatabaseFactoryHelper
{
  protected ?string $namespace = null;

  public function __construct(
    protected Registry $registry
  ) {}

  /**
   * @throws \ReflectionException
   */
  public function resetResolvers(): void
  {
    $this->unsetProperty(Factory::class, 'modelNameResolver');
    $this->unsetProperty(Factory::class, 'factoryNameResolver');
  }

  public function modelNameResolver(): Closure
  {
    return function (Factory $factory) {
      if (($module = $this->registry->moduleForClass(get_class($factory))) instanceof ConfigStore) {
        return (string) Str::of(get_class($factory))
          ->replaceFirst($module->qualify($this->namespace()), '')
          ->replaceLast('Factory', '')
          ->prepend($module->qualify('Models'), '\\');
      }

      // Temporarily disable the modular resolver if we're not in a module
      try {
        $this->unsetProperty(Factory::class, 'modelNameResolver');

        return $factory->modelName();
      } finally {
        Factory::guessModelNamesUsing($this->modelNameResolver());
      }
    };
  }

  public function factoryNameResolver(): Closure
  {
    return function ($model_name) {
      if (($module = $this->registry->moduleForClass($model_name)) instanceof ConfigStore) {
        $model_name = Str::startsWith($model_name, $module->qualify('Models\\'))
            ? Str::after($model_name, $module->qualify('Models\\'))
            : Str::after($model_name, $module->namespace());

        return $module->qualify($this->namespace().$model_name.'Factory');
      }

      // Temporarily disable the modular resolver if we're not in a module
      try {
        $this->unsetProperty(Factory::class, 'factoryNameResolver');

        return Factory::resolveFactoryName($model_name);
      } finally {
        Factory::guessFactoryNamesUsing($this->factoryNameResolver());
      }
    };
  }

  /**
   * Because Factory::$namespace is protected, we need to access it via reflection.
   *
   * @throws \ReflectionException
   */
  public function namespace(): string
  {
    return $this->namespace ??= $this->getProperty(Factory::class, 'namespace');
  }

  /**
   * @return mixed
   *
   * @throws \ReflectionException
   */
  protected function getProperty($target, $property)
  {
    $reflection = new ReflectionProperty($target, $property);

    return $reflection->getValue();
  }

  /**
   * @throws \ReflectionException
   */
  protected function unsetProperty($target, $property): void
  {
    $reflection = new ReflectionProperty($target, $property);

    $reflection->setValue(null);
  }
}
