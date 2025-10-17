# laravel-plugin-core
Provides a core modular Plugin system to apply to Laravel API projects

1. Add the package provider (Hdruk\PluginCore\PluginCoreServiceProvider) to host app providers or rely on package discovery.
2. Ensure config/plugin-core.php 'path' points to your plugins directory.
3. In app/Http/Kernel.php add the middleware to the api group (so it runs for every API request):

```php
 'api' => [ ..., \PluginCore\Middleware\InjectPlugins::class, ... ]
```

4. Place plugins under base_path('plugins'). Each plugin needs plugin.json and (optionally) a service provider and middleware.
5. Consider persistence for enabled/disabled state (file flag, or DB table). The manager currently only discovers packages.


# Next steps and improvements:
- Persist enable/disable state and plugin order to control middleware priority.
- Add tests and error handling to ensure broken plugins cannot break the host app.
