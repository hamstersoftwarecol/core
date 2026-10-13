<?php

namespace AppCore\Core\Support;

use AppCore\Contracts\Exceptions\InvalidManifest;
use AppCore\Contracts\Manifest;
use AppCore\Contracts\PluginState;
use AppCore\Core\Models\PluginApp;
use Throwable;

class ActiveAppsCache implements PluginState
{
    public function __construct(
        private readonly string $cacheFile,
    ) {}

    public function isActive(string $slug): bool
    {
        return array_key_exists($slug, $this->read());
    }

    public function accessPermissionFor(string $slug): ?string
    {
        return $this->read()[$slug] ?? null;
    }

    /** @return array<int, string> slugs activos */
    public function rebuild(): array
    {
        $map = [];
        $appsPath = rtrim((string) config('plugin-core.apps_path'), '/');

        foreach (PluginApp::where('status', PluginApp::STATUS_ACTIVE)->pluck('slug') as $slug) {
            try {
                $manifest = Manifest::fromFile($appsPath.'/'.$slug.'/app.json');
            } catch (InvalidManifest) {
                continue; // app activa sin manifest válido: no se cachea; apps:verify (Fase 2) lo reporta
            }
            $map[$slug] = $manifest->accessPermission();
        }

        $dir = dirname($this->cacheFile);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $tmp = $this->cacheFile.'.'.uniqid('', true).'.tmp';
        file_put_contents($tmp, "<?php\n\nreturn ".var_export($map, true).";\n");
        rename($tmp, $this->cacheFile);

        return array_keys($map);
    }

    public function cacheFile(): string
    {
        return $this->cacheFile;
    }

    /** @return array<string, string> slug => permiso de acceso */
    private function read(): array
    {
        try {
            $data = is_file($this->cacheFile) ? require $this->cacheFile : [];
        } catch (Throwable) {
            return [];
        }

        return is_array($data) ? $data : [];
    }
}
