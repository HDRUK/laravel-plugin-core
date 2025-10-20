# laravel-plugin-core
A small, framework-agnostic-ish Laravel package that provides a lightweight plugin system for API projects.

#### Key responsibilities:

- Discover plugin folders under a configured path.
- Register plugin service providers.
- Run plugin middleware conditionally per-request.

#### Core classes:

- [Hdruk\LaravelPluginCore\PluginCoreServiceProvider](https://github.com/HDRUK/laravel-plugin-core/blob/main/src/PluginCoreServiceProvider.php)
- [Hdruk\LaravelPluginCore\Middleware\InjectPlugins](https://github.com/HDRUK/laravel-plugin-core/blob/main/src/Middleware/InjectPlugins.php)
- [Hdruk\LaravelPluginCore\Services\PluginManager](https://github.com/HDRUK/laravel-plugin-core/blob/main/src/Services/PluginManager.php)

#### Configuration:

- Default config file: `plugin-core.php`
- Example plugin manifest: plugin.json
- Example plugin provider: Plugins\AdminOnly\PluginServiceProvider
- Example plugin middleware: Plugins\AdminOnly\Middleware\RestrictToAdmin

### 1) Installing
Install via composer:

```php
composer require hdruk/laravel-plugin-core
```

Or add the package to your composer.json and run composer update:

```json
{
  "require": {
    "hdruk/laravel-plugin-core": "^1.0"
  }
}
```

The package provides auto-discovery via composer.json. If you prefer manual registration, add the provider:

Publish the configuration to choose your plugins directory:

```php
php artisan vendor:publish --tag=plugin-core-config
# This will publish config/plugin-core.php
```

By default the package reads `config('plugin-core.path')` (see config/plugin-core.php).

### 2) Implementing plugins
Overview:

Plugins are placed under the directory configured by `plugin-core.path` (default: base_path('plugins')).
Each plugin is a folder that contains a `plugin.json` manifest and an optional `src` directory with a ServiceProvider and middleware.

#### Minimum manifest (plugin.json):

- slug — unique identifier (recommended)
- service_provider — fully-qualified provider class to register
- middleware — array of FQCN middleware to run when the plugin activates
- activation — rules to decide when to run middleware (routes, models, conditions, etc.)

See the example manifest: [plugin.json](https://github.com/HDRUK/laravel-plugin-core/blob/main/src/_examples/Plugins/AdminOnly/plugin.json)

## Example plugin layout:

```
plugins/
  admin-only/
    plugin.json
    src/
      PluginServiceProvider.php
      Middleware/
        RestrictToAdmin.php
```

Implement a ServiceProvider in your plugin:

Register routes, publish resources or bind services within your plugin provider.

Example: [Plugins\AdminOnly\PluginServiceProvider](https://github.com/HDRUK/laravel-plugin-core/blob/main/src/_examples/Plugins/AdminOnly/src/PluginServiceProvider.php)

Implement middleware in your plugin:

Middleware classes should expose `handle(Request $request, Closure $next)` as usual.

Example: [Plugins\AdminOnly\Middleware\RestrictToAdmin](https://github.com/HDRUK/laravel-plugin-core/blob/main/src/_examples/Plugins/AdminOnly/src/Middleware/RestrictToAdmin.php)

Activate plugin middleware per-request

The package middleware [Hdruk\LaravelPluginCore\Middleware\InjectPlugins](https://github.com/HDRUK/laravel-plugin-core/blob/main/src/Middleware/InjectPlugins.php) inspects each request and evaluates plugin activation rules.

To enable injection, add the middleware to your API stack:
Laravel 10 (Kernel):
```php
<?php
// app/Http/Kernel.php
'api' => [
    // ...
    \Hdruk\LaravelPluginCore\Middleware\InjectPlugins::class,
],
```

Laravel 11+ (bootstrap):
```php
<?php
// bootstrap/app.php
$middleware->append(\Hdruk\LaravelPluginCore\Middleware\InjectPlugins::class);
```

Activation rules supported (examples from `InjectPlugins`):

- routes: wildcards supported (e.g. `api/admin/*`)
- actions: controller/action pattern matching
- models: match route model binding or model param names (e.g. `App\Models\User`)
- conditions: simple expressions such as `user.is_admin` or `request.has('flag')`

### Notes and next steps:

Currently the [Hdruk\LaravelPluginCore\Services\PluginManager](https://github.com/HDRUK/laravel-plugin-core/blob/main/src/Services/PluginManager.php) only discovers plugins and registers autoloaders and providers.

- (TODO) - Persisted enable/disable state and ordering are not implemented.
- The manager will mark plugins as broken when provider registration fails; broken plugins are skipped during middleware injection.
- Consider adding persistence for enable/disable and middleware ordering to control priority and lifecycle.
If you need a working example, inspect the example plugin at AdminOnly.