<?php

namespace AppCore\Contracts;

use AppCore\Contracts\Exceptions\InvalidManifest;

final class Manifest
{
    public const array CONFIG_TYPES = ['string', 'number', 'boolean'];

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

        foreach ($data['content_types'] ?? [] as $type => $definition) {
            if (! is_string($type) || ! str_starts_with($type, $data['slug'].'.') || ! is_array($definition)) {
                throw new InvalidManifest(
                    "app.json: el tipo de contenido '{$type}' debe empezar por '{$data['slug']}.' y ser un objeto"
                );
            }
        }

        foreach ($data['config'] ?? [] as $key => $field) {
            if (! is_array($field) || ! in_array($field['type'] ?? null, self::CONFIG_TYPES, true)) {
                throw new InvalidManifest(
                    "app.json: config '{$key}' debe tener type ".implode('|', self::CONFIG_TYPES)
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

    /** @return array<string, array<string, mixed>> slug del tipo => definición (label, ...) */
    public function contentTypes(): array
    {
        return $this->data['content_types'] ?? [];
    }

    /** @return array<string, mixed> clave => default */
    public function configDefaults(): array
    {
        return array_map(fn (array $field) => $field['default'] ?? null, $this->configSchema());
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
