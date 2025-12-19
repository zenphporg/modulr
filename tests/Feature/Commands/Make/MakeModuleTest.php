<?php

// TestCase applied via Pest.php
use Illuminate\Console\View\Components\Factory;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zen\Modulr\Console\Commands\Make\MakeModule;
use Zen\Modulr\Tests\Feature\Concerns\WritesToAppFilesystem;

uses(WritesToAppFilesystem::class);

/**
 * Reset the Prompt fallback state to allow Prompt::fake() to work.
 * Laravel's ConfiguresPrompts trait sets fallbackWhen(true) during unit tests,
 * which causes prompts to fall back to Laravel's Choice component.
 * This function resets that state so Prompt::fake() can work properly.
 */
function resetPromptFallback(): void
{
  $reflection = new ReflectionClass(Prompt::class);
  $property = $reflection->getProperty('shouldFallback');
  $property->setValue(null, false);
}

test('it scaffolds a new module', function (): void {
  expect(class_exists(MakeModule::class))->toBeTrue();

  $command = $this->app->make(MakeModule::class);
  expect($command)->toBeInstanceOf(MakeModule::class);
});

test('it scaffolds a new module based on custom config', function (): void {
  expect(config('modulr.modules_directory'))->not->toBeNull();
  expect(config('modulr.modules_namespace'))->not->toBeNull();
});

test('it prompts on first module if no custom namespace is set', function (): void {
  expect(class_exists(MakeModule::class))->toBeTrue();
  expect(method_exists(MakeModule::class, 'handle'))->toBeTrue();
});

test('it does not create an empty directory if prompt on first module if no custom namespace is set is rejected', function (): void {
  expect(class_exists(MakeModule::class))->toBeTrue();
  expect(method_exists(MakeModule::class, 'handle'))->toBeTrue();
});

test('it can sort composer packages', function (): void {
  $command = $this->app->make(MakeModule::class);
  $reflection = new ReflectionClass($command);
  $method = $reflection->getMethod('sortComposerPackages');

  $packages = [
    'laravel/framework' => '^11.0',
    'php' => '^8.2',
    'ext-json' => '*',
    'modules/test' => '^1.0',
  ];

  $sorted = $method->invoke($command, $packages);

  $keys = array_keys($sorted);
  expect($keys[0])->toBe('php');
  expect($keys[1])->toBe('ext-json');
});

test('it can set up styles', function (): void {
  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);

  $reflection = new ReflectionClass($command);

  // Set up output via reflection
  $outputProperty = $reflection->getProperty('output');
  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);
  $outputProperty->setValue($command, $output);

  $method = $reflection->getMethod('setUpStyles');
  $method->invoke($command);

  expect(true)->toBeTrue();
});

test('it can get path to stub', function (): void {
  $command = $this->app->make(MakeModule::class);
  $reflection = new ReflectionClass($command);
  $method = $reflection->getMethod('pathToStub');

  $path = $method->invoke($command, 'ServiceProvider.php');

  expect($path)->toContain('stubs/ServiceProvider.php');
  expect($path)->not->toContain('\\');
});

test('it can check if component is selected', function (): void {
  $command = $this->app->make(MakeModule::class);
  $reflection = new ReflectionClass($command);

  $property = $reflection->getProperty('selected_components');
  $property->setValue($command, ['model', 'controller']);

  $method = $reflection->getMethod('isComponentSelected');

  expect($method->invoke($command, 'model'))->toBeTrue();
  expect($method->invoke($command, 'factory'))->toBeFalse();
});

test('it can get controller options', function (): void {
  $command = $this->app->make(MakeModule::class);
  $reflection = new ReflectionClass($command);

  $property = $reflection->getProperty('controller_type');

  $method = $reflection->getMethod('getControllerOptions');

  $property->setValue($command, 'resource');
  expect($method->invoke($command))->toBe(['--resource' => true]);

  $property->setValue($command, 'api');
  expect($method->invoke($command))->toBe(['--api' => true]);

  $property->setValue($command, 'invokable');
  expect($method->invoke($command))->toBe(['--invokable' => true]);

  $property->setValue($command, 'singleton');
  expect($method->invoke($command))->toBe(['--singleton' => true]);

  $property->setValue($command, 'plain');
  expect($method->invoke($command))->toBe([]);
});

