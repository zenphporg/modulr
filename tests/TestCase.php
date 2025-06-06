<?php

namespace Zen\Modulr\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use Zen\Modulr\ModulrServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Additional setup can be added here
    }

    /**
     * Get package providers.
     *
     * @param Application $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ModulrServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param Application $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        // Setup the application environment for testing
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set up modules directory for testing
        $app['config']->set('modulr.modules_directory', $this->getTestModulesPath());
    }

    /**
     * Get the path to test modules directory.
     *
     * @return string
     */
    protected function getTestModulesPath(): string
    {
        return __DIR__.'/app/modules';
    }

    /**
     * Create a test module directory structure.
     *
     * @param string $moduleName
     * @param array $structure
     * @return string
     */
    protected function createTestModule(string $moduleName, array $structure = []): string
    {
        $modulePath = $this->getTestModulesPath().'/'.$moduleName;
        
        if (!is_dir($modulePath)) {
            mkdir($modulePath, 0755, true);
        }

        // Create basic composer.json for the module
        $composerJson = [
            'name' => 'test/'.$moduleName,
            'type' => 'library',
            'autoload' => [
                'psr-4' => [
                    'Test\\'.ucfirst($moduleName).'\\' => 'src/',
                ],
            ],
            'extra' => [
                'laravel' => [
                    'providers' => [
                        'Test\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'ServiceProvider',
                    ],
                ],
            ],
        ];

        file_put_contents(
            $modulePath.'/composer.json',
            json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        // Create additional structure if provided
        foreach ($structure as $path => $content) {
            $fullPath = $modulePath.'/'.$path;
            $dir = dirname($fullPath);
            
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            if (is_string($content)) {
                file_put_contents($fullPath, $content);
            }
        }

        return $modulePath;
    }

    /**
     * Clean up test modules after test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->cleanupTestModules();
        parent::tearDown();
    }

    /**
     * Remove test modules directory.
     *
     * @return void
     */
    protected function cleanupTestModules(): void
    {
        $modulesPath = $this->getTestModulesPath();
        
        if (is_dir($modulesPath)) {
            $this->removeDirectory($modulesPath);
        }
    }

    /**
     * Recursively remove a directory.
     *
     * @param string $dir
     * @return void
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        
        rmdir($dir);
    }
}
