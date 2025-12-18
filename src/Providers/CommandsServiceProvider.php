<?php

declare(strict_types=1);

namespace Zen\Modulr\Providers;

use Illuminate\Console\Application as Artisan;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand as OriginalMakeMigrationCommand;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Support\Composer;
use Illuminate\Support\ServiceProvider;
use Override;
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

  #[Override]
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

  protected function registerMakeCommandOverrides(): void
  {
    foreach ($this->overrides as $alias => $class_name) {
      $parentClass = get_parent_class($class_name);
      $this->app->singleton($alias, $class_name);
      if ($parentClass !== false) {
        $this->app->singleton($parentClass, $class_name);
      }
    }
  }

  protected function registerMigrationCommandOverrides(): void
  {
    // Laravel 8
    $this->app->singleton('command.migrate.make', function (Application $application): MakeMigration {
      /** @var MigrationCreator $creator */
      $creator = $application->make('migration.creator');
      /** @var Composer $composer */
      $composer = $application->make(Composer::class);

      return new MakeMigration($creator, $composer);
    });

    // Laravel 9
    $this->app->singleton(function (Application $application): OriginalMakeMigrationCommand {
      /** @var MigrationCreator $creator */
      $creator = $application->make('migration.creator');
      /** @var Composer $composer */
      $composer = $application->make(Composer::class);

      return new MakeMigration($creator, $composer);
    });
  }
}