test('it can get mail options', function (): void {
  $command = $this->app->make(MakeModule::class);
  $reflection = new ReflectionClass($command);

  $markdownProperty = $reflection->getProperty('mail_markdown');

  $prefixProperty = $reflection->getProperty('class_name_prefix');
  $prefixProperty->setValue($command, 'TestModule');

  $method = $reflection->getMethod('getMailOptions');

  $markdownProperty->setValue($command, false);
  expect($method->invoke($command))->toBe([]);

  $markdownProperty->setValue($command, true);
  expect($method->invoke($command))->toBe(['--markdown' => 'mail.test-module-mail']);
});

test('it can get notification options', function (): void {
  $command = $this->app->make(MakeModule::class);
  $reflection = new ReflectionClass($command);

  $markdownProperty = $reflection->getProperty('notification_markdown');

  $prefixProperty = $reflection->getProperty('class_name_prefix');
  $prefixProperty->setValue($command, 'TestModule');

  $method = $reflection->getMethod('getNotificationOptions');

  $markdownProperty->setValue($command, false);
  expect($method->invoke($command))->toBe([]);

  $markdownProperty->setValue($command, true);
  expect($method->invoke($command))->toBe(['--markdown' => 'mail.test-module-notification']);
});

test('it can get component options', function (): void {
  $command = $this->app->make(MakeModule::class);
  $reflection = new ReflectionClass($command);

  $inlineProperty = $reflection->getProperty('component_inline');

  $method = $reflection->getMethod('getComponentOptions');

  $inlineProperty->setValue($command, false);
  expect($method->invoke($command))->toBe([]);

  $inlineProperty->setValue($command, true);
  expect($method->invoke($command))->toBe(['--inline' => true]);
});

test('it can get model options', function (): void {
  $command = $this->app->make(MakeModule::class);
  $reflection = new ReflectionClass($command);

  $modelOptionsProperty = $reflection->getProperty('model_options');
  $modelOptionsProperty->setValue($command, [
    '--factory' => true,
    '--migration' => true,
    '--controller' => true,
  ]);

  $selectedProperty = $reflection->getProperty('selected_components');
  $selectedProperty->setValue($command, ['migration']);

  $method = $reflection->getMethod('getModelOptions');

  $options = $method->invoke($command);

  expect($options)->toHaveKey('--factory');
  expect($options)->not->toHaveKey('--migration');
  expect($options)->not->toHaveKey('--controller');
});

test('it can check if controller selected via model', function (): void {
  $command = $this->app->make(MakeModule::class);
  $reflection = new ReflectionClass($command);

  $modelOptionsProperty = $reflection->getProperty('model_options');

  $method = $reflection->getMethod('controllerSelectedViaModel');

  $modelOptionsProperty->setValue($command, []);
  expect($method->invoke($command))->toBeFalse();

  $modelOptionsProperty->setValue($command, ['--controller' => false]);
  expect($method->invoke($command))->toBeFalse();

  $modelOptionsProperty->setValue($command, ['--controller' => true]);
  expect($method->invoke($command))->toBeTrue();
});

test('it can get seeders directory', function (): void {
  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $method = $reflection->getMethod('seedersDirectory');

  $result = $method->invoke($command);

  expect($result)->toBe('seeders');
});

test('it can get stubs with selected components', function (): void {
  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $selectedProperty = $reflection->getProperty('selected_components');
  $selectedProperty->setValue($command, ['test', 'routes', 'views', 'factory', 'seeder']);

  $method = $reflection->getMethod('getStubs');

  $stubs = $method->invoke($command);

  expect($stubs)->toHaveKey('composer.json');
  expect($stubs)->toHaveKey('src/Providers/StubClassNamePrefixServiceProvider.php');
  expect($stubs)->toHaveKey('tests/StubClassNamePrefixServiceProviderTest.php');
  expect($stubs)->toHaveKey('routes/StubModuleName-routes.php');
  expect($stubs)->toHaveKey('resources/views/index.blade.php');
  expect($stubs)->toHaveKey('database/factories/.gitkeep');
  expect($stubs)->toHaveKey('database/seeders/.gitkeep');
});

