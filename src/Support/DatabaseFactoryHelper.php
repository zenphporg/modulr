<?php

declare(strict_types=1);

namespace Zen\Modulr\Support;

use Closure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ReflectionException;
use ReflectionProperty;

class DatabaseFactoryHelper
{
  protected ?string $namespace = null;

  public function __construct(
    protected Registry $registry
  ) {}

  /**
   * @throws ReflectionException
   */
  public function resetResolvers(): void
  {
    $this->unsetProperty(Factory::class, 'modelNameResolver');
    $this->unsetProperty(Factory::class, 'factoryNameResolver');
  }

  /**
   * @return Closure(Factory<Model>):class-string<Model>
   */
  public function modelNameResolver(): Closure
  {
    // @phpstan-ignore return.type (closure returns class-string at runtime)
    return function (Factory $factory): string {
      if (($module = $this->registry->moduleForClass($factory::class)) instanceof ConfigStore) {
        return (string) Str::of($factory::class)
          ->replaceFirst($module->qualify($this->namespace()), '')
          ->replaceLast('Factory', '')
          ->prepend($module->qualify('Models'), '\\');
      }

      // For non-module factories, use Laravel's default logic directly
      // This avoids infinite recursion by not calling modelName() again
      $stringable = Str::of($factory::class)
        ->replaceLast('Factory', '')
        ->replaceLast('Database\\Factories\\', '')
        ->prepend('App\\Models\\');

      return (string) $stringable;
    };
  }

  /**
   * @return Closure(class-string<Model>):class-string<Factory<Model>>
   */
  public function factoryNameResolver(): Closure
  {
    // @phpstan-ignore return.type (closure returns class-string at runtime)
    return function (string $model_name): string {
      if (($module = $this->registry->moduleForClass($model_name)) instanceof ConfigStore) {
        $model_name = Str::startsWith($model_name, $module->qualify('Models\\'))
            ? Str::after($model_name, $module->qualify('Models\\'))
            : Str::after($model_name, $module->namespace());

        return $module->qualify($this->namespace().$model_name.'Factory');
      }

      // For non-module models, use Laravel's configured factory namespace
      // This avoids infinite recursion by not calling resolveFactoryName() again
      $namespace = $this->namespace();

      // Handle both App\Models\Foo and App\Foo patterns
      $stringable = Str::of($model_name)
        ->replaceFirst('App\\Models\\', $namespace)
        ->replaceFirst('App\\', $namespace)
        ->append('Factory');

      return (string) $stringable;
    };
  }

  /**
   * Because Factory::$namespace is protected, we need to access it via reflection.
   *
   * @throws ReflectionException
   */
  public function namespace(): string
  {
    // Don't cache the namespace since it can change via Factory::useNamespace()
    $value = $this->getProperty(Factory::class, 'namespace');

    return is_string($value) ? $value : '';
  }

  /**
   * @param  class-string  $target
   *
   * @throws ReflectionException
   */
  protected function getProperty(string $target, string $property): mixed
  {
    $reflectionProperty = new ReflectionProperty($target, $property);

    return $reflectionProperty->getValue();
  }

  /**
   * @param  class-string  $target
   *
   * @throws ReflectionException
   */
  protected function unsetProperty(string $target, string $property): void
  {
    $reflectionProperty = new ReflectionProperty($target, $property);

    $reflectionProperty->setValue(null);
  }
}
