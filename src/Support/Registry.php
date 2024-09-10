<?php

namespace Zen\Modulr\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;
use Zen\Modulr\Exceptions\CannotFindModuleForPathException;

class Registry
{
  /**
   * @var \Illuminate\Support\Collection|null
   */
  protected ?Collection $modules = null;

  /**
   * @param  string  $modules_path
   * @param  string  $cache_path
   */
  public function __construct(
    protected string $modules_path,
    protected string $cache_path
  ) {}

  /**
   * @return string
   */
  public function getModulesPath(): string
  {
    return $this->modules_path;
  }

  /**
   * @return string
   */
  public function getCachePath(): string
  {
    return $this->cache_path;
  }

  /**
   * @param  string|null  $name
   * @return \Zen\Modulr\Support\ConfigStore|null
   */
  public function module(?string $name = null): ?ConfigStore
  {
    // We want to allow for gracefully handling empty/null names
    return $name
        ? $this->modules()->get($name)
        : null;
  }

  /**
   * @param  string  $path
   * @return \Zen\Modulr\Support\ConfigStore|null
   */
  public function moduleForPath(string $path): ?ConfigStore
  {
    return $this->module($this->extractModuleNameFromPath($path));
  }

  /**
   * @param  string  $path
   * @return \Zen\Modulr\Support\ConfigStore
   *
   * @throws \Zen\Modulr\Exceptions\CannotFindModuleForPathException
   */
  public function moduleForPathOrFail(string $path): ConfigStore
  {
    if (($module = $this->moduleForPath($path)) instanceof ConfigStore) {
      return $module;
    }

    throw new CannotFindModuleForPathException($path);
  }

  /**
   * @param  string  $fqcn
   * @return \Zen\Modulr\Support\ConfigStore|null
   */
  public function moduleForClass(string $fqcn): ?ConfigStore
  {
    return $this->modules()->first(function (ConfigStore $module) use ($fqcn): bool {
      foreach ($module->namespaces as $namespace) {
        if (Str::startsWith($fqcn, $namespace)) {
          return true;
        }
      }

      return false;
    });
  }

  /**
   * @return \Illuminate\Support\Collection
   */
  public function modules(): Collection
  {
    return $this->modules ??= $this->loadModules();
  }

  /**
   * @return \Illuminate\Support\Collection
   */
  public function reload(): Collection
  {
    $this->modules = null;

    return $this->loadModules();
  }

  /**
   * @return \Illuminate\Support\Collection
   */
  protected function loadModules(): Collection
  {
    if (file_exists($this->cache_path)) {
      return Collection::make(require $this->cache_path)
        ->mapWithKeys(function (array $cached) {
          $config = new ConfigStore($cached['name'], $cached['base_path'], new Collection($cached['namespaces']));

          return [$config->name => $config];
        });
    }

    if (! is_dir($this->modules_path)) {
      return new Collection;
    }

    return FinderCollection::forFiles()
      ->depth('== 1')
      ->name('composer.json')
      ->in($this->modules_path)
      ->collect()
      ->mapWithKeys(function (SplFileInfo $path) {
        $config = ConfigStore::fromComposerFile($path);

        return [$config->name => $config];
      });
  }

  /**
   * @param  string  $path
   * @return string
   */
  protected function extractModuleNameFromPath(string $path): string
  {
    // Handle Windows-style paths
    $path = str_replace('\\', '/', $path);

    // If the modules directory is symlinked, we may get two paths that are actually
    // in the same directory, but have different prefixes. This helps resolve that.
    if (Str::startsWith($path, $this->modules_path)) {
      $path = trim(Str::after($path, $this->modules_path), '/');
    } elseif (Str::startsWith($path, $modules_real_path = str_replace('\\', '/', realpath($this->modules_path)))) {
      $path = trim(Str::after($path, $modules_real_path), '/');
    }

    return explode('/', $path)[0];
  }
}
