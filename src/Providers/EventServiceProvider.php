<?php

namespace Zen\Modulr\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as CoreProvider;
use Symfony\Component\Finder\SplFileInfo;
use Zen\Modulr\Support\AutoDiscoveryHelper;
use Zen\Modulr\Support\DiscoverEvents;

class EventServiceProvider extends CoreProvider
{
  public function discoverEvents()
  {
    return collect($this->discoverEventsWithin())
      ->reject(fn ($directory) => ! is_dir($directory))
      ->reduce(fn ($discovered, $directory) => array_merge_recursive(
        $discovered,
        DiscoverEvents::within($directory, $this->eventDiscoveryBasePath())
      ), []);
  }

  public function shouldDiscoverEvents()
  {
    // We'll enable event discovery if it's enabled in the app namespace
    return collect($this->app->getProviders(CoreProvider::class))
      ->filter(fn (CoreProvider $provider) => str_starts_with(get_class($provider), $this->app->getNamespace()))
      ->contains(fn (CoreProvider $provider) => $provider->shouldDiscoverEvents());
  }

  protected function discoverEventsWithin()
  {
    return $this->app->make(AutoDiscoveryHelper::class)
      ->listenerDirectoryFinder()
      ->map(fn (SplFileInfo $directory) => $directory->getPathname())
      ->values()
      ->all();
  }
}
