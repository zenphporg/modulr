<?php

declare(strict_types=1);

namespace Zen\Modulr\Support;

use Override;
use SplFileInfo;
use Zen\Modulr\Support\Facades\Modulr;

class DiscoverEvents extends \Illuminate\Foundation\Events\DiscoverEvents
{
  /**
   * @param  string  $basePath
   * @return class-string
   */
  #[Override]
  protected static function classFromFile(SplFileInfo $file, $basePath): string // @pest-ignore-type
  {
    if ($module = Modulr::moduleForPath($file->getRealPath())) {
      /** @var class-string $class */
      $class = $module->pathToFullyQualifiedClassName($file->getPathname());

      return $class;
    }

    return parent::classFromFile($file, $basePath);
  }
}
