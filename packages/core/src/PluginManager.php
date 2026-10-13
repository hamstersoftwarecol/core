<?php

namespace AppCore\Core;

use AppCore\Contracts\Exceptions\InvalidManifest;
use AppCore\Contracts\Manifest;
use AppCore\Core\Exceptions\AppNotFound;
use AppCore\Core\Models\PluginApp;
use AppCore\Core\Support\ActiveAppsCache;
use Illuminate\Support\Collection;

class PluginManager
{
    public function __construct(
        private readonly ActiveAppsCache $cache,
    ) {
    }

    /** @return Collection<string, Manifest> */
    public function discover(): Collection
    {
        $appsPath = rtrim((string) config('plugin-core.apps_path'), '/');
        $found = collect();

        if (! is_dir($appsPath)) {
            return $found;
        }

        foreach (glob($appsPath.'/*/app.json') ?: [] as $file) {
            try {
                $manifest = Manifest::fromFile($file);
            } catch (InvalidManifest $e) {
                report($e); // manifest roto: no rompe el listado; se ignora
                continue;
            }
            $found->put($manifest->slug(), $manifest);
        }

        return $found;
    }

    public function sync(): void
    {
        foreach ($this->discover() as $manifest) {
            PluginApp::updateOrCreate(
                ['slug' => $manifest->slug()],
                ['name' => $manifest->name(), 'version' => $manifest->version()]
            );
        }
    }

    public function manifestFor(string $slug): Manifest
    {
        // El slug viaja hacia un path de archivos: validar antes de tocar el disco.
        if (! preg_match('/^[a-z0-9][a-z0-9-]*$/', $slug)) {
            throw new AppNotFound("App \"{$slug}\" no encontrada");
        }

        $file = rtrim((string) config('plugin-core.apps_path'), '/').'/'.$slug.'/app.json';

        if (! is_file($file)) {
            throw new AppNotFound("App \"{$slug}\" no encontrada");
        }

        return Manifest::fromFile($file);
    }
}
