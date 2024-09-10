<?php

namespace Zen\Modulr\Support;

use SplFileInfo;
use Zen\Modulr\Support\Facades\Modulr;

class DiscoverEvents extends \Illuminate\Foundation\Events\DiscoverEvents
{
  /**
   * @return string
   */
  protected static function classFromFile(SplFileInfo $file, $basePath)
  {
    if ($module = Modulr::moduleForPath($file->getRealPath())) {
      return $module->pathToFullyQualifiedClassName($file->getPathname());
    }

    return parent::classFromFile($file, $basePath);
  }
}
