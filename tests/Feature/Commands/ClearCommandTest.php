<?php

use Zen\Modulr\Console\Commands\CacheCommand;
use Zen\Modulr\Console\Commands\ClearCommand;

uses(Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);

test('it writes to cache file', function () {
  $this->artisan(CacheCommand::class);

  $expected_path = $this->getBasePath().$this->normalizeDirectorySeparators('bootstrap/cache/modules.php');

  expect($expected_path)->toBeFile();

  $this->artisan(ClearCommand::class);

  $this->assertFileDoesNotExist($expected_path);
});
