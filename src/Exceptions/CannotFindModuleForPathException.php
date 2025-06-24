<?php

declare(strict_types=1);

namespace Zen\Modulr\Exceptions;

use Throwable;

class CannotFindModuleForPathException extends Exception
{
  public function __construct(string $path, ?Throwable $throwable = null)
  {
    parent::__construct("Unable to determine module for '$path'", 0, $throwable);
  }
}
