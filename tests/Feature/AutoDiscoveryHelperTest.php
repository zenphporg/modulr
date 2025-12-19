<?php

// TestCase applied via Pest.php
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;
use Zen\Modulr\Console\Commands\Make\MakeCommand;
use Zen\Modulr\Console\Commands\Make\MakeComponent;
use Zen\Modulr\Console\Commands\Make\MakeFactory;
use Zen\Modulr\Console\Commands\Make\MakeListener;
use Zen\Modulr\Console\Commands\Make\MakeModel;
use Zen\Modulr\Support\AutoDiscoveryHelper;
use Zen\Modulr\Support\Registry;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(WritesToAppFilesystem::class);

beforeEach(function (): void {
  $this->module1 = $this->makeModule('test-module');
  $this->module2 = $this->makeModule('test-module-two');
  // Use the same Registry instance from the container that makeModule() uses
  $this->helper = new AutoDiscoveryHelper(
    $this->app->make(Registry::class),
    new Filesystem
  );
});

test('it finds commands', function (): void {
  $this->artisan(MakeCommand::class, [
    'name' => 'TestCommand',
    '--module' => $this->module1->name,
  ]);

  $this->artisan(MakeCommand::class, [
    'name' => 'TestCommand',
    '--module' => $this->module2->name,
  ]);

  $resolved = [];

  $this->helper->commandFileFinder()->each(function (SplFileInfo $command) use (&$resolved): void {
    $resolved[] = str_replace('\\', '/', $command->getPathname());
  });

  expect($resolved)->toContain($this->module1->path('src/Console/Commands/TestCommand.php'));
  expect($resolved)->toContain($this->module2->path('src/Console/Commands/TestCommand.php'));
});

test('it finds factory directories', function (): void {
  // Create factories to ensure the factory directories exist
  $this->artisan(MakeFactory::class, [
    'name' => 'TestFactory',
    '--module' => $this->module1->name,
  ]);

  $this->artisan(MakeFactory::class, [
    'name' => 'TestFactory',
    '--module' => $this->module2->name,
  ]);

  $resolved = [];

  $this->helper->factoryDirectoryFinder()->each(function (SplFileInfo $directory) use (&$resolved): void {
    $resolved[] = str_replace('\\', '/', $directory->getPathname());
  });

  expect($resolved)->toContain($this->module1->path('database/factories'));
  expect($resolved)->toContain($this->module2->path('database/factories'));
});

test('it finds migration directories', function (): void {
  // Create migration directories since they're not created by default
  $fs = new Filesystem;
  $fs->ensureDirectoryExists($this->module1->path('database/migrations'));
  $fs->ensureDirectoryExists($this->module2->path('database/migrations'));

  $resolved = [];

  $this->helper->migrationDirectoryFinder()->each(function (SplFileInfo $directory) use (&$resolved): void {
    $resolved[] = str_replace('\\', '/', $directory->getPathname());
  });

  expect($resolved)->toContain($this->module1->path('database/migrations'));
  expect($resolved)->toContain($this->module2->path('database/migrations'));
});

test('it finds models', function (): void {
  $this->artisan(MakeModel::class, [
    'name' => 'TestModel',
    '--module' => $this->module1->name,
  ]);

  $this->artisan(MakeModel::class, [
    'name' => 'TestModel',
    '--module' => $this->module2->name,
  ]);

  $resolved = [];

  $this->helper->modelFileFinder()->each(function (SplFileInfo $file) use (&$resolved): void {
    $resolved[] = str_replace('\\', '/', $file->getPathname());
  });

  expect($resolved)->toContain($this->module1->path('src/Models/TestModel.php'));
  expect($resolved)->toContain($this->module2->path('src/Models/TestModel.php'));
});

test('it finds blade components', function (): void {
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

  $this->helper->bladeComponentDirectoryFinder()->each(function (SplFileInfo $file) use (&$resolved_directories): void {
    $resolved_directories[] = str_replace('\\', '/', $file->getPathname());
  });

  $this->helper->bladeComponentFileFinder()->each(function (SplFileInfo $file) use (&$resolved_files): void {
    $resolved_files[] = str_replace('\\', '/', $file->getPathname());
  });

  expect($resolved_directories)->toContain($this->module1->path('src/View/Components'));
  expect($resolved_directories)->toContain($this->module2->path('src/View/Components'));

  expect($resolved_files)->toContain($this->module1->path('src/View/Components/TestComponent.php'));
  expect($resolved_files)->toContain($this->module2->path('src/View/Components/TestComponent.php'));
});

test('it finds routes', function (): void {
  // Create route files since they're not created by default
  $fs = new Filesystem;
  $fs->ensureDirectoryExists($this->module1->path('routes'));
  $fs->ensureDirectoryExists($this->module2->path('routes'));
  $fs->put($this->module1->path("routes/{$this->module1->name}-routes.php"), '<?php');
  $fs->put($this->module2->path("routes/{$this->module2->name}-routes.php"), '<?php');

  $resolved = [];

  $this->helper->routeFileFinder()->each(function (SplFileInfo $file) use (&$resolved): void {
    $resolved[] = str_replace('\\', '/', $file->getPathname());
  });

  expect($resolved)->toContain($this->module1->path("routes/{$this->module1->name}-routes.php"));
  expect($resolved)->toContain($this->module2->path("routes/{$this->module2->name}-routes.php"));
});

test('it finds view directories', function (): void {
  // Create view directories since they're not created by default
  $fs = new Filesystem;
  $fs->ensureDirectoryExists($this->module1->path('resources/views'));
  $fs->ensureDirectoryExists($this->module2->path('resources/views'));

  $resolved = [];

  $this->helper->viewDirectoryFinder()->each(function (SplFileInfo $directory) use (&$resolved): void {
    $resolved[] = str_replace('\\', '/', $directory->getPathname());
  });

  expect($resolved)->toContain($this->module1->path('resources/views'));
  expect($resolved)->toContain($this->module2->path('resources/views'));
});

test('it finds lang directories', function (): void {
  // Create lang directories since they're not created by default
  $fs = new Filesystem;
  $fs->ensureDirectoryExists($this->module1->path('resources/lang'));
  $fs->ensureDirectoryExists($this->module2->path('resources/lang'));

  $resolved = [];

  $this->helper->langDirectoryFinder()->each(function (SplFileInfo $directory) use (&$resolved): void {
    $resolved[] = str_replace('\\', '/', $directory->getPathname());
  });

  expect($resolved)->toContain($this->module1->path('resources/lang'));
  expect($resolved)->toContain($this->module2->path('resources/lang'));
});

test('it finds event listeners', function (): void {
  $this->artisan(MakeListener::class, [
    'name' => 'TestListener',
    '--module' => $this->module1->name,
  ]);

  $this->artisan(MakeListener::class, [
    'name' => 'TestListener',
    '--module' => $this->module2->name,
  ]);

  $resolved = $this->helper->listenerDirectoryFinder()
    ->map(fn (SplFileInfo $directory): string => str_replace('\\', '/', $directory->getPathname()))
    ->all();

  expect($resolved)->toContain($this->module1->path('src/Listeners'));
  expect($resolved)->toContain($this->module2->path('src/Listeners'));
});
