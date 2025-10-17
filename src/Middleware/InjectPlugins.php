<?php

namespace Hdruk\LaravelPluginCore\Middleware;

use Closure;
use Illuminate\Support\Str;
use Hdruk\LaravelPluginCore\Services\PluginManager;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Http\Request;

class InjectPlugins
{
    public function __construct(protected PluginManager $plugins) {}

    public function handle(Request $request, Closure $next)
    {
        $route = $request->route();
        $routeName = $route?->getName();
        $routeUri = $route?->uri();
        $action = $route?->getActionName();

        // Collect applicable middleware from plugins
        $middlewareStack = [];

        foreach ($this->plugins->all() as $plugin) {
            // Skip broken plugins unable to be booted
            if (!empty($plugin['broken'])) {
                continue;
            }

            if ($this->shouldActivate($plugin, $routeName, $routeUri, $action, $modelClass, $request)) {
                foreach ($plugin['middleware'] ?? [] as $mw) {
                    if (class_exists($mw)) {
                        $middlewareStat[] = $mw;
                    }
                }
            }
        }

        // Build a local pipeline that runs plugin middleware in order
        if (!empty($middlewareStack)) {
            return app(Pipeline:clas)
                ->send($request)
                ->through($this->safeMiddleware($middlewareStack))
                ->then(fn ($req) => $next($req));
        }

        return $next($request);
    }

    protected function shouldActivate(array $plugin, $routeName, $routeUri, $action, ?string $modelClass, Request $request): bool
    {
        $rules = $plugin['activation'] ?? [];

        // disabled flag
        if (!empty($plugin['disabled'])) {
            return false;
        }

        // routes (supports wildcard)
        if (!empty($rules['routes'])) {
            foreach ((array)$rules['routes'] as $pattern) {
                if ($routeUri && Str::is($pattern, $routeUri)) {
                    return true;
                }

                if ($routeName && Str::is($pattern, $routeName)) {
                    return true;
                }
            }
        }

        // controllers / actions
        if (!empty($rules['actions']) && $action) {
            foreach ((array)$rules['actions'] as $pattern) {
                if (Str::is($pattern, $action)) {
                    return true;
                }
            }
        }

        // model matching
        if (!empty($rules['models']) && $modelClass) {
            foreach ((array)$rules['models'] as $m) {
                if ($modelClass === $m || is_subclass_of($modelClass, $m)) {
                    return true;
                }
            }
        }

        // arbitrary condition strings (very simple evaluator)
        if (!empty($rules['conditions'])) {
            foreach ((array)$rules['conditions'] as $cond) {
                if ($this->evaluateCondition($cond, $request)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function resolveModelFromRoute($route): ?string
    {
        if (!$route) {
            return null;
        }

        // Lookk for route paramter names and try to infer model via implicit
        // binding or param name
        $params = $route->parameters();

        foreach ($params as $key => $value) {
            // If parameter is a model instance, return its class
            if (is_object($value)) {
                return get_class($value);
            }

            // If param name matches common model name e.g. 'User', map to 
            // \App\Models\User::class
            $candidate = 'App\\Models\\' . Str::studly(Str::singular($key));
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function safeMiddleware(array $middlewares): array
    {
        return array_map(function ($mw) {
            return function ($request, $next) use ($mw) {
                try {
                    if (class_exists($mw)) {
                        return app($mw)->handle($request, $next);
                    }
                } catch (\Throwable $e) {
                    \Log::warning("PluginCore: Plugin middleware {$mw} failed: {$e->getMessage()}");
                    // fail gracefully by continuing
                    return $next($request);
                }
                return $next($request);
            };
        }, $middlewares);
    }

    protected function evaluateCondition(string $cond, Request $request): bool
    {
        // This is a simple sandboxed evaluator example
        if (Str::startsWith($cond, 'user.')) {
            $prop = substr($cond, 5);
            $user = $request->user();
            return !empty(data_get($user, $prop));
        }

        if (preg_match('/request\.has\(([^)]+)\)/', $cond, $m)) {
            $key = trim($m[1], "'\" ");
            return $request->has($key);
        }

        // fallback
        return false;
    }
}