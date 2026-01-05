<?php

use Zen\Modulr\Events\ControllerPromptsCollecting;

test('it can be instantiated', function (): void {
  $event = new ControllerPromptsCollecting(
    controllerType: 'resource',
    moduleName: 'TestModule',
    className: 'TestController',
  );

  expect($event)->toBeInstanceOf(ControllerPromptsCollecting::class);
});

test('it has readonly properties', function (): void {
  $event = new ControllerPromptsCollecting(
    controllerType: 'api',
    moduleName: 'MyModule',
    className: 'UserController',
  );

  expect($event->controllerType)->toBe('api');
  expect($event->moduleName)->toBe('MyModule');
  expect($event->className)->toBe('UserController');
});

test('it can add options', function (): void {
  $event = new ControllerPromptsCollecting(
    controllerType: 'resource',
    moduleName: 'TestModule',
    className: 'TestController',
  );

  $event->addOption('--requests', true);

  expect($event->getAdditionalOptions())->toBe(['--requests' => true]);
});

test('it can add multiple options', function (): void {
  $event = new ControllerPromptsCollecting(
    controllerType: 'resource',
    moduleName: 'TestModule',
    className: 'TestController',
  );

  $event->addOption('--requests', true);
  $event->addOption('--model', 'User');
  $event->addOption('--api', false);

  expect($event->getAdditionalOptions())->toBe([
    '--requests' => true,
    '--model' => 'User',
    '--api' => false,
  ]);
});

test('it returns empty array when no options added', function (): void {
  $event = new ControllerPromptsCollecting(
    controllerType: 'invokable',
    moduleName: 'TestModule',
    className: 'TestController',
  );

  expect($event->getAdditionalOptions())->toBe([]);
});

test('it overwrites option with same key', function (): void {
  $event = new ControllerPromptsCollecting(
    controllerType: 'resource',
    moduleName: 'TestModule',
    className: 'TestController',
  );

  $event->addOption('--model', 'User');
  $event->addOption('--model', 'Post');

  expect($event->getAdditionalOptions())->toBe(['--model' => 'Post']);
});
