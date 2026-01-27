<?php

declare(strict_types=1);

namespace Zen\Modulr\Support\PhpStorm;

use Illuminate\Support\Collection;
use RuntimeException;
use SimpleXMLElement;
use Zen\Modulr\Support\ConfigStore;

class ProjectImlWriter extends ConfigWriter
{
  public function write(): bool
  {
    /** @var string $modules_directory */
    $modules_directory = config('modulr.modules_directory', 'modules');

    $normalizedPluginConfig = $this->getNormalizedPluginConfig();
    $source_folders = $normalizedPluginConfig->xpath('//component[@name="NewModuleRootManager"]//content[@url="file://$MODULE_DIR$"]//sourceFolder');

    /** @var Collection<int, string> $existing_urls */
    $existing_urls = collect($source_folders)
      ->map(fn (SimpleXMLElement $node): string => (string) $node['url']);

    // Now add all missing modules to the config
    $contentResult = $normalizedPluginConfig->xpath('//component[@name="NewModuleRootManager"]//content[@url="file://$MODULE_DIR$"]');
    throw_if(empty($contentResult), RuntimeException::class, 'Could not find content element in config');
    $content = $contentResult[0];

    $this->module_registry->modules()
      ->sortBy('name')
      ->each(function (ConfigStore $configStore) use ($content, $modules_directory, $existing_urls): void {
        $src_url = "file://\$MODULE_DIR\$/$modules_directory/$configStore->name/src";

        if ($existing_urls->doesntContain($src_url)) {
          $src_node = $content->addChild('sourceFolder');
          if ($src_node !== null) {
            $src_node->addAttribute('url', $src_url);
            $src_node->addAttribute('isTestSource', 'false');
            $src_node->addAttribute('packagePrefix', rtrim($configStore->namespaces->first() ?? '', '\\'));
          }
        }

        // Add Feature tests directory
        $featureTestsUrl = "file://\$MODULE_DIR\$/$modules_directory/$configStore->name/tests/Feature";
        if ($existing_urls->doesntContain($featureTestsUrl)) {
          $featureNode = $content->addChild('sourceFolder');
          if ($featureNode !== null) {
            $featureNode->addAttribute('url', $featureTestsUrl);
            $featureNode->addAttribute('isTestSource', 'true');
            $featureNode->addAttribute('packagePrefix', rtrim($configStore->namespaces->first() ?? '', '\\').'\\Tests\\Feature');
          }
        }

        // Add Unit tests directory
        $unitTestsUrl = "file://\$MODULE_DIR\$/$modules_directory/$configStore->name/tests/Unit";
        if ($existing_urls->doesntContain($unitTestsUrl)) {
          $unitNode = $content->addChild('sourceFolder');
          if ($unitNode !== null) {
            $unitNode->addAttribute('url', $unitTestsUrl);
            $unitNode->addAttribute('isTestSource', 'true');
            $unitNode->addAttribute('packagePrefix', rtrim($configStore->namespaces->first() ?? '', '\\').'\\Tests\\Unit');
          }
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

    $config = @simplexml_load_string($contents);
    if ($config === false) {
      throw new RuntimeException("Could not parse XML from: {$this->config_path}");
    }

    // Ensure that <component name="NewModuleRootManager"> exists
    $componentResult = $config->xpath('//component[@name="NewModuleRootManager"]');
    if (empty($componentResult)) {
      $component = $config->addChild('component');
      throw_if($component === null, RuntimeException::class, 'Could not add component element');
      $component->addAttribute('name', 'NewModuleRootManager');
    } else {
      $component = $componentResult[0];
    }

    // Ensure that <content url="file://$MODULE_DIR$"> exists
    $contentResult = $component->xpath('//content[@url="file://$MODULE_DIR$"]');
    if (empty($contentResult)) {
      $content = $component->addChild('content');
      throw_if($content === null, RuntimeException::class, 'Could not add content element');
      $content->addAttribute('url', 'file://$MODULE_DIR$');
    }

    return $config;
  }
}
