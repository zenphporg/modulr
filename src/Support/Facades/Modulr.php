<?php

namespace Zen\Modulr\Support\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Zen\Modulr\Support\ConfigStore;
use Zen\Modulr\Support\Registry;

/**
 * @method static ConfigStore|null module(string $name)
 * @method static ConfigStore|null moduleForPath(string $path)
 * @method static ConfigStore|null moduleForClass(string $fqcn)
 * @method static Collection modules()
 * @method static Collection reload()
 *
 * @see Registry
 */
class Modulr extends Facade
{
  protected static function getFacadeAccessor(): string
  {
    return Registry::class;
  }
}