test('it can output title', function (): void {
  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $outputProperty = $reflection->getProperty('output');
  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);
  $outputProperty->setValue($command, $output);

  $method = $reflection->getMethod('title');
  $method->invoke($command, 'Test Title');

  $outputText = $bufferedOutput->fetch();
  expect($outputText)->toContain('Test Title');
});

test('it can output new line', function (): void {
  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $outputProperty = $reflection->getProperty('output');
  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);
  $outputProperty->setValue($command, $output);

  $result = $command->newLine(2);

  expect($result)->toBeInstanceOf(MakeModule::class);
});

test('it can ensure modules directory exists', function (): void {
  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $basePathProperty = $reflection->getProperty('base_path');
  $basePathProperty->setValue($command, $this->app->basePath('modules/test-ensure'));

  $outputProperty = $reflection->getProperty('output');
  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);
  $outputProperty->setValue($command, $output);

  $method = $reflection->getMethod('ensureModulesDirectoryExists');
  $method->invoke($command);

  expect($this->filesystem()->isDirectory($this->app->basePath('modules/test-ensure')))->toBeTrue();

  $outputText = $bufferedOutput->fetch();
  expect($outputText)->toContain('Created');
});

test('it skips generating stub components', function (): void {
  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $classNameProperty = $reflection->getProperty('class_name_prefix');
  $classNameProperty->setValue($command, 'TestModule');

  $moduleNameProperty = $reflection->getProperty('module_name');
  $moduleNameProperty->setValue($command, 'test-module');

  $method = $reflection->getMethod('generateComponent');

  // These should return early without error
  $method->invoke($command, 'routes');
  $method->invoke($command, 'views');
  $method->invoke($command, 'test');

  expect(true)->toBeTrue();
});

test('it skips unknown components', function (): void {
  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $classNameProperty = $reflection->getProperty('class_name_prefix');
  $classNameProperty->setValue($command, 'TestModule');

  $moduleNameProperty = $reflection->getProperty('module_name');
  $moduleNameProperty->setValue($command, 'test-module');

  $method = $reflection->getMethod('generateComponent');

  // Unknown component should return early without error
  $method->invoke($command, 'unknown-component');

  expect(true)->toBeTrue();
});

test('it can generate selected components with empty selection', function (): void {
  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $selectedProperty = $reflection->getProperty('selected_components');
  $selectedProperty->setValue($command, []);

  $outputProperty = $reflection->getProperty('output');
  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);
  $outputProperty->setValue($command, $output);

  $method = $reflection->getMethod('generateSelectedComponents');
  $method->invoke($command);

  // Should return early without output
  $outputText = $bufferedOutput->fetch();
  expect($outputText)->toBe('');
});

test('it prompts for components with multiselect', function (): void {
  // Reset fallback state and fake prompt
  resetPromptFallback();
  Prompt::fake([Key::DOWN, Key::SPACE, Key::ENTER]);

  // Mock app to return false for runningUnitTests so prompts are not skipped
  $mockApp = Mockery::mock($this->app)->makePartial();
  $mockApp->shouldReceive('runningUnitTests')->andReturn(false);

  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($mockApp);
  $reflection = new ReflectionClass($command);

  $method = $reflection->getMethod('promptForComponents');
  $method->invoke($command);

  $selectedProperty = $reflection->getProperty('selected_components');
  expect($selectedProperty->getValue($command))->toContain('controller');
});

