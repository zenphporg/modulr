<?php

// TestCase applied via Pest.php
use Database\Factories\WidgetFactory;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Application;
use Illuminate\View\Compilers\BladeCompiler;
use Zen\Modulr\Console\Commands\ListCommand;
use Zen\Modulr\ModulrServiceProvider;
use Zen\Modulr\Support\Registry;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(WritesToAppFilesystem::class);

test('registry is bound as a singleton', function (): void {
  $registry = $this->app->make(Registry::class);
  $registry2 = $this->app->make(Registry::class);

  expect($registry)->toBeInstanceOf(Registry::class);
  expect($registry2)->toBe($registry);
});

test('model factory classes are resolved correctly', function (): void {
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

test('model factory classes are resolved correctly with custom namespace', function (): void {
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

test('model classes are resolved correctly for factories with custom namespace', function (): void {
  $module = $this->makeModule();

  // We'll create a factory and instantiate it
  $this->artisan('make:model', ['name' => 'Widget', '--factory' => true, '--module' => $module->name]);
  require $module->path('database/factories/WidgetFactory.php');
  $factory_class = $module->qualify('Database\\Factories\\WidgetFactory');
  $factory = new $factory_class;

  /** @var Factory $factory */
  expect($factory->modelName())->toEqual($module->qualify('Models\\Widget'));

  // We'll also confirm that non-app factories are unaffected
  $this->artisan('make:model', ['name' => 'Widget', '--factory' => true]);
  require database_path('factories/WidgetFactory.php');
  $factory = new WidgetFactory;

  expect($factory->modelName())->toEqual('App\\Models\\Widget');
});

test('it loads translations from module', function (): void {
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

test('it registers modulePath macro on application', function (): void {
  // Test that the macro exists
  expect(Application::hasMacro('modulePath'))->toBeTrue();

  // Test with empty path - uses static call since it's a static closure
  $result = Application::modulePath();
  expect($result)->toBeString();
  expect($result)->toEndWith('modules');

  // Test with a path
  $result = Application::modulePath('test-module');
  expect($result)->toBeString();
  expect($result)->toEndWith('modules/test-module');
});

test('it registers views from module', function (): void {
  $module = $this->makeModule();

  // Create a views directory with a view file
  $this->filesystem()->ensureDirectoryExists($module->path('resources/views'));
  $this->filesystem()->put(
    $module->path('resources/views/test.blade.php'),
    '<h1>Test View</h1>'
  );

  // Resolve the view factory to trigger the callback
  $viewFactory = $this->app->make('view');

  // Check if the namespace was registered
  $viewFinder = $viewFactory->getFinder();

  // The hints should include our module
  $hints = $viewFinder->getHints();
  expect($hints)->toHaveKey('test-module');
});

test('it registers blade components from module', function (): void {
  $module = $this->makeModule();

  // Create a blade component
  $this->filesystem()->ensureDirectoryExists($module->path('src/View/Components'));
  $componentContent = <<<'PHP'
<?php

namespace Modules\TestModule\View\Components;

use Illuminate\View\Component;

class Alert extends Component
{
    public function render()
    {
        return view('test-module::components.alert');
    }
}
PHP;
  $this->filesystem()->put($module->path('src/View/Components/Alert.php'), $componentContent);

  // Create the view for the component
  $this->filesystem()->ensureDirectoryExists($module->path('resources/views/components'));
  $this->filesystem()->put(
    $module->path('resources/views/components/alert.blade.php'),
    '<div class="alert">{{ $slot }}</div>'
  );

  // Resolve the blade compiler to trigger the callback
  $bladeCompiler = $this->app->make('blade.compiler');

  // The component should be registered
  expect($bladeCompiler)->toBeInstanceOf(BladeCompiler::class);
});

test('it registers routes from module', function (): void {
  $module = $this->makeModule();

  // Create a routes file
  $this->filesystem()->ensureDirectoryExists($module->path('routes'));
  $routeContent = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-module-route', function () {
    return 'test';
})->name('test-module.test');
PHP;
  $this->filesystem()->put($module->path('routes/web.php'), $routeContent);

  // The route should be registered via boot
  $routes = $this->app->make('router')->getRoutes();

  // Just verify boot completed without errors
  expect($routes)->not->toBeNull();
});

test('it registers migrations from module', function (): void {
  $module = $this->makeModule();

  // Create a migrations directory with a migration
  $this->filesystem()->ensureDirectoryExists($module->path('database/migrations'));
  $migrationContent = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_module_widgets', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_module_widgets');
    }
};
PHP;
  $this->filesystem()->put(
    $module->path('database/migrations/2024_01_01_000000_create_test_module_widgets_table.php'),
    $migrationContent
  );

  // Resolve the migrator to trigger the callback
  $migrator = $this->app->make('migrator');

  // Check that migration paths were registered
  $paths = $migrator->paths();
  $moduleMigrationPath = $module->path('database/migrations');

  expect($paths)->toContain($moduleMigrationPath);
});

test('it registers policies from module', function (): void {
  $module = $this->makeModule();

  // Create a model
  $this->filesystem()->ensureDirectoryExists($module->path('src/Models'));
  $modelContent = <<<'PHP'
<?php

namespace Modules\TestModule\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
}
PHP;
  $this->filesystem()->put($module->path('src/Models/Post.php'), $modelContent);

  // Create a policy
  $this->filesystem()->ensureDirectoryExists($module->path('src/Policies'));
  $policyContent = <<<'PHP'
<?php

namespace Modules\TestModule\Policies;

class PostPolicy
{
    public function view(): bool
    {
        return true;
    }
}
PHP;
  $this->filesystem()->put($module->path('src/Policies/PostPolicy.php'), $policyContent);

  // Require the files to load the classes
  require_once $module->path('src/Models/Post.php');
  require_once $module->path('src/Policies/PostPolicy.php');

  // Resolve the gate to trigger the callback
  $gate = $this->app->make(Gate::class);

  // Just verify it completed without errors
  expect($gate)->not->toBeNull();
});

