<?php

declare(strict_types=1);

namespace Zen\Modulr\Support;

use Override;
use SplFileInfo;
use Zen\Modulr\Support\Facades\Modulr;

class DiscoverEvents extends \Illuminate\Foundation\Events\DiscoverEvents
{
  /**
   * @return string
   */
  #[Override]
  protected static function classFromFile(SplFileInfo $file, $basePath)
  {
    if ($module = Modulr::moduleForPath($file->getRealPath())) {
      return $module->pathToFullyQualifiedClassName($file->getPathname());
    }

    return parent::classFromFile($file, $basePath);
  }
}
