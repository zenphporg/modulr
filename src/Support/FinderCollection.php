<?php

declare(strict_types=1);

namespace Zen\Modulr\Support;

use Generator;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Traits\ForwardsCalls;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @mixin LazyCollection<string, SplFileInfo>
 * @mixin Finder
 */
final class FinderCollection
{
  use ForwardsCalls;

  /** @var array<int, string> */
  private const array PREFER_COLLECTION_METHODS = ['filter', 'each', 'map'];

  public static function forFiles(): self
  {
    return new self(Finder::create()->files());
  }

  public static function forDirectories(): self
  {
    return new self(Finder::create()->directories());
  }

  /**
   * @param  LazyCollection<string, SplFileInfo>|null  $lazyCollection
   */
  public function __construct(
    private ?Finder $finder = null,
    private ?LazyCollection $lazyCollection = null,
  ) {
    if (! $this->finder && ! $this->lazyCollection) {
      /** @var LazyCollection<string, SplFileInfo> $emptyCollection */
      $emptyCollection = new LazyCollection;
      $this->lazyCollection = $emptyCollection;
    }
  }

  /**
   * @param  string|array<int, string>  $dirs
   */
  public function in(string|array $dirs): self
  {
    if ($this->finder instanceof Finder) {
      return new self($this->finder->in($dirs));
    }

    return $this;
  }

  /**
   * @param  string|array<int, string>  $dirs
   */
  public function inOrEmpty(string|array $dirs): self
  {
    try {
      return $this->in($dirs);
    } catch (DirectoryNotFoundException) {
      return new self;
    }
  }

  /**
   * @param  string|int|array<int, string|int>  $levels
   */
  public function depth(string|int|array $levels): self
  {
    if ($this->finder instanceof Finder) {
      return new self($this->finder->depth($levels));
    }

    return $this;
  }

  /**
   * @param  string|array<int, string>  $patterns
   */
  public function name(string|array $patterns): self
  {
    if ($this->finder instanceof Finder) {
      return new self($this->finder->name($patterns));
    }

    return $this;
  }

  public function sortByName(bool $useNaturalSort = false): self
  {
    if ($this->finder instanceof Finder) {
      return new self($this->finder->sortByName($useNaturalSort));
    }

    return $this;
  }

  /**
   * Convert the finder results to a LazyCollection.
   *
   * @return LazyCollection<string, SplFileInfo>
   */
  public function collect(): LazyCollection
  {
    return $this->forwardCollection();
  }

  /**
   * @param  array<int, mixed>  $arguments
   */
  public function __call(string $name, array $arguments): mixed
  {
    $result = $this->forwardCallTo($this->forwardCallTargetForMethod($name), $name, $arguments);

    if ($result instanceof Finder) {
      return new self($result);
    }

    if ($result instanceof LazyCollection) {
      return new self($this->finder, $result);
    }

    return $result;
  }

  /**
   * @return Finder|LazyCollection<string, SplFileInfo>
   */
  private function forwardCallTargetForMethod(string $name): Finder|LazyCollection
  {
    if ($this->finder instanceof Finder && is_callable([$this->finder, $name]) && ! in_array($name, self::PREFER_COLLECTION_METHODS)) {
      return $this->finder;
    }

    return $this->forwardCollection();
  }

  /**
   * @return LazyCollection<string, SplFileInfo>
   */
  private function forwardCollection(): LazyCollection
  {
    return $this->lazyCollection ??= new LazyCollection(function (): Generator {
      if ($this->finder instanceof Finder) {
        foreach ($this->finder as $key => $value) {
          yield $key => $value;
        }
      }
    });
  }
}
