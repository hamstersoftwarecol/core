<?php

namespace AppCore\Core\Registry;

use AppCore\Contracts\PluginState;
use AppCore\Contracts\Registry\MenuItem;
use AppCore\Contracts\Registry\MenuRegistry as MenuRegistryContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Route;

class MenuRegistry implements MenuRegistryContract
{
    /** @var array<string, MenuItem> */
    private array $items = [];

    public function __construct(
        private readonly PluginState $state,
    ) {}

    public function add(MenuItem $item): void
    {
        $this->items[$item->route] = $item; // re-boot del provider no duplica entradas
    }

    public function forUser(?Authenticatable $user): array
    {
        if (! $user) {
            return [];
        }

        return collect($this->items)
            ->filter(fn (MenuItem $i) => $this->state->isActive($i->app)
                && Route::has($i->route)
                && (! $i->permission || $user->can($i->permission)))
            ->sortBy([['order', 'asc'], ['label', 'asc']])
            ->values()
            ->all();
    }
}
