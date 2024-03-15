<?php

use Zen\Modulr\Console\Commands\ListCommand;

uses(Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);

it('writes to cache file', function () {
  $this->makeModule('test-module');

  $this->artisan(ListCommand::class)
    ->expectsOutput('You have 1 module installed.')
    ->assertExitCode(0);

  $this->makeModule('test-module-two');

  $this->artisan(ListCommand::class)
    ->expectsOutput('You have 2 modules installed.')
    ->assertExitCode(0);
});
