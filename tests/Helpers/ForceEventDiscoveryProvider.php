<?php

namespace App\Tests\ModularEventSeviceProviderTest;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class ForceEventDiscoveryProvider extends EventServiceProvider
{
  public function shouldDiscoverEvents()
  {
    return true;
  }
}
