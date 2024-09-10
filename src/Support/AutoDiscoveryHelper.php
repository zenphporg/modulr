<?php

namespace Zen\Modulr\Support;

use Illuminate\Filesystem\Filesystem;

class AutoDiscoveryHelper
{
  /**
   * @var string
   */
  protected string $base_path;

  /**
   * @param  \Zen\Modulr\Support\Registry  $module_registry
   * @param  \Illuminate\Filesystem\Filesystem  $filesystem
   */
  public function __construct(
    protected Registry $module_registry,
    protected Filesystem $filesystem
  ) {
    $this->base_path = $module_registry->getModulesPath();
  }

  /**
   * @return \Zen\Modulr\Support\FinderCollection
   */
  public function commandFileFinder(): FinderCollection
  {
    return FinderCollection::forFiles()
      ->name('*.php')
      ->inOrEmpty($this->base_path.'/*/src/Console/Commands');
  }

  /**
   * @return \Zen\Modulr\Support\FinderCollection
   */
  public function factoryDirectoryFinder(): FinderCollection
  {
    return FinderCollection::forDirectories()
      ->depth(0)
      ->name('factories')
      ->inOrEmpty($this->base_path.'/*/database/');
  }

  /**
   * @return \Zen\Modulr\Support\FinderCollection
   */
  public function migrationDirectoryFinder(): FinderCollection
  {
    return FinderCollection::forDirectories()
      ->depth(0)
      ->name('migrations')
      ->inOrEmpty($this->base_path.'/*/database/');
  }

  /**
   * @return \Zen\Modulr\Support\FinderCollection
   */
  public function modelFileFinder(): FinderCollection
  {
    return FinderCollection::forFiles()
      ->name('*.php')
      ->inOrEmpty($this->base_path.'/*/src/Models');
  }

  /**
   * @return \Zen\Modulr\Support\FinderCollection
   */
  public function bladeComponentFileFinder(): FinderCollection
  {
    return FinderCollection::forFiles()
      ->name('*.php')
      ->inOrEmpty($this->base_path.'/*/src/View/Components');
  }

  /**
   * @return \Zen\Modulr\Support\FinderCollection
   */
  public function bladeComponentDirectoryFinder(): FinderCollection
  {
    return FinderCollection::forDirectories()
      ->name('Components')
      ->inOrEmpty($this->base_path.'/*/src/View');
  }

  /**
   * @return \Zen\Modulr\Support\FinderCollection
   */
  public function routeFileFinder(): FinderCollection
  {
    return FinderCollection::forFiles()
      ->depth(0)
      ->name('*.php')
      ->sortByName()
      ->inOrEmpty($this->base_path.'/*/routes');
  }

  /**
   * @return \Zen\Modulr\Support\FinderCollection
   */
  public function viewDirectoryFinder(): FinderCollection
  {
    return FinderCollection::forDirectories()
      ->depth(0)
      ->name('views')
      ->inOrEmpty($this->base_path.'/*/resources/');
  }

  /**
   * @return \Zen\Modulr\Support\FinderCollection
   */
  public function langDirectoryFinder(): FinderCollection
  {
    return FinderCollection::forDirectories()
      ->depth(0)
      ->name('lang')
      ->inOrEmpty($this->base_path.'/*/resources/');
  }

  /**
   * @return \Zen\Modulr\Support\FinderCollection
   */
  public function listenerDirectoryFinder(): FinderCollection
  {
    return FinderCollection::forDirectories()
      ->name('Listeners')
      ->inOrEmpty($this->base_path.'/*/src');
  }
}
