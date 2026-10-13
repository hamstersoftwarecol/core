<?php

namespace AppCore\Contracts;

use AppCore\Contracts\Exceptions\InvalidManifest;

final class Manifest
{
    private function __construct(
        private readonly array $data,
        private readonly string $sourceDir,
    ) {}

    public static function fromFile(string $file): self
    {
        if (! is_file($file)) {
            throw new InvalidManifest("No existe el manifiesto: {$file}");
        }

        $decoded = json_decode((string) file_get_contents($file), true);
        if (! is_array($decoded)) {
            throw new InvalidManifest("JSON inválido en {$file}: ".json_last_error_msg());
        }

        return self::fromArray($decoded, dirname($file));
    }

    public static function fromArray(array $data, string $sourceDir): self
    {
        foreach (['slug', 'name', 'version'] as $key) {
            if (empty($data[$key]) || ! is_string($data[$key])) {
                throw new InvalidManifest("app.json: falta el campo obligatorio '{$key}'");
            }
        }

        if (! preg_match('/^[a-z0-9][a-z0-9-]*$/', $data['slug'])) {
            throw new InvalidManifest(
                "app.json: slug inválido '{$data['slug']}' (solo minúsculas, números y guiones)"
            );
        }

        $access = $data['access'] ?? $data['slug'].'.access';
        $data['permissions'] = array_values(array_unique(array_merge($data['permissions'] ?? [], [$access])));

        foreach ($data['permissions'] as $permission) {
            if (! is_string($permission) || ! str_starts_with($permission, $data['slug'].'.')) {
                throw new InvalidManifest(
                    "app.json: el permiso '{$permission}' debe empezar por '{$data['slug']}.'"
                );
            }
        }

        return new self($data, rtrim($sourceDir, '/'));
    }

    public function slug(): string
    {
        return $this->data['slug'];
    }

    public function name(): string
    {
        return $this->data['name'];
    }

    public function version(): string
    {
        return $this->data['version'];
    }

    /** @return array<int, string> */
    public function permissions(): array
    {
        return $this->data['permissions'];
    }

    public function accessPermission(): string
    {
        return $this->data['access'] ?? $this->slug().'.access';
    }

    /** @return array<int, string> */
    public function requiredApps(): array
    {
        return array_values($this->data['requires']['apps'] ?? []);
    }

    public function phpConstraint(): ?string
    {
        return $this->data['requires']['php'] ?? null;
    }

    public function laravelConstraint(): ?string
    {
        return $this->data['requires']['laravel'] ?? null;
    }

    /** @return array<string, mixed> */
    public function configSchema(): array
    {
        return $this->data['config'] ?? [];
    }

    public function seeder(): ?string
    {
        return $this->data['seeder'] ?? null;
    }

    public function sourceDir(): string
    {
        return $this->sourceDir;
    }

    public function migrationsPath(): string
    {
        return $this->sourceDir.'/'.($this->data['migrations'] ?? 'database/migrations');
    }
}