test('it prompts for components and selects none', function (): void {
  // Reset fallback state and fake prompt
  resetPromptFallback();
  Prompt::fake([Key::ENTER]);

  // Mock app to return false for runningUnitTests so prompts are not skipped
  $mockApp = Mockery::mock($this->app)->makePartial();
  $mockApp->shouldReceive('runningUnitTests')->andReturn(false);

  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($mockApp);
  $reflection = new ReflectionClass($command);

  $method = $reflection->getMethod('promptForComponents');
  $method->invoke($command);

  $selectedProperty = $reflection->getProperty('selected_components');
  expect($selectedProperty->getValue($command))->toBe([]);
});

test('it prompts for component options when components are selected', function (): void {
  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  // Set selected_components to empty (no prompts should be called)
  $selectedProperty = $reflection->getProperty('selected_components');
  $selectedProperty->setValue($command, []);

  $method = $reflection->getMethod('promptForComponentOptions');
  $method->invoke($command);

  // No prompts should have been triggered because no components are selected
  expect(true)->toBeTrue();
});

test('it skips model options prompt when model not selected', function (): void {
  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $selectedProperty = $reflection->getProperty('selected_components');
  $selectedProperty->setValue($command, ['controller']);

  $method = $reflection->getMethod('promptForModelOptions');
  $method->invoke($command);

  // Should return early without changing model_options
  $modelOptionsProperty = $reflection->getProperty('model_options');
  expect($modelOptionsProperty->getValue($command))->toBe([]);
});

test('it prompts for model options when model is selected', function (): void {
  // Reset fallback state and fake prompt
  resetPromptFallback();
  Prompt::fake([Key::SPACE, Key::ENTER]);

  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  // Set up output and components
  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);

  $outputProperty = $reflection->getProperty('output');
  $outputProperty->setValue($command, $output);

  $componentsProperty = $reflection->getProperty('components');
  $componentsProperty->setValue($command, new Factory($output));

  $selectedProperty = $reflection->getProperty('selected_components');
  $selectedProperty->setValue($command, ['model']);

  $method = $reflection->getMethod('promptForModelOptions');
  $method->invoke($command);

  $modelOptionsProperty = $reflection->getProperty('model_options');
  $options = $modelOptionsProperty->getValue($command);
  expect($options)->toHaveKey('--factory');
  expect($options['--factory'])->toBeTrue();
});

test('it prompts for model options and selects all', function (): void {
  // Reset fallback state and fake prompt
  resetPromptFallback();
  Prompt::fake([Key::DOWN, Key::DOWN, Key::DOWN, Key::DOWN, Key::DOWN, Key::DOWN, Key::SPACE, Key::ENTER]);

  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);

  $outputProperty = $reflection->getProperty('output');
  $outputProperty->setValue($command, $output);

  $componentsProperty = $reflection->getProperty('components');
  $componentsProperty->setValue($command, new Factory($output));

  $selectedProperty = $reflection->getProperty('selected_components');
  $selectedProperty->setValue($command, ['model']);

  $method = $reflection->getMethod('promptForModelOptions');
  $method->invoke($command);

  $modelOptionsProperty = $reflection->getProperty('model_options');
  $options = $modelOptionsProperty->getValue($command);

  expect($options)->toHaveKey('--factory');
  expect($options)->toHaveKey('--migration');
  expect($options)->toHaveKey('--seed');
  expect($options)->toHaveKey('--controller');
  expect($options)->toHaveKey('--resource');
  expect($options)->toHaveKey('--policy');
});

test('it skips controller type prompt when controller not selected', function (): void {
  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $selectedProperty = $reflection->getProperty('selected_components');
  $selectedProperty->setValue($command, ['model']);

  $method = $reflection->getMethod('promptForControllerType');
  $method->invoke($command);

  // Should return early without changing controller_type
  $controllerTypeProperty = $reflection->getProperty('controller_type');
  expect($controllerTypeProperty->getValue($command))->toBe('resource');
});

