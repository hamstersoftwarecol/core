<?php

namespace AppCore\Contracts\Registry;

final class WidgetDefinition
{
    /** @param class-string $component componente Livewire */
    public function __construct(
        public readonly string $app,
        public readonly string $component,
        public readonly ?string $permission = null,
        public readonly int $order = 100,
    ) {}
}
