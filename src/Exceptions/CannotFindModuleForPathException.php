<?php

namespace Zen\Modulr\Exceptions;

use Throwable;

class CannotFindModuleForPathException extends Exception
{
  /**
   * @param  string  $path
   * @param  \Throwable|null  $previous
   */
  public function __construct(string $path, ?Throwable $previous = null)
  {
    parent::__construct("Unable to determine module for '$path'", 0, $previous);
  }
}