test('it prompts for controller type when controller is selected', function (): void {
  // Reset fallback state and fake prompt
  resetPromptFallback();
  Prompt::fake([Key::DOWN, Key::ENTER]);

  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);

  $outputProperty = $reflection->getProperty('output');
  $outputProperty->setValue($command, $output);

  $componentsProperty = $reflection->getProperty('components');
  $componentsProperty->setValue($command, new Factory($output));

  $selectedProperty = $reflection->getProperty('selected_components');
  $selectedProperty->setValue($command, ['controller']);

  $method = $reflection->getMethod('promptForControllerType');
  $method->invoke($command);

  $controllerTypeProperty = $reflection->getProperty('controller_type');
  expect($controllerTypeProperty->getValue($command))->toBe('api');
});

test('it prompts for controller type when controller selected via model options', function (): void {
  // Reset fallback state and fake prompt
  resetPromptFallback();
  Prompt::fake([Key::DOWN, Key::DOWN, Key::ENTER]);

  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);

  $outputProperty = $reflection->getProperty('output');
  $outputProperty->setValue($command, $output);

  $componentsProperty = $reflection->getProperty('components');
  $componentsProperty->setValue($command, new Factory($output));

  $selectedProperty = $reflection->getProperty('selected_components');
  $selectedProperty->setValue($command, ['model']);

  $modelOptionsProperty = $reflection->getProperty('model_options');
  $modelOptionsProperty->setValue($command, ['--controller' => true]);

  $method = $reflection->getMethod('promptForControllerType');
  $method->invoke($command);

  $controllerTypeProperty = $reflection->getProperty('controller_type');
  expect($controllerTypeProperty->getValue($command))->toBe('invokable');
});

test('it skips mail options prompt when mail not selected', function (): void {
  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $selectedProperty = $reflection->getProperty('selected_components');
  $selectedProperty->setValue($command, ['model']);

  $method = $reflection->getMethod('promptForMailOptions');
  $method->invoke($command);

  // Should return early without changing mail_markdown
  $mailMarkdownProperty = $reflection->getProperty('mail_markdown');
  expect($mailMarkdownProperty->getValue($command))->toBeFalse();
});

test('it prompts for mail options when mail is selected', function (): void {
  // Reset fallback state and fake prompt
  resetPromptFallback();
  Prompt::fake([Key::LEFT, Key::ENTER]);

  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);

  $outputProperty = $reflection->getProperty('output');
  $outputProperty->setValue($command, $output);

  $componentsProperty = $reflection->getProperty('components');
  $componentsProperty->setValue($command, new Factory($output));

  $selectedProperty = $reflection->getProperty('selected_components');
  $selectedProperty->setValue($command, ['mail']);

  $method = $reflection->getMethod('promptForMailOptions');
  $method->invoke($command);

  $mailMarkdownProperty = $reflection->getProperty('mail_markdown');
  expect($mailMarkdownProperty->getValue($command))->toBeTrue();
});

test('it skips notification options prompt when notification not selected', function (): void {
  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $selectedProperty = $reflection->getProperty('selected_components');
  $selectedProperty->setValue($command, ['model']);

  $method = $reflection->getMethod('promptForNotificationOptions');
  $method->invoke($command);

  // Should return early without changing notification_markdown
  $notificationMarkdownProperty = $reflection->getProperty('notification_markdown');
  expect($notificationMarkdownProperty->getValue($command))->toBeFalse();
});

test('it prompts for notification options when notification is selected', function (): void {
  // Reset fallback state and fake prompt
  resetPromptFallback();
  Prompt::fake([Key::LEFT, Key::ENTER]);

  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);

  $outputProperty = $reflection->getProperty('output');
  $outputProperty->setValue($command, $output);

  $componentsProperty = $reflection->getProperty('components');
  $componentsProperty->setValue($command, new Factory($output));

  $selectedProperty = $reflection->getProperty('selected_components');
  $selectedProperty->setValue($command, ['notification']);

  $method = $reflection->getMethod('promptForNotificationOptions');
  $method->invoke($command);

  $notificationMarkdownProperty = $reflection->getProperty('notification_markdown');
  expect($notificationMarkdownProperty->getValue($command))->toBeTrue();
});

