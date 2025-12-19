<?php

// TestCase applied via Pest.php
use Zen\Modulr\Console\Commands\Make\MakeMail;
use Zen\Modulr\Console\Commands\Make\MakeModule;
use Zen\Modulr\Support\Registry;
use Zen\Modulr\Tests\Feature\Concerns\TestsMakeCommands;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(TestsMakeCommands::class);

uses(WritesToAppFilesystem::class);

test('it overrides the default command', function (): void {
  $this->requiresLaravelVersion('11.0');

  $this->artisan('make:mail', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a mail in the module when module option is set', function (): void {
  $command = MakeMail::class;
  $arguments = ['name' => 'TestMail'];
  $expected_path = 'src/Mail/TestMail.php';
  $expected_substrings = [
    'namespace Modules\TestModule\Mail',
    'class TestMail',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
});

test('it scaffolds a mail in the app when module option is missing', function (): void {
  $command = MakeMail::class;
  $arguments = ['name' => 'TestMail'];
  $expected_path = 'app/Mail/TestMail.php';
  $expected_substrings = [
    'namespace App\Mail',
    'class TestMail',
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

  $this->artisan(MakeMail::class, [
    'name' => 'TestMarkdownMail',
    '--module' => $module_name,
    '--markdown' => 'mail.test-markdown-mail',
  ])->assertExitCode(0);

  // Check that mail class was created
  $this->assertModuleFile('src/Mail/TestMarkdownMail.php', [
    'namespace Modules\TestModule\Mail',
    'class TestMarkdownMail',
  ], $module_name);

  // Check that markdown view was created in module
  $this->assertModuleFile('resources/views/mail/test-markdown-mail.blade.php', [
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
  $this->filesystem()->put($viewPath.'/existing-mail.blade.php', 'existing content');

  $this->artisan(MakeMail::class, [
    'name' => 'ExistingMail',
    '--module' => $module_name,
    '--markdown' => 'mail.existing-mail',
  ])->expectsOutputToContain('already exists')
    ->assertExitCode(0);
});

test('it creates markdown template in app when no module is set', function (): void {
  $this->artisan(MakeMail::class, [
    'name' => 'AppMarkdownMail',
    '--markdown' => 'mail.app-markdown-mail',
  ])->assertExitCode(0);

  // Check that mail class was created in app
  $this->assertBaseFile('app/Mail/AppMarkdownMail.php', [
    'namespace App\Mail',
    'class AppMarkdownMail',
  ]);

  // Check that markdown view was created in app resources
  $this->assertBaseFile('resources/views/mail/app-markdown-mail.blade.php', [
    '<x-mail::message>',
  ]);
});
