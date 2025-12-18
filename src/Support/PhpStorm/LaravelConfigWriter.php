<?php

declare(strict_types=1);

namespace Zen\Modulr\Support\PhpStorm;

use RuntimeException;
use SimpleXMLElement;
use Zen\Modulr\Support\ConfigStore;

class LaravelConfigWriter extends ConfigWriter
{
  public function write(): bool
  {
    $normalizedPluginConfig = $this->getNormalizedPluginConfig();
    $template_paths = $normalizedPluginConfig->xpath('//templatePath') ?? [];

    // Clean up template paths to prevent duplicates
    foreach ($template_paths as $template_path_key => $existing) {
      if ($this->module_registry->module((string) $existing['namespace']) instanceof ConfigStore) {
        unset($template_paths[$template_path_key][0]);
      }
    }

    // Now add all modules to the config
    /** @var string $modules_directory */
    $modules_directory = config('modulr.modules_directory', 'modules');
    $listResult = $normalizedPluginConfig->xpath('//option[@name="templatePaths"]//list');
    throw_if(empty($listResult), RuntimeException::class, 'Could not find list element in config');
    $list = $listResult[0];

    $this->module_registry->modules()
      ->sortBy('name')
      ->each(function (ConfigStore $configStore) use ($list, $modules_directory): void {
        $node = $list->addChild('templatePath');
        if ($node !== null) {
          $node->addAttribute('namespace', $configStore->name);
          $node->addAttribute('path', "$modules_directory/$configStore->name/resources/views");
        }
      });

    return file_put_contents($this->config_path, $this->formatXml($normalizedPluginConfig)) !== false;
  }

  protected function getNormalizedPluginConfig(): SimpleXMLElement
  {
    $contents = file_get_contents($this->config_path);
    if ($contents === false) {
      throw new RuntimeException("Could not read config file: {$this->config_path}");
    }

    $config = simplexml_load_string($contents);
    if ($config === false) {
      throw new RuntimeException("Could not parse XML from: {$this->config_path}");
    }

    // Ensure that <component name="LaravelPluginSettings"> exists
    $componentResult = $config->xpath('//component[@name="LaravelPluginSettings"]');
    if (empty($componentResult)) {
      $component = $config->addChild('component');
      throw_if($component === null, RuntimeException::class, 'Could not add component element');
      $component->addAttribute('name', 'LaravelPluginSettings');
    } else {
      $component = $componentResult[0];
    }

    // Ensure that <option name="templatePaths"> exists
    $template_paths_result = $component->xpath('//option[@name="templatePaths"]');
    if (empty($template_paths_result)) {
      $template_paths = $component->addChild('option');
      throw_if($template_paths === null, RuntimeException::class, 'Could not add option element');
      $template_paths->addAttribute('name', 'templatePaths');
    } else {
      $template_paths = $template_paths_result[0];
    }

    // Ensure that <list> exists inside template paths config
    $list = $template_paths->xpath('//list');
    if (empty($list)) {
      $template_paths->addChild('list');
    }

    return $config;
  }
}
