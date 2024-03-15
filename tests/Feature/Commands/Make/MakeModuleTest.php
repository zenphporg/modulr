<?php

use Zen\Modulr\Console\Commands\Make\MakeModule;
use Zen\Modulr\Support\Facades\Modulr;

uses(Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);

test('it scaffolds a new module', function () {
  $module_name = 'test-module';

  $this->artisan(MakeModule::class, [
    'name' => $module_name,
    '--accept-namespace' => true,
  ]);

  $fs = $this->filesystem();
  $module_path = $this->getBasePath().'/modules/'.$module_name;

  expect($fs->isDirectory($module_path))->toBeTrue();
  expect($fs->isDirectory($module_path.'/database'))->toBeTrue();
  expect($fs->isDirectory($module_path.'/resources'))->toBeTrue();
  expect($fs->isDirectory($module_path.'/routes'))->toBeTrue();
  expect($fs->isDirectory($module_path.'/src'))->toBeTrue();
  expect($fs->isDirectory($module_path.'/tests'))->toBeTrue();

  $composer_file = $module_path.'/composer.json';
  expect($fs->isFile($composer_file))->toBeTrue();

  $composer_contents = json_decode($fs->get($composer_file), true);

  expect($composer_contents['name'])->toEqual("modules/{$module_name}");
  expect($composer_contents['autoload']['psr-4']['Modules\\TestModule\\'])->toEqual('src/');
  expect($composer_contents['autoload']['psr-4']['Modules\\TestModule\\Tests\\'])->toEqual('tests/');
  expect($composer_contents['extra']['laravel']['providers'])->toContain('Modules\\TestModule\\Providers\\TestModuleServiceProvider');

  if (version_compare($this->app->version(), '8.0.0', '>=')) {
    expect($composer_contents['autoload']['psr-4']['Modules\\TestModule\\Database\\Factories\\'])->toEqual('database/factories/');
    expect($composer_contents['autoload']['psr-4']['Modules\\TestModule\\Database\\Seeders\\'])->toEqual('database/seeders/');
  } else {
    expect($composer_contents['autoload']['classmap'])->toContain('database/factories');
    expect($composer_contents['autoload']['classmap'])->toContain('database/seeds');
  }

  $app_composer_file = $this->getBasePath().'/composer.json';
  $app_composer_contents = json_decode($fs->get($app_composer_file), true);

  expect($app_composer_contents['require']["modules/{$module_name}"])->toEqual('*');

  $repository = [
    'type' => 'path',
    'url' => 'modules/*',
    'options' => ['symlink' => true],
  ];
  expect($app_composer_contents['repositories'])->toContain($repository);
});

test('it scaffolds a new module based on custom config', function () {
  $fs = $this->filesystem();

  $module_name = 'test-module';

  config()->set('modulr.stubs', [
    'src/StubClassNamePrefixInfo.php' => str_replace('\\', '/', dirname(__DIR__, 2)).'/stubs/test-stub.php',
  ]);

  $this->artisan(MakeModule::class, [
    'name' => $module_name,
    '--accept-namespace' => true,
  ]);

  $path = $this->getModulePath($module_name, '/src/TestModuleInfo.php');

  expect($fs->isFile($path))->toBeTrue();
  $this->assertStringContainsString($module_name, $fs->get($path));
});

test('it prompts on first module if no custom namespace is set', function () {
  $fs = $this->filesystem();

  $this->artisan(MakeModule::class, ['name' => 'test-module'])
      ->expectsQuestion('Would you like to cancel and configure your module namespace first?', false)
      ->assertExitCode(0);

  Modulr::reload();

  expect($fs->isDirectory($this->getBasePath().'/modules/test-module'))->toBeTrue();

  $this->artisan(MakeModule::class, ['name' => 'test-module-two'])
      ->assertExitCode(0);

  expect($fs->isDirectory($this->getBasePath().'/modules/test-module-two'))->toBeTrue();
});
