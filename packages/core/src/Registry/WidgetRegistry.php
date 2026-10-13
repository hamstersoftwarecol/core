<?php

namespace AppCore\Core\Registry;

use AppCore\Contracts\PluginState;
use AppCore\Contracts\Registry\WidgetDefinition;
use AppCore\Contracts\Registry\WidgetRegistry as WidgetRegistryContract;
use Illuminate\Contracts\Auth\Authenticatable;

class WidgetRegistry implements WidgetRegistryContract
{
    /** @var array<string, WidgetDefinition> */
    private array $widgets = [];

    public function __construct(
        private readonly PluginState $state,
    ) {}

    public function add(WidgetDefinition $widget): void
    {
        $this->widgets[$widget->component] = $widget;
    }

    public function forUser(?Authenticatable $user): array
    {
        if (! $user) {
            return [];
        }

        return collect($this->widgets)
            ->filter(fn (WidgetDefinition $w) => $this->state->isActive($w->app)
                && (! $w->permission || $user->can($w->permission)))
            ->sortBy('order')
            ->values()
            ->all();
    }
}
