<?php

// TestCase applied via Pest.php
uses(\Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);

test('it writes to cache file', function () {
    $this->artisan('modules:cache');

    $expected_path = $this->getApplicationBasePath().$this->normalizeDirectorySeparators('bootstrap/cache/modules.php');

    expect($expected_path)->toBeFile();

    $this->artisan('modules:clear');

    $this->assertFileDoesNotExist($expected_path);
});