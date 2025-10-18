# laravel-plugin-core
Provides a core modular Plugin system to apply to Laravel API projects

1. Add the package provider (Hdruk\LaravelPluginCore\PluginCoreServiceProvider) to host app providers or rely on package discovery.
2. Ensure config/plugin-core.php 'path' points to your plugins directory.
3. Add the middleware to the api globally (so it runs for every API request):

```php
 // Laravel 10.x
 // app/Http/Kernel.php

 'api' => [ ..., \Hdruk\LaravelPluginCore\Middleware\InjectPlugins::class, ... ]

 // Laravel 11+
 // bootstrap/app.php

 $middleware->append(\Hdruk\LaravelPluginCore\Middleware\InjectPlugins::class);
```

4. Place plugins under base_path('plugins'). Each plugin needs plugin.json and (optionally) a service provider and middleware.
5. Consider persistence for enabled/disabled state (file flag, or DB table). The manager currently only discovers packages.
6. Publish the config and update accordingly:

```php
    php artisan vendor:publish --tag=plugin-core-config
```

# Next steps and improvements:
- Persist enable/disable state and plugin order to control middleware priority.
- Add tests and error handling to ensure broken plugins cannot break the host app.
