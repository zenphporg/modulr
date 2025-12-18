<?php

use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

// TestCase applied via Pest.php
uses(WritesToAppFilesystem::class);

test('it writes to cache file', function (): void {
  $this->artisan('modules:cache');

  $expected_path = $this->getApplicationBasePath().$this->normalizeDirectorySeparators('bootstrap/cache/modules.php');

  expect($expected_path)->toBeFile();

  $this->artisan('modules:clear');

  $this->assertFileDoesNotExist($expected_path);
});
