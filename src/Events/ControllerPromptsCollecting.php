<?php

declare(strict_types=1);

namespace Zen\Modulr\Events;

class ControllerPromptsCollecting
{
  /**
   * Additional options collected from listeners.
   *
   * @var array<string, mixed>
   */
  protected array $additionalOptions = [];

  public function __construct(
    public readonly string $controllerType,
    public readonly string $moduleName,
    public readonly string $className,
  ) {}

  /**
   * Add an additional option to be passed to make:controller.
   */
  public function addOption(string $key, mixed $value): void
  {
    $this->additionalOptions[$key] = $value;
  }

  /**
   * Get all additional options.
   *
   * @return array<string, mixed>
   */
  public function getAdditionalOptions(): array
  {
    return $this->additionalOptions;
  }
}
