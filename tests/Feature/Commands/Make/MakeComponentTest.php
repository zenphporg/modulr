<?php

use Zen\Modulr\Console\Commands\Make\MakeComponent;
use Zen\Modulr\Console\Commands\Make\MakeModule;

uses(Zen\Modulr\Tests\Concerns\WritesToAppFilesystem::class);
uses(Zen\Modulr\Tests\Concerns\TestsMakeCommands::class);

test('it overrides the default command', function () {
  $this->requiresLaravelVersion('9.2.0');

  $this->artisan('make:component', ['--help' => true])
    ->expectsOutputToContain('--module')
    ->assertExitCode(0);
});

test('it scaffolds a component in the module when module option is set', function () {
  $this->artisan(MakeModule::class, [
    'name' => 'test-module',
    '--accept-namespace' => true,
  ]);

  $command = MakeComponent::class;
  $arguments = ['name' => 'TestComponent'];
  $expected_path = 'src/View/Components/TestComponent.php';
  $expected_substrings = [
    'namespace Modules\TestModule\View\Components',
    'class TestComponent',
  ];

  $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);

  $expected_view_path = 'resources/views/components/test-component.blade.php';
  $this->assertModuleFile($expected_view_path);
});

test('it scaffolds a component in the app when module option is missing', function () {
  $command = MakeComponent::class;
  $arguments = ['name' => 'TestComponent'];
  $expected_path = 'app/View/Components/TestComponent.php';
  $expected_substrings = [
    'namespace App\View\Components',
    'class TestComponent',
  ];

  $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);

  $expected_view_path = 'resources/views/components/test-component.blade.php';
  $this->assertBaseFile($expected_view_path);
});
