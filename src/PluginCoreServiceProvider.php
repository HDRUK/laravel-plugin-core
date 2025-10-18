<?php

namespace Hdruk\LaravelPluginCore;

use Illuminate\Support\ServiceProvider;
use Hdruk\LaravelPluginCore\Services\PluginManager;

class PluginCoreServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/plugin-core.php', 'plugin-core');

        // Bind singleton so it initialises on first use
        $this->app->singleton(PluginManager::class, function($app) {
            $path = config('plugin-core.path', base_path('app/Plugins'));
            return new PluginManager($path);
        });

        // Force early load of PluginManager (so autoloaders are registered before boot)
        $this->app->make(PluginManager::class);
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/plugin-core.php' => config_path('plugin-core.php'),
        ], 'plugin-core-config');

        $this->app->router->aliasMiddleware('inject.plugins', \Hdruk\LaravelPluginCore\Middleware\InjectPlugins::class);

        // Resolve PluginManager from container
        $plugins = $this->app->make(PluginManager::class);

        foreach ($plugins->all() as $plugin) {
            if (!empty($plugin['service_provider'])) {
                try {
                    \Log::info('plugin service provider path: ' . $plugin['service_provider']);
                    
                    // Register exactly as declared
                    $this->app->register($plugin['service_provider']);
                } catch (\Throwable $e) {
                    \Log::error('Failed to register plugin provider: ' . $e->getMessage());
                    $plugins->markAsBroken($plugin['slug'], $e);
                }
            }
        }
    }
}