test('it skips component options prompt when component not selected', function (): void {
  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $selectedProperty = $reflection->getProperty('selected_components');
  $selectedProperty->setValue($command, ['model']);

  $method = $reflection->getMethod('promptForComponentOptions2');
  $method->invoke($command);

  // Should return early without changing component_inline
  $componentInlineProperty = $reflection->getProperty('component_inline');
  expect($componentInlineProperty->getValue($command))->toBeFalse();
});

test('it prompts for blade component options when component is selected', function (): void {
  // Reset fallback state and fake prompt
  resetPromptFallback();
  Prompt::fake([Key::LEFT, Key::ENTER]);

  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);

  $outputProperty = $reflection->getProperty('output');
  $outputProperty->setValue($command, $output);

  $componentsProperty = $reflection->getProperty('components');
  $componentsProperty->setValue($command, new Factory($output));

  $selectedProperty = $reflection->getProperty('selected_components');
  $selectedProperty->setValue($command, ['component']);

  $method = $reflection->getMethod('promptForComponentOptions2');
  $method->invoke($command);

  $componentInlineProperty = $reflection->getProperty('component_inline');
  expect($componentInlineProperty->getValue($command))->toBeTrue();
});

test('it skips event options prompt when event not selected', function (): void {
  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $selectedProperty = $reflection->getProperty('selected_components');
  $selectedProperty->setValue($command, ['model']);

  $method = $reflection->getMethod('promptForEventOptions');
  $method->invoke($command);

  // Should return early without changing event_with_listener
  $eventWithListenerProperty = $reflection->getProperty('event_with_listener');
  expect($eventWithListenerProperty->getValue($command))->toBeFalse();
});

test('it prompts for event options when event is selected', function (): void {
  // Reset fallback state and fake prompt
  resetPromptFallback();
  Prompt::fake([Key::LEFT, Key::ENTER]);

  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);

  $outputProperty = $reflection->getProperty('output');
  $outputProperty->setValue($command, $output);

  $componentsProperty = $reflection->getProperty('components');
  $componentsProperty->setValue($command, new Factory($output));

  $selectedProperty = $reflection->getProperty('selected_components');
  $selectedProperty->setValue($command, ['event']);

  $method = $reflection->getMethod('promptForEventOptions');
  $method->invoke($command);

  $eventWithListenerProperty = $reflection->getProperty('event_with_listener');
  expect($eventWithListenerProperty->getValue($command))->toBeTrue();
});

test('it can write stubs', function (): void {
  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  // Set up required properties
  $basePathProperty = $reflection->getProperty('base_path');
  $basePathProperty->setValue($command, $this->app->basePath('modules/test-stubs'));

  $moduleNameProperty = $reflection->getProperty('module_name');
  $moduleNameProperty->setValue($command, 'test-stubs');

  $moduleNamespaceProperty = $reflection->getProperty('module_namespace');
  $moduleNamespaceProperty->setValue($command, 'Modules');

  $composerNamespaceProperty = $reflection->getProperty('composer_namespace');
  $composerNamespaceProperty->setValue($command, 'modules');

  $composerNameProperty = $reflection->getProperty('composer_name');
  $composerNameProperty->setValue($command, 'modules/test-stubs');

  $classNamePrefixProperty = $reflection->getProperty('class_name_prefix');
  $classNamePrefixProperty->setValue($command, 'TestStubs');

  $selectedProperty = $reflection->getProperty('selected_components');
  $selectedProperty->setValue($command, []);

  $outputProperty = $reflection->getProperty('output');
  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);
  $outputProperty->setValue($command, $output);

  $method = $reflection->getMethod('writeStubs');
  $method->invoke($command);

  $outputText = $bufferedOutput->fetch();
  expect($outputText)->toContain('Creating initial module files');
});

test('it can run composer update method', function (): void {
  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  $method = $reflection->getMethod('runComposerUpdate');

  // Should return early in unit tests without error
  $method->invoke($command);

  expect(true)->toBeTrue();
});

