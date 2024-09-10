<?php

namespace Zen\Modulr\Providers;

use Illuminate\Console\Application as Artisan;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand as OriginalMakeMigrationCommand;
use Illuminate\Support\ServiceProvider;
use Zen\Modulr\Console\Commands\Database\SeedCommand;
use Zen\Modulr\Console\Commands\Make\MakeCast;
use Zen\Modulr\Console\Commands\Make\MakeChannel;
use Zen\Modulr\Console\Commands\Make\MakeCommand;
use Zen\Modulr\Console\Commands\Make\MakeComponent;
use Zen\Modulr\Console\Commands\Make\MakeController;
use Zen\Modulr\Console\Commands\Make\MakeEvent;
use Zen\Modulr\Console\Commands\Make\MakeException;
use Zen\Modulr\Console\Commands\Make\MakeFactory;
use Zen\Modulr\Console\Commands\Make\MakeJob;
use Zen\Modulr\Console\Commands\Make\MakeListener;
use Zen\Modulr\Console\Commands\Make\MakeMail;
use Zen\Modulr\Console\Commands\Make\MakeMiddleware;
use Zen\Modulr\Console\Commands\Make\MakeMigration;
use Zen\Modulr\Console\Commands\Make\MakeModel;
use Zen\Modulr\Console\Commands\Make\MakeNotification;
use Zen\Modulr\Console\Commands\Make\MakeObserver;
use Zen\Modulr\Console\Commands\Make\MakePolicy;
use Zen\Modulr\Console\Commands\Make\MakeProvider;
use Zen\Modulr\Console\Commands\Make\MakeRequest;
use Zen\Modulr\Console\Commands\Make\MakeResource;
use Zen\Modulr\Console\Commands\Make\MakeRule;
use Zen\Modulr\Console\Commands\Make\MakeSeeder;
use Zen\Modulr\Console\Commands\Make\MakeTest;

class CommandsServiceProvider extends ServiceProvider
{
  /**
   * @var array|string[]
   */
  protected array $overrides = [
    'command.cast.make' => MakeCast::class,
    'command.controller.make' => MakeController::class,
    'command.console.make' => MakeCommand::class,
    'command.channel.make' => MakeChannel::class,
    'command.event.make' => MakeEvent::class,
    'command.exception.make' => MakeException::class,
    'command.factory.make' => MakeFactory::class,
    'command.job.make' => MakeJob::class,
    'command.listener.make' => MakeListener::class,
    'command.mail.make' => MakeMail::class,
    'command.middleware.make' => MakeMiddleware::class,
    'command.model.make' => MakeModel::class,
    'command.notification.make' => MakeNotification::class,
    'command.observer.make' => MakeObserver::class,
    'command.policy.make' => MakePolicy::class,
    'command.provider.make' => MakeProvider::class,
    'command.request.make' => MakeRequest::class,
    'command.resource.make' => MakeResource::class,
    'command.rule.make' => MakeRule::class,
    'command.seeder.make' => MakeSeeder::class,
    'command.test.make' => MakeTest::class,
    'command.component.make' => MakeComponent::class,
    'command.seed' => SeedCommand::class,
  ];

  public function register(): void
  {
    // Register our overrides via the "booted" event to ensure that we override
    // the default behavior regardless of which service provider happens to be
    // bootstrapped first.
    $this->app->booted(function (): void {
      Artisan::starting(function (): void {
        $this->registerMakeCommandOverrides();
        $this->registerMigrationCommandOverrides();
      });
    });
  }

  /**
   * @return void
   */
  protected function registerMakeCommandOverrides()
  {
    foreach ($this->overrides as $alias => $class_name) {
      $this->app->singleton($alias, $class_name);
      $this->app->singleton(get_parent_class($class_name), $class_name);
    }
  }

  /**
   * @return void
   */
  protected function registerMigrationCommandOverrides()
  {
    // Laravel 8
    $this->app->singleton('command.migrate.make', function (Application $app): MakeMigration {
      return new MakeMigration($app['migration.creator'], $app['composer']);
    });

    // Laravel 9
    $this->app->singleton(OriginalMakeMigrationCommand::class, function (Application $app): MakeMigration {
      return new MakeMigration($app['migration.creator'], $app['composer']);
    });
  }
}