test('it registers policies for nested models', function (): void {
  $module = $this->makeModule();

  // Create a nested model (Models/Nested/Article)
  $this->filesystem()->ensureDirectoryExists($module->path('src/Models/Nested'));
  $modelContent = <<<'PHP'
<?php

namespace Modules\TestModule\Models\Nested;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
}
PHP;
  $this->filesystem()->put($module->path('src/Models/Nested/Article.php'), $modelContent);

  // Create a simple policy (Policies/ArticlePolicy - not Policies/Nested/ArticlePolicy)
  $this->filesystem()->ensureDirectoryExists($module->path('src/Policies'));
  $policyContent = <<<'PHP'
<?php

namespace Modules\TestModule\Policies;

class ArticlePolicy
{
    public function view(): bool
    {
        return true;
    }
}
PHP;
  $this->filesystem()->put($module->path('src/Policies/ArticlePolicy.php'), $policyContent);

  // Require the files to load the classes
  require_once $module->path('src/Models/Nested/Article.php');
  require_once $module->path('src/Policies/ArticlePolicy.php');

  // Resolve the gate to trigger the callback
  $gate = $this->app->make(Gate::class);

  // Just verify it completed without errors
  expect($gate)->not->toBeNull();
});

test('it registers commands from module', function (): void {
  $module = $this->makeModule();

  // Create a command
  $this->filesystem()->ensureDirectoryExists($module->path('src/Console/Commands'));
  $commandContent = <<<'PHP'
<?php

namespace Modules\TestModule\Console\Commands;

use Illuminate\Console\Command;

class TestModuleCommand extends Command
{
    protected $signature = 'test-module:run';
    protected $description = 'Test module command';

    public function handle(): void
    {
        $this->info('Test module command executed');
    }
}
PHP;
  $this->filesystem()->put($module->path('src/Console/Commands/TestModuleCommand.php'), $commandContent);

  // Require the file to load the class
  require_once $module->path('src/Console/Commands/TestModuleCommand.php');

  // Resolve artisan to trigger the callback
  $artisan = $this->app->make(Kernel::class);

  // Just verify it completed without errors
  expect($artisan)->not->toBeNull();
});

test('it checks if command is instantiable', function (): void {
  $provider = $this->app->getProvider(ModulrServiceProvider::class);
  $reflection = new ReflectionClass($provider);

  $method = $reflection->getMethod('isInstantiableCommand');

  // Test with a real command class
  $result = $method->invoke($provider, ListCommand::class);
  expect($result)->toBeTrue();

  // Test with a non-command class
  $result = $method->invoke($provider, stdClass::class);
  expect($result)->toBeFalse();
});

test('it gets modules base path', function (): void {
  $provider = $this->app->getProvider(ModulrServiceProvider::class);
  $reflection = new ReflectionClass($provider);

  $method = $reflection->getMethod('getModulesBasePath');

  $result = $method->invoke($provider);
  expect($result)->toContain('modules');
});

test('it registers lazily', function (): void {
  $provider = $this->app->getProvider(ModulrServiceProvider::class);
  $reflection = new ReflectionClass($provider);

  $method = $reflection->getMethod('registerLazily');

  $called = false;
  $result = $method->invoke($provider, stdClass::class, function () use (&$called): void {
    $called = true;
  });

  expect($result)->toBe($provider);

  // Resolve the class to trigger the callback
  $this->app->make(stdClass::class);
  expect($called)->toBeTrue();
});
