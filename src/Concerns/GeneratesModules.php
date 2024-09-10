<?php

namespace Zen\Modulr\Concerns;

use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputOption;
use Zen\Modulr\Support\ConfigStore;
use Zen\Modulr\Support\Registry;

trait GeneratesModules
{
  /**
   * @throws \Illuminate\Contracts\Container\BindingResolutionException
   */
  protected function module(): ?ConfigStore
  {
    if ($name = $this->option('module')) {
      $registry = $this->getLaravel()->make(Registry::class);

      if ($module = $registry->module($name)) {
        return $module;
      }

      throw new InvalidOptionException(sprintf('The "%s" module does not exist.', $name));
    }

    return null;
  }

  /**
   * @noinspection PhpMultipleClassDeclarationsInspection
   */
  protected function configure(): void
  {
    parent::configure();

    $this->getDefinition()->addOption(
      new InputOption(
        '--module',
        null,
        InputOption::VALUE_REQUIRED,
        'Run inside an application module'
      )
    );
  }
}
