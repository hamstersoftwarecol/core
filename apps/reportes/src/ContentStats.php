<?php

namespace AppCore\Reportes;

use AppCore\Contracts\Registry\ContentType;
use AppCore\Contracts\Registry\ContentTypeRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cuenta registros de cada tipo de contenido de apps activas. Solo conoce el
 * contrato: un tipo que declara `table` en su config es contable.
 */
class ContentStats
{
    public function __construct(
        private readonly ContentTypeRegistry $types,
    ) {}

    /** @return array<int, array{slug: string, app: string, label: string, count: ?int}> */
    public function rows(bool $showEmpty = true): array
    {
        $rows = [];

        foreach ($this->types->all() as $type) {
            $count = $this->count($type);
            if (! $showEmpty && ! $count) {
                continue;
            }
            $rows[] = ['slug' => $type->slug, 'app' => $type->app, 'label' => $type->label, 'count' => $count];
        }

        return $rows;
    }

    public function total(): int
    {
        return array_sum(array_map(fn ($r) => $r['count'] ?? 0, $this->rows()));
    }

    private function count(ContentType $type): ?int
    {
        $table = $type->config['table'] ?? null;

        // el nombre viaja a SQL: solo identificadores simples y tablas existentes
        if (! is_string($table) || ! preg_match('/^[a-z_][a-z0-9_]*$/', $table) || ! Schema::hasTable($table)) {
            return null;
        }

        return DB::table($table)->count();
    }
}
