<?php

namespace Zen\Modulr\Support\PhpStorm;

use DOMDocument;
use SimpleXMLElement;
use Zen\Modulr\Support\Registry;

abstract class ConfigWriter
{
  /**
   * @var string
   */
  public string $last_error;

  /**
   * @var string
   */
  protected string $config_path;

  /**
   * @var \Zen\Modulr\Support\Registry
   */
  protected Registry $module_registry;

  /**
   * @return bool
   */
  abstract public function write(): bool;

  /**
   * @param  $config_path
   * @param  \Zen\Modulr\Support\Registry  $module_registry
   */
  public function __construct(string $config_path, Registry $module_registry)
  {
    $this->config_path = $config_path;
    $this->module_registry = $module_registry;
  }

  /**
   * @return bool
   */
  public function handle(): bool
  {
    if (! $this->checkConfigFilePermissions()) {
      return false;
    }

    return $this->write();
  }

  /**
   * @return bool
   */
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

  /**
   * @param  string  $message
   * @return bool
   */
  protected function error(string $message): bool
  {
    $this->last_error = $message;

    return false;
  }

  /**
   * @param  \SimpleXMLElement  $xml
   * @return string
   */
  protected function formatXml(SimpleXMLElement $xml): string
  {
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;
    $dom->preserveWhiteSpace = false;
    $dom->loadXML($xml->asXML());

    $xml = $dom->saveXML();

    return preg_replace('~(\S)/>\s*$~m', '$1 />', $xml);
  }
}
