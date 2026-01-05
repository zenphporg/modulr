<?php

declare(strict_types=1);

namespace Zen\Modulr\Console\Commands\Make;

use Composer\Factory;
use Composer\Json\JsonFile;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Seld\JsonLint\ParsingException;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Terminal;
use Zen\Modulr\Console\Commands\ClearCommand;
use Zen\Modulr\Events\ControllerPromptsCollecting;
use Zen\Modulr\Support\Registry;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class MakeModule extends Command
{
  /**
   * @var string
   */
  protected $signature = 'modules:make
    {name? : The name of the module}
    {--accept-namespace : Skip default namespace confirmation}
    {--empty : Create an empty module directory and namespace}';

  /**
   * @var string
   */
  protected $description = 'Create a new Laravel module';

  /**
   * This is the base path of the module
   */
  protected string $base_path;

  /**
   * This is the PHP namespace for all modules
   */
  protected string $module_namespace;

  /**
   * This is the composer namespace for all modules
   */
  protected string $composer_namespace;

  /**
   * This is the name of the module
   */
  protected string $module_name;

  /**
   * This is the module name as a StudlyCased name
   */
  protected string $class_name_prefix;

  /**
   * This is the name of the module as a composer package
   * i.e. modules/my-module
   */
  protected string $composer_name;

  /**
   * Components selected by the user to generate
   *
   * @var array<string>
   */
  protected array $selected_components = [];

  /**
   * Selected controller type (resource, api, invokable, singleton, plain)
   */
  protected string $controller_type = 'resource';

  /**
   * Available controller types
   *
   * @var array<string, string>
   */
  protected array $controller_types = [
    'resource' => 'Resource (index, create, store, show, edit, update, destroy)',
    'api' => 'API Resource (index, store, show, update, destroy)',
    'invokable' => 'Invokable (single __invoke method)',
    'singleton' => 'Singleton Resource (show, edit, update)',
    'plain' => 'Plain (empty controller)',
  ];

  /**
   * Selected model options
   *
   * @var array<string, bool>
   */
  protected array $model_options = [];

  /**
   * Selected mail option (with/without markdown)
   */
  protected bool $mail_markdown = false;

  /**
   * Selected notification option (with/without markdown)
   */
  protected bool $notification_markdown = false;

  /**
   * Selected component option (with/without inline view)
   */
  protected bool $component_inline = false;

  /**
   * Selected event option (should also create listener)
   */
  protected bool $event_with_listener = false;

  /**
   * Additional controller options collected from event listeners.
   *
   * @var array<string, mixed>
   */
  protected array $additional_controller_options = [];

  /**
   * Available components that can be generated
   *
   * @var array<string, string>
   */
  protected array $available_components = [
    'model' => 'Model',
    'controller' => 'Controller',
    'migration' => 'Migration',
    'factory' => 'Factory',
    'seeder' => 'Seeder',
    'request' => 'Form Request',
    'resource' => 'API Resource',
    'policy' => 'Policy',
    'event' => 'Event',
    'listener' => 'Listener',
    'job' => 'Job',
    'mail' => 'Mailable',
    'notification' => 'Notification',
    'component' => 'Blade Component',
    'observer' => 'Observer',
    'rule' => 'Validation Rule',
    'cast' => 'Cast',
    'middleware' => 'Middleware',
    'exception' => 'Exception',
    'command' => 'Console Command',
    'channel' => 'Broadcast Channel',
    'test' => 'Test',
    'routes' => 'Routes File',
    'views' => 'Blade Views',
  ];

  public function __construct(protected Filesystem $filesystem, protected Registry $module_registry)
  {
    parent::__construct();
  }

  /**
   * @throws ParsingException
   */
  public function handle(): int
  {
    $this->setUpStyles();

    $argumentName = $this->argument('name');

    if (! is_string($argumentName) || $argumentName === '') {
      // @codeCoverageIgnoreStart
      $argumentName = text(
        label: 'What is the name of your module?',
        placeholder: 'E.g. billing, user-management, inventory',
        required: 'A module name is required.',
        validate: fn (string $value): ?string => preg_match('/^[a-zA-Z][a-zA-Z0-9-]*$/', $value)
          ? null
          : 'Module name must start with a letter and contain only letters, numbers, and hyphens.',
      );
      // @codeCoverageIgnoreEnd
    }

    $this->module_name = Str::kebab($argumentName);
    $this->class_name_prefix = Str::studly($argumentName);

    /** @var string $moduleNamespace */
    $moduleNamespace = config('modulr.modules_namespace', 'Modules');
    $this->module_namespace = $moduleNamespace;

    /** @var string|null $modulesVendor */
    $modulesVendor = config('modulr.modules_vendor');
    $this->composer_namespace = $modulesVendor ?? Str::kebab($this->module_namespace);
    $this->composer_name = "$this->composer_namespace/$this->module_name";
    $this->base_path = $this->module_registry->getModulesPath().'/'.$this->module_name;

    $this->newLine();

    $this->ensureModulesDirectoryExists();

    if ($this->shouldAbortToPublishConfig()) {
      return 0; // @codeCoverageIgnore
    }

    // Skip prompts if --empty flag is used
    if (! $this->option('empty')) {
      $this->promptForComponents();
      $this->promptForComponentOptions();
    }

    $this->writeStubs();

    // Reload registry so the new module is available for component generation
    $this->module_registry->reload();

    $this->generateSelectedComponents();

    $this->updateCoreComposerConfig();

    $this->runComposerUpdate();

    $this->call(ClearCommand::class);

    return 0;
  }

  protected function promptForComponents(): void
  {
    // Skip prompts during unit tests when running via artisan
    // (prompts can still be tested via reflection with Prompt::fake())
    if ($this->laravel->runningUnitTests()) {
      return;
    }

    /** @var array<string> $selected */
    $selected = multiselect(
      label: 'Which components would you like to generate?',
      options: $this->available_components,
      default: [],
      hint: 'Use space to select, enter to confirm. ServiceProvider is always created.',
    );

    $this->selected_components = $selected;
  }

  protected function promptForComponentOptions(): void
  {
    $this->promptForModelOptions();
    $this->promptForControllerType();
    $this->promptForMailOptions();
    $this->promptForNotificationOptions();
    $this->promptForComponentOptions2();
    $this->promptForEventOptions();
  }

  protected function promptForModelOptions(): void
  {
    if (! in_array('model', $this->selected_components, true)) {
      return;
    }

    $this->newLine();
    $this->components->info('Model Options');

    /** @var array<string> $selected */
    $selected = multiselect(
      label: 'What would you like to include with your model?',
      options: [
        'factory' => 'Factory',
        'migration' => 'Migration',
        'seeder' => 'Seeder',
        'controller' => 'Controller',
        'resource' => 'API Resource',
        'policy' => 'Policy',
        'all' => 'All of the above',
      ],
      default: [],
      hint: 'These will be generated in addition to any already selected',
    );

    if (in_array('all', $selected, true)) {
      $this->model_options = [
        '--factory' => true,
        '--migration' => true,
        '--seed' => true,
        '--controller' => true,
        '--resource' => true,
        '--policy' => true,
      ];
    } else {
      $this->model_options = [
        '--factory' => in_array('factory', $selected, true),
        '--migration' => in_array('migration', $selected, true),
        '--seed' => in_array('seeder', $selected, true),
        '--controller' => in_array('controller', $selected, true),
        '--resource' => in_array('resource', $selected, true),
        '--policy' => in_array('policy', $selected, true),
      ];
    }

    // Filter out false values
    $this->model_options = array_filter($this->model_options);
  }

  protected function promptForControllerType(): void
  {
    $controllerSelectedAsComponent = in_array('controller', $this->selected_components, true);
    $controllerSelectedViaModel = isset($this->model_options['--controller']) && $this->model_options['--controller'];

    // Show controller type prompt if controller is selected either as component or via model
    if (! $controllerSelectedAsComponent && ! $controllerSelectedViaModel) {
      return;
    }

    $this->newLine();
    $this->components->info('Controller Options');

    /** @var string $selected */
    $selected = select(
      label: 'What type of controller would you like?',
      options: $this->controller_types,
      default: 'resource',
    );

    $this->controller_type = $selected;

    // Dispatch event to allow extensions to add their own prompts/options
    $event = new ControllerPromptsCollecting(
      controllerType: $this->controller_type,
      moduleName: $this->module_name,
      className: $this->class_name_prefix,
    );
    event($event);

    $this->additional_controller_options = $event->getAdditionalOptions();
  }

  protected function promptForMailOptions(): void
  {
    if (! in_array('mail', $this->selected_components, true)) {
      return;
    }

    $this->newLine();
    $this->components->info('Mailable Options');

    $this->mail_markdown = confirm(
      label: 'Would you like to use a Markdown template?',
      default: false,
    );
  }

  protected function promptForNotificationOptions(): void
  {
    if (! in_array('notification', $this->selected_components, true)) {
      return;
    }

    $this->newLine();
    $this->components->info('Notification Options');

    $this->notification_markdown = confirm(
      label: 'Would you like to use a Markdown template?',
      default: false,
    );
  }

  protected function promptForComponentOptions2(): void
  {
    if (! in_array('component', $this->selected_components, true)) {
      return;
    }

    $this->newLine();
    $this->components->info('Blade Component Options');

    $this->component_inline = confirm(
      label: 'Would you like an inline view (no separate Blade file)?',
      default: false,
    );
  }

  protected function promptForEventOptions(): void
  {
    if (! in_array('event', $this->selected_components, true)) {
      return;
    }

    $this->newLine();
    $this->components->info('Event Options');

    $this->event_with_listener = confirm(
      label: 'Would you also like to create a listener for this event?',
      default: false,
    );
  }

  protected function generateSelectedComponents(): void
  {
    if ($this->selected_components === []) {
      return;
    }

    $this->title('Generating selected components');

    foreach ($this->selected_components as $component) {
      $this->generateComponent($component);
    }

    $this->newLine();
  }

  protected function generateComponent(string $component): void
  {
    $componentName = $this->class_name_prefix;

    $commandMap = [
      'model' => ['make:model', ['name' => $componentName, ...$this->getModelOptions()]],
      'controller' => ['make:controller', ['name' => "{$componentName}Controller", ...$this->getControllerOptions()]],
      'factory' => ['make:factory', ['name' => "{$componentName}Factory"]],
      'seeder' => ['make:seeder', ['name' => "{$componentName}Seeder"]],
      'request' => ['make:request', ['name' => "Store{$componentName}Request"]],
      'resource' => ['make:resource', ['name' => "{$componentName}Resource"]],
      'policy' => ['make:policy', ['name' => "{$componentName}Policy"]],
      'event' => ['make:event', ['name' => "{$componentName}Created"]],
      'listener' => ['make:listener', ['name' => "{$componentName}CreatedListener"]],
      'job' => ['make:job', ['name' => "Process{$componentName}"]],
      'mail' => ['make:mail', ['name' => "{$componentName}Mail", ...$this->getMailOptions()]],
      'notification' => ['make:notification', ['name' => "{$componentName}Notification", ...$this->getNotificationOptions()]],
      'observer' => ['make:observer', ['name' => "{$componentName}Observer"]],
      'rule' => ['make:rule', ['name' => "{$componentName}Rule"]],
      'cast' => ['make:cast', ['name' => "{$componentName}Cast"]],
      'middleware' => ['make:middleware', ['name' => "{$componentName}Middleware"]],
      'exception' => ['make:exception', ['name' => "{$componentName}Exception"]],
      'command' => ['make:command', ['name' => "{$componentName}Command"]],
      'channel' => ['make:channel', ['name' => "{$componentName}Channel"]],
      'component' => ['make:component', ['name' => $componentName, ...$this->getComponentOptions()]],
    ];

    // Skip components handled by stubs (routes, views, test)
    // ServiceProvider and Test are created via stub, not artisan commands
    if (in_array($component, ['routes', 'views', 'test'], true)) {
      return;
    }

    // Handle migration separately - use make:migration command
    // @codeCoverageIgnoreStart
    if ($component === 'migration') {
      $tableName = Str::snake(Str::plural($this->class_name_prefix));
      $this->callSilently('make:migration', [
        'name' => "create_{$tableName}_table",
        '--create' => $tableName,
        '--module' => $this->module_name,
      ]);
      $this->line(' - Generated <info>Migration</info>');

      return;
    }
    // @codeCoverageIgnoreEnd

    if (! isset($commandMap[$component])) {
      return;
    }

    // @codeCoverageIgnoreStart
    [$command, $arguments] = $commandMap[$component];

    $this->callSilently($command, [
      ...$arguments,
      '--module' => $this->module_name,
    ]);

    $this->line(" - Generated <info>{$this->available_components[$component]}</info>");

    // If model was selected with controller option, create the controller with the selected type
    // We do this separately because make:model --controller doesn't support controller type flags
    if ($component === 'model' && $this->controllerSelectedViaModel() && ! $this->isComponentSelected('controller')) {
      $this->callSilently('make:controller', [
        'name' => "{$componentName}Controller",
        ...$this->getControllerOptions(),
        '--module' => $this->module_name,
      ]);
      $this->line(' - Generated <info>Controller</info> (for model)');
    }

    // If event was selected with listener option, create the listener too
    if ($component === 'event' && $this->event_with_listener) {
      $this->callSilently('make:listener', [
        'name' => "{$componentName}CreatedListener",
        '--event' => "Modules\\{$this->class_name_prefix}\\Events\\{$componentName}Created",
        '--module' => $this->module_name,
      ]);
      $this->line(' - Generated <info>Listener</info> (for event)');
    }
    // @codeCoverageIgnoreEnd
  }

  /**
   * Get the model options based on user selections.
   *
   * @return array<string, bool>
   */
  protected function getModelOptions(): array
  {
    $options = $this->model_options;

    // If migration was selected as a component, don't create duplicate via model
    if ($this->isComponentSelected('migration')) {
      unset($options['--migration']);
    }

    // Remove --controller from model options - we'll create it separately with the correct type
    // Laravel's make:model --controller doesn't support controller type flags
    unset($options['--controller']);

    return $options;
  }

  /**
   * Check if controller was selected via model options.
   */
  protected function controllerSelectedViaModel(): bool
  {
    return isset($this->model_options['--controller']) && $this->model_options['--controller'];
  }

  /**
   * Get the controller options based on the selected type.
   *
   * @return array<string, mixed>
   */
  protected function getControllerOptions(): array
  {
    $options = match ($this->controller_type) {
      'resource' => ['--resource' => true],
      'api' => ['--api' => true],
      'invokable' => ['--invokable' => true],
      'singleton' => ['--singleton' => true],
      default => [],
    };

    return array_merge($options, $this->additional_controller_options);
  }

  /**
   * Get the mail options based on user selections.
   *
   * @return array<string, mixed>
   */
  protected function getMailOptions(): array
  {
    if ($this->mail_markdown) {
      // Generate the default view name: mail.{kebab-name}-mail
      $viewName = 'mail.'.Str::kebab($this->class_name_prefix).'-mail';

      return ['--markdown' => $viewName];
    }

    return [];
  }

  /**
   * Get the notification options based on user selections.
   *
   * @return array<string, mixed>
   */
  protected function getNotificationOptions(): array
  {
    if ($this->notification_markdown) {
      // Generate the default view name: mail.{kebab-name}-notification
      $viewName = 'mail.'.Str::kebab($this->class_name_prefix).'-notification';

      return ['--markdown' => $viewName];
    }

    return [];
  }

  /**
   * Get the component options based on user selections.
   *
   * @return array<string, bool>
   */
  protected function getComponentOptions(): array
  {
    if ($this->component_inline) {
      return ['--inline' => true];
    }

    return [];
  }

  protected function shouldAbortToPublishConfig(): bool
  {
    if (
      $this->module_namespace !== 'Modules'
      || $this->option('accept-namespace') === true
      || $this->module_registry->modules()->isNotEmpty()
    ) {
      return false;
    }

    // @codeCoverageIgnoreStart
    $this->title('Welcome');

    $message = "You're about to create your first module in the <info>$this->module_namespace</info> "
        .'namespace. This is the default namespace, and will work for many use-cases. However, '
        .'if you ever choose to extract a module into its own package, you will '
        ."likely want to use a custom namespace (like your organization name).\n\n"
        .'If you would like to use a custom namespace, please publish the config '
        ."and customize it first. You can do this by calling:\n\n"
        .'<kbd>php artisan vendor:publish --tag=modular-config</kbd>';

    $width = min((new Terminal)->getWidth(), 100) - 1;
    $messages = explode(PHP_EOL, wordwrap($message, $width, PHP_EOL));
    foreach ($messages as $message) {
      $this->line(" $message");
    }

    return $this->confirm('Would you like to cancel and configure your module namespace first?', true);
    // @codeCoverageIgnoreEnd
  }

  protected function ensureModulesDirectoryExists(): void
  {
    if (! $this->filesystem->isDirectory($this->base_path)) {
      $this->filesystem->makeDirectory($this->base_path, 0777, true);
      $this->line(" - Created <info>$this->base_path</info>");
    }
  }

  protected function writeStubs(): void
  {
    $this->title('Creating initial module files');

    /** @var string $tests_base */
    $tests_base = config('modulr.tests_base', 'Tests\TestCase');

    $placeholders = [
      'StubBasePath' => $this->base_path,
      'StubModuleNamespace' => $this->module_namespace,
      'StubComposerNamespace' => $this->composer_namespace,
      'StubModuleNameSingular' => Str::singular($this->module_name),
      'StubModuleNamePlural' => Str::plural($this->module_name),
      'StubModuleName' => $this->module_name,
      'StubClassNamePrefix' => $this->class_name_prefix,
      'StubComposerName' => $this->composer_name,
      'StubFullyQualifiedTestCaseBase' => $tests_base,
      'StubTestCaseBase' => class_basename($tests_base),
    ];

    /** @var array<string> $search */
    $search = array_keys($placeholders);
    /** @var array<string> $replace */
    $replace = array_values($placeholders);

    foreach ($this->getStubs() as $destination => $stub_file) {
      $contents = file_get_contents($stub_file);
      if ($contents === false) { // @codeCoverageIgnore
        continue; // @codeCoverageIgnore
      }
      $destination = str_replace($search, $replace, $destination);
      $filename = "$this->base_path/$destination";

      $output = str_replace($search, $replace, $contents);

      if ($this->filesystem->exists($filename)) {
        $this->line(" - Skipping <info>$destination</info> (already exists)");

        continue;
      }

      $this->filesystem->ensureDirectoryExists($this->filesystem->dirname($filename));
      $this->filesystem->put($filename, $output);

      $this->line(" - Wrote to <info>$destination</info>");
    }

    $this->newLine();
  }

  protected function seedersDirectory(): string
  {
    return version_compare($this->getLaravel()->version(), '8.0.0', '>=')
        ? 'seeders'
        : 'seeds';
  }

  /**
   * @throws ParsingException
   * @throws Exception
   */
  protected function updateCoreComposerConfig(): void
  {
    $this->title('Updating application composer.json file');

    // We're going to move into the Laravel base directory while
    // we're updating the composer file so that we're sure we update
    // the correct composer.json file (we'll restore CWD at the end)
    $original_working_dir = getcwd();
    if ($original_working_dir === false) { // @codeCoverageIgnore
      $original_working_dir = $this->laravel->basePath(); // @codeCoverageIgnore
    }
    chdir($this->laravel->basePath());

    $jsonFile = new JsonFile(Factory::getComposerFile());
    /** @var array<string, mixed> $definition */
    $definition = $jsonFile->read();

    if (! isset($definition['repositories'])) {
      $definition['repositories'] = [];
    }

    if (! isset($definition['require'])) {
      $definition['require'] = []; // @codeCoverageIgnore
    }

    /** @var string $modulesDirectory */
    $modulesDirectory = config('modulr.modules_directory', 'modules');
    $module_config = [
      'type' => 'path',
      'url' => str_replace('\\', '/', $modulesDirectory).'/*',
      'options' => [
        'symlink' => true,
      ],
    ];

    $has_changes = false;

    /** @var array<int|string, array{type: string, url: string, options?: array<string, mixed>}> $repositories */
    $repositories = $definition['repositories'];
    $repository_already_exists = collect($repositories)
      ->contains(fn (array $repository): bool => $repository['url'] === $module_config['url']);

    if ($repository_already_exists === false) {
      $this->line(" - Adding path repository for <info>{$module_config['url']}</info>");
      $has_changes = true;

      if (Arr::isAssoc($repositories)) {
        $repositories[$this->module_name] = $module_config;
      } else {
        $repositories[] = $module_config;
      }
      $definition['repositories'] = $repositories;
    }

    /** @var array<string, string> $require */
    $require = $definition['require'];
    if (! isset($require[$this->composer_name])) {
      $this->line(" - Adding require statement for <info>$this->composer_name:*</info>");
      $has_changes = true;

      $require["$this->composer_namespace/$this->module_name"] = '*';
      $definition['require'] = $this->sortComposerPackages($require);
    }

    if ($has_changes) {
      $jsonFile->write($definition);
      $this->line(" - Wrote to <info>{$jsonFile->getPath()}</info>");
    } else {
      $this->line(' - Nothing to update (repository & require entry already exist)');
    }

    chdir($original_working_dir);

    $this->newLine();
  }

  /**
   * @param  array<string, string>  $packages
   * @return array<string, string>
   */
  protected function sortComposerPackages(array $packages): array
  {
    $prefix = (fn (string $requirement): ?string => preg_replace(
      [
        '/^php$/',
        '/^hhvm-/',
        '/^ext-/',
        '/^lib-/',
        '/^\D/',
        '/^(?!php$|hhvm-|ext-|lib-)/',
      ],
      [
        '0-$0',
        '1-$0',
        '2-$0',
        '3-$0',
        '4-$0',
        '5-$0',
      ],
      $requirement
    ));

    uksort($packages, fn (string $a, string $b): int => strnatcmp((string) $prefix($a), (string) $prefix($b)));

    return $packages;
  }

  protected function setUpStyles(): void
  {
    $outputFormatter = $this->getOutput()->getFormatter();

    if (! $outputFormatter->hasStyle('kbd')) {
      $outputFormatter->setStyle('kbd', new OutputFormatterStyle('cyan'));
    }
  }

  protected function title(string $title): void
  {
    $this->getOutput()->title($title);
  }

  /**
   * @param  int  $count
   */
  public function newLine($count = 1): static // @pest-ignore-type
  {
    $this->getOutput()->newLine($count);

    return $this;
  }

  /**
   * @return array<string, string>
   */
  protected function getStubs(): array
  {
    $custom_stubs = config('modulr.stubs');
    if (is_array($custom_stubs)) {
      /** @var array<string, string> $custom_stubs */
      return $custom_stubs;
    }

    $composer_stub = 'composer-stub-latest.json';

    // Base stubs always included (composer.json and ServiceProvider)
    $stubs = [
      'composer.json' => $this->pathToStub($composer_stub),
      'src/Providers/StubClassNamePrefixServiceProvider.php' => $this->pathToStub('ServiceProvider.php'),
    ];

    // Test for service provider (if test is selected)
    if ($this->isComponentSelected('test')) {
      $stubs['tests/StubClassNamePrefixServiceProviderTest.php'] = $this->pathToStub('ServiceProviderTest.php');
    }

    // Routes (if selected)
    if ($this->isComponentSelected('routes')) {
      $stubs['routes/StubModuleName-routes.php'] = $this->pathToStub('web-routes.php');
    }

    // Views (if selected)
    if ($this->isComponentSelected('views')) {
      $stubs['resources/views/index.blade.php'] = $this->pathToStub('view.blade.php');
      $stubs['resources/views/create.blade.php'] = $this->pathToStub('view.blade.php');
      $stubs['resources/views/show.blade.php'] = $this->pathToStub('view.blade.php');
      $stubs['resources/views/edit.blade.php'] = $this->pathToStub('view.blade.php');
    }

    // Factory directory (if factory is selected)
    if ($this->isComponentSelected('factory')) {
      $stubs['database/factories/.gitkeep'] = $this->pathToStub('.gitkeep');
    }

    // Seeder directory (if seeder is selected)
    if ($this->isComponentSelected('seeder')) {
      $stubs['database/'.$this->seedersDirectory().'/.gitkeep'] = $this->pathToStub('.gitkeep');
    }

    return $stubs;
  }

  protected function isComponentSelected(string $component): bool
  {
    return in_array($component, $this->selected_components, true);
  }

  protected function pathToStub(string $filename): string
  {
    return str_replace('\\', '/', dirname(__DIR__, 4))."/stubs/$filename";
  }

  /**
   * @codeCoverageIgnore
   */
  protected function runComposerUpdate(): void
  {
    // Skip in unit tests
    if ($this->laravel->runningUnitTests()) {
      return;
    }

    $this->info('Running composer update...');
    $this->newLine();

    $process = proc_open(
      'composer update',
      [
        0 => STDIN,
        1 => STDOUT,
        2 => STDERR,
      ],
      $pipes,
      $this->laravel->basePath()
    );

    if (is_resource($process)) {
      proc_close($process);
    }
  }
}
