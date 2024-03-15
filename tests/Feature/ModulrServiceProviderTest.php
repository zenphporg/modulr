<?php

use Illuminate\Database\Eloquent\Factories\Factory;
use Zen\Modulr\Support\Registry;

uses(Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);

test('registry is bound as a singleton', function () {
  $registry = $this->app->make(Registry::class);
  $registry2 = $this->app->make(Registry::class);

  expect($registry)->toBeInstanceOf(Registry::class);
  expect($registry2)->toBe($registry);
});

test('model factory classes are resolved correctly', function () {
  $module = $this->makeModule();

  expect(Factory::resolveFactoryName($module->qualify('Models\\Foo')))->toEqual($module->qualify('Database\\Factories\\FooFactory'));

  expect(Factory::resolveFactoryName($module->qualify('Foo')))->toEqual($module->qualify('Database\\Factories\\FooFactory'));

  expect(Factory::resolveFactoryName($module->qualify('Models\\Foo\\Bar')))->toEqual($module->qualify('Database\\Factories\\Foo\\BarFactory'));

  expect(Factory::resolveFactoryName($module->qualify('Foo\\Bar')))->toEqual($module->qualify('Database\\Factories\\Foo\\BarFactory'));

  expect(Factory::resolveFactoryName('App\\Models\\Foo'))->toEqual('Database\\Factories\\FooFactory');

  expect(Factory::resolveFactoryName('App\\Foo'))->toEqual('Database\\Factories\\FooFactory');

  expect(Factory::resolveFactoryName('App\\Models\\Foo\\Bar'))->toEqual('Database\\Factories\\Foo\\BarFactory');

  expect(Factory::resolveFactoryName('App\\Foo\\Bar'))->toEqual('Database\\Factories\\Foo\\BarFactory');
});

test('model factory classes are resolved correctly with custom namespace', function () {
  Factory::useNamespace('Something\\');

  $module = $this->makeModule();

  expect(Factory::resolveFactoryName($module->qualify('Models\\Foo')))->toEqual($module->qualify('Something\\FooFactory'));

  expect(Factory::resolveFactoryName($module->qualify('Foo')))->toEqual($module->qualify('Something\\FooFactory'));

  expect(Factory::resolveFactoryName($module->qualify('Models\\Foo\\Bar')))->toEqual($module->qualify('Something\\Foo\\BarFactory'));

  expect(Factory::resolveFactoryName($module->qualify('Foo\\Bar')))->toEqual($module->qualify('Something\\Foo\\BarFactory'));

  expect(Factory::resolveFactoryName('App\\Models\\Foo'))->toEqual('Something\\FooFactory');

  expect(Factory::resolveFactoryName('App\\Foo'))->toEqual('Something\\FooFactory');

  expect(Factory::resolveFactoryName('App\\Models\\Foo\\Bar'))->toEqual('Something\\Foo\\BarFactory');

  expect(Factory::resolveFactoryName('App\\Foo\\Bar'))->toEqual('Something\\Foo\\BarFactory');

  Factory::useNamespace('Database\\Factories\\');
});

test('model classes are resolved correctly for factories with custom namespace', function () {
  $module = $this->makeModule();

  // We'll create a factory and instantiate it
  $this->artisan('make:model', ['name' => 'Widget', '--factory' => true, '--module' => $module->name]);
  require $module->path('database/factories/WidgetFactory.php');
  $factory_class = $module->qualify('Database\\Factories\\WidgetFactory');
  $factory = new $factory_class();

  /** @var Factory $factory */
  expect($factory->modelName())->toEqual($module->qualify('Models\\Widget'));

  // We'll also confirm that non-app factories are unaffected
  $this->artisan('make:model', ['name' => 'Widget', '--factory' => true]);
  require database_path('factories/WidgetFactory.php');
  $factory = new Database\Factories\WidgetFactory();

  expect($factory->modelName())->toEqual('App\\Widget');
});

test('it loads translations from module', function () {
  $module = $this->makeModule();

  $this->filesystem()->ensureDirectoryExists($module->path('resources/lang'));
  $this->filesystem()->ensureDirectoryExists($module->path('resources/lang/en'));

  $this->filesystem()->put($module->path('resources/lang/en.json'), json_encode([
    'Test JSON string' => 'Test JSON translation',
  ], JSON_THROW_ON_ERROR));

  $this->filesystem()->put(
    $module->path('resources/lang/en/foo.php'),
    '<?php return ["bar" => "Test PHP translation"];'
  );

  $this->app->setLocale('en');

  $translator = $this->app->make('translator');

  expect($translator->get('Test JSON string'))->toEqual('Test JSON translation');
  expect($translator->get('test-module::foo.bar'))->toEqual('Test PHP translation');
});
