<?php

namespace AppCore\Contracts\Registry;

use Illuminate\Contracts\Auth\Authenticatable;

interface MenuRegistry
{
    public function add(MenuItem $item): void;

    /** @return array<int, MenuItem> entradas de apps activas visibles para el usuario, ordenadas */
    public function forUser(?Authenticatable $user): array;
}
