<?php

declare(strict_types=1);

namespace Zen\Modulr\Providers;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as CoreProvider;
use Override;
use Symfony\Component\Finder\SplFileInfo;
use Zen\Modulr\Support\AutoDiscoveryHelper;
use Zen\Modulr\Support\DiscoverEvents;

class EventServiceProvider extends CoreProvider
{
  /**
   * @return array|Closure|null
   *
   * @throws BindingResolutionException
   */
  #[Override]
  public function discoverEvents()
  {
    return collect($this->discoverEventsWithin())
      ->reject(fn ($directory): bool => ! is_dir($directory))
      ->reduce(fn ($discovered, $directory): array => array_merge_recursive(
        $discovered,
        DiscoverEvents::within($directory, $this->eventDiscoveryBasePath())
      ), []);
  }

  /**
   * @return bool
   */
  #[Override]
  public function shouldDiscoverEvents()
  {
    // We'll enable event discovery if it's enabled in the app namespace
    return collect($this->app->getProviders(CoreProvider::class))
      ->filter(fn (CoreProvider $coreProvider): bool => str_starts_with($coreProvider::class, $this->app->getNamespace()))
      ->contains(fn (CoreProvider $coreProvider) => $coreProvider->shouldDiscoverEvents());
  }

  /**
   * @return array
   *
   * @throws BindingResolutionException
   */
  #[Override]
  protected function discoverEventsWithin()
  {
    return $this->app->make(AutoDiscoveryHelper::class)
      ->listenerDirectoryFinder()
      ->map(fn (SplFileInfo $directory) => $directory->getPathname())
      ->values()
      ->all();
  }
}
