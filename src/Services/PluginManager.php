<?php

namespace Hdruk\LaravelPluginCore\Services;

use Illuminate\Support\Facades\File;

class PluginManager
{
    protected array $plugins = [];

    public function __construct(protected string $pluginPath)
    {
        $this->discoverPlugins();
    }

    protected function discoverPlugins(): void
    {
        if (! File::exists($this->pluginPath)) return;

        $dirs = File::directories($this->pluginPath);

        foreach ($dirs as $dir) {
            $manifestPath = $dir . DIRECTORY_SEPARATOR . 'plugin.json';
            if (! File::exists($manifestPath)) continue;

            try {
                $config = json_decode(File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                \Log::error("[PluginCore] Invalid manifest in {$dir}: {$e->getMessage()}");
                continue;
            }

            $config['path'] = $dir;

            // Register PSR-4 autoloader dynamically
            $ns = rtrim($config['namespace'] ?? 'Plugins\\' . basename($dir) . '\\', '\\');
            $srcPath = $dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;

            spl_autoload_register(function ($class) use ($ns, $srcPath) {
                if (str_starts_with($class, $ns)) {
                    $relative = substr($class, strlen($ns));
                    $file = $srcPath . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
                    if (is_file($file)) {
                        require_once $file;
                    }
                }
            });

            $this->plugins[$config['slug'] ?? basename($dir)] = $config;
        }
    }

    protected function validateManifest(array $config, string $dir)
    {
        if (empty($config['slug']) || empty($config['service_provider'])) {
            throw new \RuntimeException('Missing slug of service_provider');
        }
    }

    public function all(): array
    {
        return $this->plugins;
    }

    public function get(string $slug): ?array
    {
        return $this->plugins[$slug] ?? null;
    }

    public function enable(string $slug): bool
    {
        // Optional slug for now, to be able to toggle a flag in .json or DB.
        return false;
    }

    public function markAsBroken(string $slug, \Throwable $e): void
    {
        if (isset($this->plugins[$slug])) {
            $this->plugins[$slug]['broken'] = true;
            $this->plugins[$slug]['error'] = $e->getMessage();
        }
    }

    public function isBroken(string $slug): bool
    {
        return $this->plugins[$slug]['broken'] ?? false;
    }
}