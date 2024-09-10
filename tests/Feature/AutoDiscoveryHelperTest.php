<?php

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;
use Zen\Modulr\Console\Commands\Make\MakeCommand;
use Zen\Modulr\Console\Commands\Make\MakeComponent;
use Zen\Modulr\Console\Commands\Make\MakeListener;
use Zen\Modulr\Console\Commands\Make\MakeModel;
use Zen\Modulr\Support\AutoDiscoveryHelper;
use Zen\Modulr\Support\Registry;

uses(Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);

beforeEach(function () {
  $this->module1 = $this->makeModule('test-module');
  $this->module2 = $this->makeModule('test-module-two');
  $this->helper = new AutoDiscoveryHelper(
    new Registry($this->getBasePath().'/modules', ''),
    new Filesystem
  );
});

test('it finds commands', function () {
  $this->artisan(MakeCommand::class, [
    'name' => 'TestCommand',
    '--module' => $this->module1->name,
  ]);

  $this->artisan(MakeCommand::class, [
    'name' => 'TestCommand',
    '--module' => $this->module2->name,
  ]);

  $resolved = [];

  $this->helper->commandFileFinder()->each(function (SplFileInfo $command) use (&$resolved) {
    $resolved[] = str_replace('\\', '/', $command->getPathname());
  });

  expect($resolved)->toContain($this->module1->path('src/Console/Commands/TestCommand.php'));
  expect($resolved)->toContain($this->module2->path('src/Console/Commands/TestCommand.php'));
});

test('it finds factory directories', function () {
  $resolved = [];

  $this->helper->factoryDirectoryFinder()->each(function (SplFileInfo $directory) use (&$resolved) {
    $resolved[] = str_replace('\\', '/', $directory->getPathname());
  });

  expect($resolved)->toContain($this->module1->path('database/factories'));
  expect($resolved)->toContain($this->module2->path('database/factories'));
});

test('it finds migration directories', function () {
  $resolved = [];

  $this->helper->migrationDirectoryFinder()->each(function (SplFileInfo $directory) use (&$resolved) {
    $resolved[] = str_replace('\\', '/', $directory->getPathname());
  });

  expect($resolved)->toContain($this->module1->path('database/migrations'));
  expect($resolved)->toContain($this->module2->path('database/migrations'));
});

test('it finds models', function () {
  $this->artisan(MakeModel::class, [
    'name' => 'TestModel',
    '--module' => $this->module1->name,
  ]);

  $this->artisan(MakeModel::class, [
    'name' => 'TestModel',
    '--module' => $this->module2->name,
  ]);

  $resolved = [];

  $this->helper->modelFileFinder()->each(function (SplFileInfo $file) use (&$resolved) {
    $resolved[] = str_replace('\\', '/', $file->getPathname());
  });

  expect($resolved)->toContain($this->module1->path('src/Models/TestModel.php'));
  expect($resolved)->toContain($this->module2->path('src/Models/TestModel.php'));
});

test('it finds blade components', function () {
  $this->artisan(MakeComponent::class, [
    'name' => 'TestComponent',
    '--module' => $this->module1->name,
  ]);

  $this->artisan(MakeComponent::class, [
    'name' => 'TestComponent',
    '--module' => $this->module2->name,
  ]);

  $resolved_directories = [];
  $resolved_files = [];

  $this->helper->bladeComponentDirectoryFinder()->each(function (SplFileInfo $file) use (&$resolved_directories) {
    $resolved_directories[] = str_replace('\\', '/', $file->getPathname());
  });

  $this->helper->bladeComponentFileFinder()->each(function (SplFileInfo $file) use (&$resolved_files) {
    $resolved_files[] = str_replace('\\', '/', $file->getPathname());
  });

  expect($resolved_directories)->toContain($this->module1->path('src/View/Components'));
  expect($resolved_directories)->toContain($this->module2->path('src/View/Components'));

  expect($resolved_files)->toContain($this->module1->path('src/View/Components/TestComponent.php'));
  expect($resolved_files)->toContain($this->module2->path('src/View/Components/TestComponent.php'));
});

test('it finds routes', function () {
  $resolved = [];

  $this->helper->routeFileFinder()->each(function (SplFileInfo $file) use (&$resolved) {
    $resolved[] = str_replace('\\', '/', $file->getPathname());
  });

  expect($resolved)->toContain($this->module1->path("routes/{$this->module1->name}-routes.php"));
  expect($resolved)->toContain($this->module2->path("routes/{$this->module2->name}-routes.php"));
});

test('it finds view directories', function () {
  $resolved = [];

  $this->helper->viewDirectoryFinder()->each(function (SplFileInfo $directory) use (&$resolved) {
    $resolved[] = str_replace('\\', '/', $directory->getPathname());
  });

  expect($resolved)->toContain($this->module1->path('resources/views'));
  expect($resolved)->toContain($this->module2->path('resources/views'));
});

test('it finds lang directories', function () {
  // These paths don't exist by default
  $fs = new Filesystem;
  $fs->makeDirectory($this->module1->path('resources/lang'));
  $fs->makeDirectory($this->module2->path('resources/lang'));

  $resolved = [];

  $this->helper->langDirectoryFinder()->each(function (SplFileInfo $directory) use (&$resolved) {
    $resolved[] = str_replace('\\', '/', $directory->getPathname());
  });

  expect($resolved)->toContain($this->module1->path('resources/lang'));
  expect($resolved)->toContain($this->module2->path('resources/lang'));
});

test('it finds event listeners', function () {
  $this->artisan(MakeListener::class, [
    'name' => 'TestListener',
    '--module' => $this->module1->name,
  ]);

  $this->artisan(MakeListener::class, [
    'name' => 'TestListener',
    '--module' => $this->module2->name,
  ]);

  $resolved = $this->helper->listenerDirectoryFinder()
    ->map(fn (SplFileInfo $directory) => str_replace('\\', '/', $directory->getPathname()))
    ->all();

  expect($resolved)->toContain($this->module1->path('src/Listeners'));
  expect($resolved)->toContain($this->module2->path('src/Listeners'));
});

function getPackageProviders($app)
{
  $providers = getPackageProviders($app);

  return $providers;
}
