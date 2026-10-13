<?php

namespace AppCore\Contracts\Registry;

use Illuminate\Contracts\Auth\Authenticatable;

interface WidgetRegistry
{
    public function add(WidgetDefinition $widget): void;

    /** @return array<int, WidgetDefinition> widgets de apps activas visibles para el usuario, ordenados */
    public function forUser(?Authenticatable $user): array;
}
