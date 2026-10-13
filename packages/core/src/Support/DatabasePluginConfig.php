<?php

namespace AppCore\Core\Support;

use AppCore\Contracts\Exceptions\InvalidManifest;
use AppCore\Contracts\Manifest;
use AppCore\Contracts\PluginConfig;
use AppCore\Core\Models\PluginApp;

class DatabasePluginConfig implements PluginConfig
{
    /** @var array<string, array<string, mixed>> memo por request */
    private array $resolved = [];

    public function get(string $slug, string $key, mixed $default = null): mixed
    {
        $values = $this->resolved[$slug] ??= $this->load($slug);

        return array_key_exists($key, $values) && $values[$key] !== null ? $values[$key] : $default;
    }

    public function forget(string $slug): void
    {
        unset($this->resolved[$slug]);
    }

    /** @return array<string, mixed> */
    private function load(string $slug): array
    {
        $file = rtrim((string) config('plugin-core.apps_path'), '/').'/'.$slug.'/app.json';

        try {
            $defaults = Manifest::fromFile($file)->configDefaults();
        } catch (InvalidManifest) {
            $defaults = [];
        }

        $stored = PluginApp::where('slug', $slug)->value('config');
        $stored = is_string($stored) ? json_decode($stored, true) : $stored;

        return array_merge($defaults, is_array($stored) ? $stored : []);
    }
}
