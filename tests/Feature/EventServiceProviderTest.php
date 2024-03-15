<?php

use App\Tests\ModularEventSeviceProviderTest\ForceEventDiscoveryProvider;
use Illuminate\Support\Facades\Event;
use Zen\Modulr\Providers\EventServiceProvider;

uses(Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);

test('it discovers event listeners', function () {
  $module = $this->makeModule();

  $this->artisan('make:event', ['name' => 'TestEvent', '--module' => $module->name]);
  $this->artisan('make:listener', ['name' => 'TestEventListener', '--event' => $module->qualify('Events\\TestEvent'), '--module' => $module->name]);

  // Because these are created after autoloading has finished, we need to manually load them
  require $module->path('src/Events/TestEvent.php');
  require $module->path('src/Listeners/TestEventListener.php');

  $this->app->register(new ForceEventDiscoveryProvider($this->app));
  $this->app->register(new EventServiceProvider($this->app), true);

  expect(Event::getListeners($module->qualify('Events\\TestEvent')))->not->toBeEmpty();

  // Also check that the events are cached correctly
  $this->artisan('event:cache');

  $cache = require $this->app->getCachedEventsPath();

  expect($cache[EventServiceProvider::class])->toHaveKey($module->qualify('Events\\TestEvent'));

  expect($cache[EventServiceProvider::class][$module->qualify('Events\\TestEvent')])->toContain($module->qualify('Listeners\\TestEventListener@handle'));

  $this->artisan('event:clear');
});

function shouldDiscoverEvents()
{
  return true;
}
