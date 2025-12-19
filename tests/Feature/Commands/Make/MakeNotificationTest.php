<?php

// TestCase applied via Pest.php
use Zen\Modulr\Console\Commands\Make\MakeModule;
use Zen\Modulr\Console\Commands\Make\MakeNotification;
use Zen\Modulr\Support\Registry;
use Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(TestsMakeCommands::class);

uses(WritesToAppFilesystem::class);

test('it overrides the default command', function (): void {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:notification', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a notification in the module when module option is set', function (): void {
  $command = MakeNotification::class;
  $arguments = ['name' => 'TestNotification'];
  $expected_path = 'src/Notifications/TestNotification.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Notifications',
    'class TestNotification',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a notification in the app when module option is missing', function (): void {
  $command = MakeNotification::class;
  $arguments = ['name' => 'TestNotification'];
  $expected_path = 'app/Notifications/TestNotification.php';
  $expected_substrings = [
    'namespace App\Notifications',
    'class TestNotification',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it creates markdown template in module when markdown option is set', function (): void {
  $module_name = 'test-module';

  $this->artisan(MakeModule::class, [
    'name' => $module_name,
    '--accept-namespace' => true,
  ])->assertExitCode(0);

  $this->app->make(Registry::class)->reload();

  $this->artisan(MakeNotification::class, [
    'name' => 'TestMarkdownNotification',
    '--module' => $module_name,
    '--markdown' => 'mail.test-markdown-notification',
  ])->assertExitCode(0);

  // Check that notification class was created
  $this->assertModuleFile('src/Notifications/TestMarkdownNotification.php', [
    'namespace Modules\TestModule\Notifications',
    'class TestMarkdownNotification',
  ], $module_name);

  // Check that markdown view was created in module
  $this->assertModuleFile('resources/views/mail/test-markdown-notification.blade.php', [
    '<x-mail::message>',
  ], $module_name);
});

test('it shows error when markdown view already exists in module', function (): void {
  $module_name = 'test-module';

  $this->artisan(MakeModule::class, [
    'name' => $module_name,
    '--accept-namespace' => true,
  ])->assertExitCode(0);

  $this->app->make(Registry::class)->reload();

  // Create the view file first
  $viewPath = $this->getModulePath($module_name, '/resources/views/mail');
  $this->filesystem()->ensureDirectoryExists($viewPath);
  $this->filesystem()->put($viewPath.'/existing-notification.blade.php', 'existing content');

  $this->artisan(MakeNotification::class, [
    'name' => 'ExistingNotification',
    '--module' => $module_name,
    '--markdown' => 'mail.existing-notification',
  ])->expectsOutputToContain('already exists')
    ->assertExitCode(0);
});

test('it creates markdown template in app when no module is set', function (): void {
  $this->artisan(MakeNotification::class, [
    'name' => 'AppMarkdownNotification',
    '--markdown' => 'mail.app-markdown-notification',
  ])->assertExitCode(0);

  // Check that notification class was created in app
  $this->assertBaseFile('app/Notifications/AppMarkdownNotification.php', [
    'namespace App\Notifications',
    'class AppMarkdownNotification',
  ]);

  // Check that markdown view was created in app resources
  $this->assertBaseFile('resources/views/mail/app-markdown-notification.blade.php', [
    '<x-mail::message>',
  ]);
});
