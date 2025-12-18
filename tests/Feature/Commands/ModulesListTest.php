<?php

use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

// TestCase applied via Pest.php
uses(WritesToAppFilesystem::class);

test('it writes to cache file', function (): void {
  $this->makeModule('test-module');

  $this->artisan('modules:list')
    ->expectsOutputToContain('module')
    ->assertExitCode(0);

  $this->makeModule('test-module-two');

  $this->artisan('modules:list')
    ->expectsOutputToContain('module')
    ->assertExitCode(0);
});
