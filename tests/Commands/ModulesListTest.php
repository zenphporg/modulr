<?php

// TestCase applied via Pest.php
uses(\Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);

test('it writes to cache file', function () {
    $this->makeModule('test-module');

    $this->artisan('modules:list')
      ->expectsOutputToContain('module')
      ->assertExitCode(0);

    $this->makeModule('test-module-two');

    $this->artisan('modules:list')
      ->expectsOutputToContain('module')
      ->assertExitCode(0);
});