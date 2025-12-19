<?php

use Illuminate\Filesystem\Filesystem;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

// TestCase applied via Pest.php
uses(WritesToAppFilesystem::class);

test('it writes to cache file', function (): void {
  $this->makeModule('test-module');
  $this->makeModule('test-module-two');

  $this->artisan('modules:cache');

  $expected_path = $this->getApplicationBasePath().$this->normalizeDirectorySeparators('bootstrap/cache/modules.php');

  expect($expected_path)->toBeFile();

  $cache = include $expected_path;

  expect($cache)->toHaveKey('test-module');
  expect($cache)->toHaveKey('test-module-two');
});

test('it throws exception when cache file is invalid', function (): void {
  $this->makeModule('test-module');

  // Mock the filesystem to write invalid PHP
  $filesystem = Mockery::mock(Filesystem::class)->makePartial();
  $filesystem->shouldReceive('put')
    ->andReturnUsing(function ($path): true {
      // Write invalid PHP to the cache file
      file_put_contents($path, '<?php invalid php syntax here {{{');

      return true;
    });

  $this->app->instance(Filesystem::class, $filesystem);

  expect(fn () => $this->artisan('modules:cache'))
    ->toThrow(LogicException::class, 'Unable to cache module configuration');
});