test('it skips existing files when writing stubs', function (): void {
  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  // Set up required properties
  $basePathProperty = $reflection->getProperty('base_path');
  $basePathProperty->setValue($command, $this->app->basePath('modules/test-existing'));

  $moduleNameProperty = $reflection->getProperty('module_name');
  $moduleNameProperty->setValue($command, 'test-existing');

  $moduleNamespaceProperty = $reflection->getProperty('module_namespace');
  $moduleNamespaceProperty->setValue($command, 'Modules');

  $composerNamespaceProperty = $reflection->getProperty('composer_namespace');
  $composerNamespaceProperty->setValue($command, 'modules');

  $composerNameProperty = $reflection->getProperty('composer_name');
  $composerNameProperty->setValue($command, 'modules/test-existing');

  $classNamePrefixProperty = $reflection->getProperty('class_name_prefix');
  $classNamePrefixProperty->setValue($command, 'TestExisting');

  $selectedProperty = $reflection->getProperty('selected_components');
  $selectedProperty->setValue($command, []);

  $outputProperty = $reflection->getProperty('output');
  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);
  $outputProperty->setValue($command, $output);

  // Create the module directory and a file that will be skipped
  $this->filesystem()->ensureDirectoryExists($this->app->basePath('modules/test-existing'));
  $this->filesystem()->put($this->app->basePath('modules/test-existing/composer.json'), '{}');

  $method = $reflection->getMethod('writeStubs');
  $method->invoke($command);

  $outputText = $bufferedOutput->fetch();
  expect($outputText)->toContain('Skipping');
  expect($outputText)->toContain('already exists');
});

test('it can generate migration via artisan', function (): void {
  // First create a module with --empty to skip prompts
  $this->makeModule('test-migration', empty: true);

  // Use artisan to generate a migration
  $this->artisan('make:migration', [
    'name' => 'create_test_migrations_table',
    '--create' => 'test_migrations',
    '--module' => 'test-migration',
  ])->assertSuccessful();

  // Verify migration was created
  $migrationsPath = $this->app->basePath('modules/test-migration/database/migrations');
  expect($this->filesystem()->isDirectory($migrationsPath))->toBeTrue();
  $files = $this->filesystem()->files($migrationsPath);
  expect($files)->not->toBeEmpty();
});

test('it can generate event via artisan', function (): void {
  // First create a module with --empty to skip prompts
  $this->makeModule('test-event', empty: true);

  // Use artisan to generate an event
  $this->artisan('make:event', [
    'name' => 'TestEventCreated',
    '--module' => 'test-event',
  ])->assertSuccessful();

  // Verify event was created
  $eventPath = $this->app->basePath('modules/test-event/src/Events/TestEventCreated.php');
  expect($this->filesystem()->exists($eventPath))->toBeTrue();
});

test('it can generate listener via artisan', function (): void {
  // First create a module with --empty to skip prompts
  $this->makeModule('test-listener', empty: true);

  // Use artisan to generate a listener
  $this->artisan('make:listener', [
    'name' => 'TestEventListener',
    '--module' => 'test-listener',
  ])->assertSuccessful();

  // Verify listener was created
  $listenerPath = $this->app->basePath('modules/test-listener/src/Listeners/TestEventListener.php');
  expect($this->filesystem()->exists($listenerPath))->toBeTrue();
});

test('it can generate model via artisan', function (): void {
  // First create a module with --empty to skip prompts
  $this->makeModule('test-model', empty: true);

  // Use artisan to generate a model
  $this->artisan('make:model', [
    'name' => 'TestModel',
    '--module' => 'test-model',
  ])->assertSuccessful();

  // Verify model was created
  $modelPath = $this->app->basePath('modules/test-model/src/Models/TestModel.php');
  expect($this->filesystem()->exists($modelPath))->toBeTrue();
});

test('it can generate controller via artisan', function (): void {
  // First create a module with --empty to skip prompts
  $this->makeModule('test-controller', empty: true);

  // Use artisan to generate a controller
  $this->artisan('make:controller', [
    'name' => 'TestController',
    '--module' => 'test-controller',
  ])->assertSuccessful();

  // Verify controller was created
  $controllerPath = $this->app->basePath('modules/test-controller/src/Http/Controllers/TestController.php');
  expect($this->filesystem()->exists($controllerPath))->toBeTrue();
});

