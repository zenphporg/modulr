<?php

declare(strict_types=1);

namespace Zen\Modulr\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @implements Arrayable<string, mixed>
 */
final class ConfigStore implements Arrayable
{
  /**
   * @throws JsonException
   */
  public static function fromComposerFile(SplFileInfo $composer_file): self
  {
    /** @var array{autoload?: array{psr-4?: array<string, string>}} $composer_config */
    $composer_config = json_decode($composer_file->getContents(), true, 16, JSON_THROW_ON_ERROR);

    $base_path = rtrim(str_replace('\\', '/', $composer_file->getPath()), '/');

    $name = basename($base_path);

    /** @var Collection<string, string> $namespaces */
    $namespaces = Collection::make($composer_config['autoload']['psr-4'] ?? [])
      ->mapWithKeys(function (string $src, string $namespace) use ($base_path): array {
        $path = $base_path.'/'.$src;

        return [$path => $namespace];
      });

    return new self($name, $base_path, $namespaces);
  }

  /**
   * @param  Collection<string, string>  $namespaces
   */
  public function __construct(
    public string $name,
    public string $base_path,
    public Collection $namespaces = new Collection,
  ) {}

  public function path(string $to = ''): string
  {
    return rtrim($this->base_path.'/'.$to, '/');
  }

  public function namespace(): string
  {
    return $this->namespaces->first() ?? '';
  }

  public function qualify(string $class_name): string
  {
    return $this->namespace().ltrim($class_name, '\\');
  }

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
   * @return array{name: string, base_path: string, namespaces: array<string, string>}
   */
  public function toArray(): array
  {
    /** @var array<string, string> $namespacesArray */
    $namespacesArray = $this->namespaces->all();

    return [
      'name' => $this->name,
      'base_path' => $this->base_path,
      'namespaces' => $namespacesArray,
    ];
  }

  private function formatPathAsNamespace(string $path): string
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
