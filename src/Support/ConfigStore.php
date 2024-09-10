<?php

namespace Zen\Modulr\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;

class ConfigStore implements Arrayable
{
  /**
   * @var \Illuminate\Support\Collection
   */
  public Collection $namespaces;

  /**
   * @param  \Symfony\Component\Finder\SplFileInfo  $composer_file
   * @return self
   *
   * @throws \JsonException
   */
  public static function fromComposerFile(SplFileInfo $composer_file): self
  {
    $composer_config = json_decode($composer_file->getContents(), true, 16, JSON_THROW_ON_ERROR);

    $base_path = rtrim(str_replace('\\', '/', $composer_file->getPath()), '/');

    $name = basename($base_path);

    $namespaces = Collection::make($composer_config['autoload']['psr-4'] ?? [])
      ->mapWithKeys(function (string $src, $namespace) use ($base_path) {
        $path = $base_path.'/'.$src;

        return [$path => $namespace];
      });

    return new static($name, $base_path, $namespaces);
  }

  /**
   * @param  string  $name
   * @param  string  $base_path
   * @param  \Illuminate\Support\Collection|null  $namespaces
   */
  public function __construct(
    public string $name,
    public string $base_path,
    ?Collection $namespaces = null
  ) {
    $this->namespaces = $namespaces ?? new Collection;
  }

  /**
   * @param  string  $to
   * @return string
   */
  public function path(string $to = ''): string
  {
    return rtrim($this->base_path.'/'.$to, '/');
  }

  /**
   * @return string
   */
  public function namespace(): string
  {
    return $this->namespaces->first();
  }

  /**
   * @param  string  $class_name
   * @return string
   */
  public function qualify(string $class_name): string
  {
    return $this->namespace().ltrim($class_name, '\\');
  }

  /**
   * @param  string  $path
   * @return string
   */
  public function pathToFullyQualifiedClassName(string $path): string
  {
    // Handle Windows-style paths
    $path = str_replace('\\', '/', $path);

    foreach ($this->namespaces as $namespace_path => $namespace) {
      if (str_starts_with($path, $namespace_path)) {
        $relative_path = Str::after($path, $namespace_path);

        return $namespace.$this->formatPathAsNamespace($relative_path);
      }
    }

    throw new RuntimeException("Unable to infer qualified class name for '$path'");
  }

  /**
   * @return array
   */
  public function toArray(): array
  {
    return [
      'name' => $this->name,
      'base_path' => $this->base_path,
      'namespaces' => $this->namespaces->toArray(),
    ];
  }

  /**
   * @param  string  $path
   * @return string
   */
  protected function formatPathAsNamespace(string $path): string
  {
    $path = trim($path, '/');

    $replacements = [
      '/' => '\\',
      '.php' => '',
    ];

    return str_replace(
      array_keys($replacements),
      array_values($replacements),
      $path
    );
  }
}