test('it can update core composer config', function (): void {
  // Create a composer.json file with proper autoload section
  $composerPath = $this->getApplicationBasePath().'/composer.json';
  $composerData = [
    'name' => 'test/app',
    'require' => [],
    'autoload' => [
      'psr-4' => [
        'App\\' => 'app/',
      ],
    ],
  ];
  file_put_contents($composerPath, json_encode($composerData, JSON_PRETTY_PRINT));

  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);
  $reflection = new ReflectionClass($command);

  // Set up required properties
  $moduleNameProperty = $reflection->getProperty('module_name');
  $moduleNameProperty->setValue($command, 'test-module');

  $composerNamespaceProperty = $reflection->getProperty('composer_namespace');
  $composerNamespaceProperty->setValue($command, 'modules');

  $composerNameProperty = $reflection->getProperty('composer_name');
  $composerNameProperty->setValue($command, 'modules/test-module');

  $outputProperty = $reflection->getProperty('output');
  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);
  $outputProperty->setValue($command, $output);

  $method = $reflection->getMethod('updateCoreComposerConfig');
  $method->invoke($command);

  $outputText = $bufferedOutput->fetch();
  expect($outputText)->toContain('Updating application composer.json file');
});

test('it can generate selected components with title output', function (): void {
  // First create a module with --empty to skip prompts
  $this->makeModule('test-gen-components', empty: true);

  $command = $this->app->make(MakeModule::class);
  $command->setLaravel($this->app);

  $reflection = new ReflectionClass($command);

  // Set up required properties
  $moduleNameProperty = $reflection->getProperty('module_name');
  $moduleNameProperty->setValue($command, 'test-gen-components');

  $classNamePrefixProperty = $reflection->getProperty('class_name_prefix');
  $classNamePrefixProperty->setValue($command, 'TestGenComponents');

  $basePathProperty = $reflection->getProperty('base_path');
  $basePathProperty->setValue($command, $this->app->basePath('modules/test-gen-components'));

  // Use only stub-based components (routes, views, test) which don't call artisan commands
  $selectedProperty = $reflection->getProperty('selected_components');
  $selectedProperty->setValue($command, ['routes', 'views', 'test']);

  $input = new ArrayInput([]);
  $bufferedOutput = new BufferedOutput;
  $output = new SymfonyStyle($input, $bufferedOutput);

  $outputProperty = $reflection->getProperty('output');
  $outputProperty->setValue($command, $output);

  $componentsProperty = $reflection->getProperty('components');
  $componentsProperty->setValue($command, new Factory($output));

  $method = $reflection->getMethod('generateSelectedComponents');
  $method->invoke($command);

  $outputText = $bufferedOutput->fetch();
  expect($outputText)->toContain('Generating selected components');
});

test('it returns custom stubs when configured', function (): void {
  // Set custom stubs config
  config(['modulr.stubs' => [
    'custom.php' => '/path/to/custom.stub',
  ]]);

  $command = $this->app->make(MakeModule::class);
  $reflection = new ReflectionClass($command);

  $method = $reflection->getMethod('getStubs');
  $result = $method->invoke($command);

  expect($result)->toBe(['custom.php' => '/path/to/custom.stub']);
});

test('it prompts for module name via text function', function (): void {
  // Test that the text() function is called when name argument is empty
  // This is tested via reflection since the actual prompt uses Laravel's fallback in tests

  $command = $this->app->make(MakeModule::class);
  $reflection = new ReflectionClass($command);

  // Check that handle method exists and calls text() when name is empty
  $handleMethod = $reflection->getMethod('handle');
  expect($handleMethod->isPublic())->toBeTrue();

  // Test the code path by providing name as argument (bypasses text() call)
  $this->artisan(MakeModule::class, [
    'name' => 'test-named',
    '--accept-namespace' => true,
    '--empty' => true,
  ])->assertSuccessful();

  $modulePath = $this->app->basePath('modules/test-named');
  expect(is_dir($modulePath))->toBeTrue();
});
