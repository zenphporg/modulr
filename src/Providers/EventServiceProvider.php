<?php

namespace Zen\Modulr\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as CoreProvider;
use Symfony\Component\Finder\SplFileInfo;
use Zen\Modulr\Support\AutoDiscoveryHelper;
use Zen\Modulr\Support\DiscoverEvents;

class EventServiceProvider extends CoreProvider
{
  /**
   * @return array|\Closure|null
   *
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
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
  public function shouldDiscoverEvents()
  {
    // We'll enable event discovery if it's enabled in the app namespace
    return collect($this->app->getProviders(CoreProvider::class))
      ->filter(fn (CoreProvider $provider): bool => str_starts_with(get_class($provider), $this->app->getNamespace()))
      ->contains(fn (CoreProvider $provider) => $provider->shouldDiscoverEvents());
  }

  /**
   * @return array
   *
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
  protected function discoverEventsWithin()
  {
    return $this->app->make(AutoDiscoveryHelper::class)
      ->listenerDirectoryFinder()
      ->map(fn (SplFileInfo $directory) => $directory->getPathname())
      ->values()
      ->all();
  }
}
