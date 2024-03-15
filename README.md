<img src="/.gitlab/assets/header.png" />

# Modulr

`zenphp/modulr`

Modulr is a module system for Laravel applications. It uses [Composer path repositories](https://getcomposer.org/doc/05-repositories.md#path) for autoloading, and [Laravel package discovery](https://laravel.com/docs/7.x/packages#package-discovery) for module initialization, and then provides minimal tooling to fill in any gaps.

Modulr is a reworking of [InterNACHI/modular](https://github.com/InterNACHI/modular) and expands on a few concepts like modulizing existing composer packages with ease using our `modules:install` command. Being able to bring packages into your app that are normally out of range in the vendor folder gives you a huge amount of flexibility, without the added burden of having to maintain your own package.

This project is as much a set of conventions as it is a package. The fundamental idea is that you can create “modules” in a separate `modules/` directory, which allows you to better organize large projects. These modules use the existing [Laravel package system](https://laravel.com/docs/10.x/packages), and follow existing Laravel conventions.

- [Installation](#installation)
- [Usage](#usage)

## Installation

To get started, run:

```bash
composer require zenphp/modulr
```

Laravel will auto-discover the package and everything will be automatically set up for you.

### Publish the config

While not required, it's highly recommended that you customize your default namespace for modules. By default, this is set to `Modules\`, which works just fine but makes it harder to extract your module to a separate package should you ever choose to.

We recommend configuring an organization namespace (we use `"ZenPHP"`, for example). To do this, you'll need to publish the package config:

```bash
php artisan vendor:publish --tag=modulr-config
```

### Create a module

Next, let's create a module:

```bash
php artisan modules:make companies
```

Modulr will scaffold up a new module for you:

```
modules/
  companies/
    composer.json
    src/
    tests/
    routes/
    resources/
    database/
```

It will also add two new entries to your app's `composer.json` file. The first entry registers `./modules/companies/` as a [path repository](https://getcomposer.org/doc/05-repositories.md#path), and the second requires `modules/companies:*` (like any other Composer dependency).

Modulr will then remind you to perform a Composer update, so let's do that now:

```bash
composer update modules/companies
```

### Optional: Config synchronization

You can run the sync command to make sure that your project is set up for module support:

```bash
php artisan modules:sync
```

This will add a `Modules` test suite to your `phpunit.xml` file (if one exists) and update your [PhpStorm Laravel plugin](https://plugins.jetbrains.com/plugin/7532-laravel) configuration (if it exists) to properly find your module's views.

It is safe to run this command at any time, as it will only add missing configurations. You may even want to add it to your `post-autoload-dump` scripts in your application's `composer.json` file.

## Usage

All modules follow existing Laravel conventions, and auto-discovery should work as expected in most cases:

- Commands are auto-registered with Artisan
- Migrations will be run by the Migrator
- Factories are auto-loaded for `factory()`
- Policies are auto-discovered for your Models
- Blade components will be auto-discovered
- Event listeners will be auto-discovered

### Commands

#### Package Commands

We provide a few helper commands:

- `php artisan modules:make` — scaffold a new module (--empty for and empty module direactory)
- `php artisan modules:install` - install any registered composer package as a module.
- `php artisan modules:cache` — cache the loaded modules for slightly faster auto-discovery
- `php artisan modules:clear` — clear the module cache
- `php artisan modules:sync` — update project configs (like `phpunit.xml`) with module settings
- `php artisan modules:list` — list all modules

#### Laravel “`make:`” Commands

We also add a `--module=` option to most Laravel `make:` commands so that you can use all the existing tooling that you know. The commands themselves are exactly the same, which means you can use your [custom stubs](https://laravel.com/docs/7.x/artisan#stub-customization) and everything else Laravel provides:

- `php artisan make:cast MyModuleCast --module=[module name]`
- `php artisan make:controller MyModuleController --module=[module name]`
- `php artisan make:command MyModuleCommand --module=[module name]`
- `php artisan make:component MyModuleComponent --module=[module name]`
- `php artisan make:channel MyModuleChannel --module=[module name]`
- `php artisan make:event MyModuleEvent --module=[module name]`
- `php artisan make:exception MyModuleException --module=[module name]`
- `php artisan make:factory MyModuleFactory --module=[module name]`
- `php artisan make:job MyModuleJob --module=[module name]`
- `php artisan make:listener MyModuleListener --module=[module name]`
- `php artisan make:mail MyModuleMail --module=[module name]`
- `php artisan make:middleware MyModuleMiddleware --module=[module name]`
- `php artisan make:model MyModule --module=[module name]`
- `php artisan make:notification MyModuleNotification --module=[module name]`
- `php artisan make:observer MyModuleObserver --module=[module name]`
- `php artisan make:policy MyModulePolicy --module=[module name]`
- `php artisan make:provider MyModuleProvider --module=[module name]`
- `php artisan make:request MyModuleRequest --module=[module name]`
- `php artisan make:resource MyModule --module=[module name]`
- `php artisan make:rule MyModuleRule --module=[module name]`
- `php artisan make:seeder MyModuleSeeder --module=[module name]`
- `php artisan make:test MyModuleTest --module=[module name]`

#### Other Laravel Commands

In addition to adding a `--module` option to most `make:` commands, we’ve also added the same option to the `db:seed` command. If you pass the `--module` option to `db:seed`, it will look for your seeder within your module namespace:

- `php artisan db:seed --module=[module name]` will try to call `Modules\MyModule\Database\Seeders\DatabaseSeeder`
- `php artisan db:seed --class=MySeeder --module=[module name]` will try to call `Modules\MyModule\Database\Seeders\MySeeder`

#### Blade Components

Your [Laravel Blade components](https://laravel.com/docs/blade#components) will be automatically registered for you under a [component namespace](https://laravel.com/docs/9.x/blade#manually-registering-package-components).

A few examples:

| File                                                           | Component                      |
| -------------------------------------------------------------- | ------------------------------ |
| `modules/demo/src/View/Components/Basic.php`                   | `<x-demo::basic />`            |
| `modules/demo/src/View/Components/Nested/One.php`              | `<x-demo::nested.one />`       |
| `modules/demo/src/View/Components/Nested/Two.php`              | `<x-demo::nested.two />`       |
| `modules/demo/resources/components/anonymous.blade.php`        | `<x-demo::anonymous />`        |
| `modules/demo/resources/components/anonymous/index.blade.php`  | `<x-demo::anonymous />`        |
| `modules/demo/resources/components/anonymous/nested.blade.php` | `<x-demo::anonymous.nested />` |

#### Customizing the Default Module Structure

When you call `modules:make`, Modulr will scaffold some basic boilerplate for you. If you would like to customize this behavior, you can do so by publishing the `modules.php` config file and adding your own stubs.

Both filenames and file contents support a number of placeholders. These include:

- `StubBasePath`
- `StubModuleNamespace`
- `StubComposerNamespace`
- `StubModuleNameSingular`
- `StubModuleNamePlural`
- `StubModuleName`
- `StubClassNamePrefix`
- `StubComposerName`
- `StubMigrationPrefix`
- `StubFullyQualifiedTestCaseBase`
- `StubTestCaseBase`
