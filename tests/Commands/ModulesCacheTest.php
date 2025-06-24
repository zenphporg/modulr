<?php

// TestCase applied via Pest.php
uses(\Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);

test('it writes to cache file', function () {
  $this->makeModule('test-module');
  $this->makeModule('test-module-two');

  $this->artisan('modules:cache');

  $expected_path = $this->getApplicationBasePath().$this->normalizeDirectorySeparators('bootstrap/cache/modules.php');

  expect($expected_path)->toBeFile();

  $cache = include $expected_path;

  expect($cache)->toHaveKey('test-module');
  expect($cache)->toHaveKey('test-module-two');
});
