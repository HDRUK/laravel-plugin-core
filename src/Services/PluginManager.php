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
        if (!File::exists($this->pluginPath)) {
            return;
        }

        $dirs = File::directories($this->pluginPath);

        foreach ($dirs as $dir) {
            $manifest = $dir . DIRECTORY_SEPARATOR . 'plugin.json';
            if (File::exists($manifest)) {

                try {
                    $config = json_decode(File::get($manifest), true, 512, JSON_THROW_ON_ERROR);
                    $this->validateManifest($config, $dir);

                    $config['path'] = $dir;
                    // normalise activation rules
                    $config['activation'] = $config['activation'] ?? [];
                    $config['middleware'] = $config['middleware'] ?? [];

                    $this->plugins[$config['slug']] = $config;

                } catch (\Throwable $e) {
                    \Log::error("PluginCore - Invalid plugin manifest in {$dir}: {$e->getMessage()}");
                    continue;
                }
            }
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
}