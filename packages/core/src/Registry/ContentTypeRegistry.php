<?php

namespace AppCore\Core\Registry;

use AppCore\Contracts\PluginState;
use AppCore\Contracts\Registry\ContentType;
use AppCore\Contracts\Registry\ContentTypeRegistry as ContentTypeRegistryContract;
use AppCore\Core\Models\ContentTypeRecord;

/**
 * Fuente de verdad: tabla content_types (la siembra el PluginManager desde app.json
 * al activar). Lo registrado en runtime con contentType() completa/sobrescribe.
 */
class ContentTypeRegistry implements ContentTypeRegistryContract
{
    /** @var array<string, ContentType> */
    private array $runtime = [];

    public function __construct(
        private readonly PluginState $state,
    ) {}

    public function register(ContentType $type): void
    {
        $this->runtime[$type->slug] = $type;
    }

    public function all(): array
    {
        $types = ContentTypeRecord::orderBy('slug')->get()
            ->mapWithKeys(fn (ContentTypeRecord $r) => [
                $r->slug => new ContentType($r->slug, $r->app_slug, $r->label, $r->config ?? []),
            ])
            ->all();

        return array_filter(
            array_merge($types, $this->runtime),
            fn (ContentType $t) => $this->state->isActive($t->app),
        );
    }

    public function find(string $slug): ?ContentType
    {
        return $this->all()[$slug] ?? null;
    }
}
