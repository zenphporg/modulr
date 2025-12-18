<?php

declare(strict_types=1);

namespace Zen\Modulr\Support\PhpStorm;

use Illuminate\Support\Str;
use RuntimeException;
use Zen\Modulr\Support\ConfigStore;

class PhpFrameworkWriter extends ConfigWriter
{
  public function write(): bool
  {
    $contents = file_get_contents($this->config_path);
    if ($contents === false) {
      throw new RuntimeException("Could not read config file: {$this->config_path}");
    }

    $config = simplexml_load_string($contents);
    if ($config === false) {
      throw new RuntimeException("Could not parse XML from: {$this->config_path}");
    }

    $pathsResult = $config->xpath('//component[@name="PhpIncludePathManager"]//include_path//path');
    if (empty($pathsResult)) {
      return true;
    }

    /** @var string $namespace */
    $namespace = config('modulr.modules_namespace', 'Modules');
    $vendorConfig = config('modulr.modules_vendor');
    $vendor = is_string($vendorConfig) ? $vendorConfig : Str::kebab($namespace);
    $module_paths = $this->module_registry->modules()
      ->map(fn (ConfigStore $configStore): string => '$PROJECT_DIR$/vendor/'.$vendor.'/'.$configStore->name);

    $include_paths = $config->xpath('//component[@name="PhpIncludePathManager"]//include_path//path') ?? [];

    foreach ($include_paths as $key => $existing) {
      if ($module_paths->contains((string) $existing['value'])) {
        unset($include_paths[$key][0]);
      }
    }

    return file_put_contents($this->config_path, $this->formatXml($config)) !== false;
  }
}
