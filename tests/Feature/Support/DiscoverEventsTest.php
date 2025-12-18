<?php

use Zen\Modulr\Support\DiscoverEvents;
use Zen\Modulr\Support\Facades\Modulr;

test('it extends laravel discover events', function (): void {
  $reflection = new ReflectionClass(DiscoverEvents::class);
  expect($reflection->getParentClass()->getName())->toBe(\Illuminate\Foundation\Events\DiscoverEvents::class);
});

test('it has class from file method', function (): void {
  $reflection = new ReflectionClass(DiscoverEvents::class);
  expect($reflection->hasMethod('classFromFile'))->toBeTrue();

  $method = $reflection->getMethod('classFromFile');
  expect($method->isStatic())->toBeTrue();
  expect($method->isProtected())->toBeTrue();
});

test('it can discover events within directories', function (): void {
  // Create a temporary directory structure
  $tempDir = sys_get_temp_dir().'/modulr_test_'.uniqid();
  mkdir($tempDir, 0777, true);

  // Create a mock event file
  $eventFile = $tempDir.'/TestEvent.php';
  file_put_contents($eventFile, '<?php class TestEvent {}');

  try {
    $events = DiscoverEvents::within($tempDir, $tempDir);
    expect($events)->toBeArray();
  } finally {
    // Cleanup
    unlink($eventFile);
    rmdir($tempDir);
  }
});

test('it calls parent class from file when no module found', function (): void {
  // Create a temporary file
  $tempDir = sys_get_temp_dir().'/modulr_test_'.uniqid();
  mkdir($tempDir, 0777, true);
  $eventFile = $tempDir.'/TestEvent.php';
  file_put_contents($eventFile, '<?php class TestEvent {}');

  try {
    // Mock Modulr facade to return null (no module found)
    Modulr::shouldReceive('moduleForPath')
      ->with(realpath($eventFile))
      ->andReturn(null);

    $file = new SplFileInfo($eventFile);
    $reflection = new ReflectionClass(DiscoverEvents::class);
    $method = $reflection->getMethod('classFromFile');

    $result = $method->invoke(null, $file, $tempDir);
    expect($result)->toBeString();
  } finally {
    // Cleanup
    unlink($eventFile);
    rmdir($tempDir);
  }
});
