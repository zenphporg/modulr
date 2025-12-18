<?php

declare(strict_types=1);

namespace Zen\Modulr\Providers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as CoreProvider;
use Illuminate\Support\Collection;
use Override;
use Symfony\Component\Finder\SplFileInfo;
use Zen\Modulr\Support\AutoDiscoveryHelper;
use Zen\Modulr\Support\DiscoverEvents;

class EventServiceProvider extends CoreProvider
{
  /**
   * @return array<class-string, array<int, class-string>>
   *
   * @throws BindingResolutionException
   */
  #[Override]
  public function discoverEvents(): array
  {
    /** @var array<class-string, array<int, class-string>> $result */
    $result = collect($this->discoverEventsWithin())
      ->filter(fn (string $directory): bool => is_dir($directory))
      ->reduce(fn (array $discovered, string $directory): array => array_merge_recursive(
        $discovered,
        DiscoverEvents::within($directory, $this->eventDiscoveryBasePath())
      ), []);

    return $result;
  }

  #[Override]
  public function shouldDiscoverEvents(): bool
  {
    // We'll enable event discovery if it's enabled in the app namespace
    /** @var Collection<int, CoreProvider> $providers */
    $providers = collect($this->app->getProviders(CoreProvider::class));

    return $providers
      ->filter(fn (CoreProvider $coreProvider): bool => str_starts_with($coreProvider::class, $this->app->getNamespace()))
      ->contains(fn (CoreProvider $coreProvider): bool => $coreProvider->shouldDiscoverEvents());
  }

  /**
   * @return array<int, string>
   *
   * @throws BindingResolutionException
   */
  #[Override]
  protected function discoverEventsWithin(): array
  {
    return $this->app->make(AutoDiscoveryHelper::class)
      ->listenerDirectoryFinder()
      ->map(fn (SplFileInfo $directory): string => $directory->getPathname())
      ->values()
      ->all();
  }
}
