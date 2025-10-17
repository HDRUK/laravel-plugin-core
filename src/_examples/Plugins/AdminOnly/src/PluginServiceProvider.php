<?php

namespace Plugins\AdminOnly;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class PluginServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register bindings if needed
    }

    public function boot()
    {
        // Load plugin routes if the plugin has explicit routes
        $path = __DIR__ . '/Routes/api.php';
        if (file_exists($path)) {
            Route::middleware(['api'])
                ->prefix('api')
                ->group($path);
        }
    }
}