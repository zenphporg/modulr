<?php

namespace Zen\Modulr\Support\PhpStorm;

use SimpleXMLElement;
use Zen\Modulr\Support\ConfigStore;

class LaravelConfigWriter extends ConfigWriter
{
  /**
   * @return bool
   */
  public function write(): bool
  {
    $plugin_config = $this->getNormalizedPluginConfig();
    $template_paths = $plugin_config->xpath('//templatePath');

    // Clean up template paths to prevent duplicates
    foreach ($template_paths as $template_path_key => $existing) {
      if ($this->module_registry->module((string) $existing['namespace']) instanceof ConfigStore) {
        unset($template_paths[$template_path_key][0]);
      }
    }

    // Now add all modules to the config
    $modules_directory = config('modulr.modules_directory', 'modules');
    $list = $plugin_config->xpath('//option[@name="templatePaths"]//list')[0];
    $this->module_registry->modules()
      ->sortBy('name')
      ->each(function (ConfigStore $module_config) use ($list, $modules_directory): void {
        $node = $list->addChild('templatePath');
        $node->addAttribute('namespace', $module_config->name);
        $node->addAttribute('path', "$modules_directory/$module_config->name/resources/views");
      });

    return file_put_contents($this->config_path, $this->formatXml($plugin_config)) !== false;
  }

  /**
   * @return \SimpleXMLElement
   */
  protected function getNormalizedPluginConfig(): SimpleXMLElement
  {
    $config = simplexml_load_string(file_get_contents($this->config_path));

    // Ensure that <component name="LaravelPluginSettings"> exists
    $component = $config->xpath('//component[@name="LaravelPluginSettings"]');
    if (empty($component)) {
      $component = $config->addChild('component');
      $component->addAttribute('name', 'LaravelPluginSettings');
    } else {
      $component = $component[0];
    }

    // Ensure that <option name="templatePaths"> exists
    $template_paths = $component->xpath('//option[@name="templatePaths"]');
    if (empty($template_paths)) {
      $template_paths = $component->addChild('option');
      $template_paths->addAttribute('name', 'templatePaths');
    } else {
      $template_paths = $template_paths[0];
    }

    // Ensure that <list> exists inside template paths config
    $list = $template_paths->xpath('//list');
    if (empty($list)) {
      $template_paths->addChild('list');
    }

    return $config;
  }
}
