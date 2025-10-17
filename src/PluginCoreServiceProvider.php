<?php

namespace Hdruk\PluginCore;

use Illuminate\Support\ServiceProvider;
use Hdruk\PluginCore\Services\PluginManager;

class PluginCoreServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(PluginManager::class, function() {
            return new PluginManager(config('plugin-core.path'));
        });

        $this->mergeConfigFrom(__DIR__ . '/../config/plugin-core.php', 'plugin-core');
    }

    public function boot(PluginManager $plugins)
    {
        $this->app->router->aliasMiddleware('inject.plugins', \Hdruk\PluginCore\Middleware\InjectPlugins::class);

        foreach ($plugins->all() as $plugin) {
            if (!empty($plugin['service_provider'])) {
                try {
                    $this->app->register($plugin['service_provider']);
                } catch (\Throwable $e) {
                    \Log::error('Failed to register plugin provider: ' . $e->getMessage());
                    $plugins->markAsBroken($plugin['slug'], $e);
                }
            }
        }
    }
}