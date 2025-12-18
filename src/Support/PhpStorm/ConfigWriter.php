<?php

declare(strict_types=1);

namespace Zen\Modulr\Support\PhpStorm;

use DOMDocument;
use RuntimeException;
use SimpleXMLElement;
use Zen\Modulr\Support\Registry;

abstract class ConfigWriter
{
  public string $last_error = '';

  abstract public function write(): bool;

  public function __construct(protected string $config_path, protected Registry $module_registry) {}

  public function handle(): bool
  {
    if (! $this->checkConfigFilePermissions()) {
      return false;
    }

    return $this->write();
  }

  protected function checkConfigFilePermissions(): bool
  {
    if (! is_readable($this->config_path) || ! is_writable($this->config_path)) {
      return $this->error("Unable to find or read: '$this->config_path'");
    }

    if (! is_writable($this->config_path)) {
      return $this->error("Config file is not writable: '$this->config_path'");
    }

    return true;
  }

  protected function error(string $message): bool
  {
    $this->last_error = $message;

    return false;
  }

  protected function formatXml(SimpleXMLElement $xml): string
  {
    $domDocument = new DOMDocument('1.0', 'UTF-8');
    $domDocument->formatOutput = true;
    $domDocument->preserveWhiteSpace = false;

    $xmlString = $xml->asXML();
    throw_if($xmlString === false, RuntimeException::class, 'Failed to convert XML to string');

    $domDocument->loadXML($xmlString);

    $savedXml = $domDocument->saveXML();
    throw_if($savedXml === false, RuntimeException::class, 'Failed to save XML document');

    $result = preg_replace('~(\S)/>\s*$~m', '$1 />', $savedXml);

    return $result ?? $savedXml;
  }
}
