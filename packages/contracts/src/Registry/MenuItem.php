<?php

namespace AppCore\Contracts\Registry;

final class MenuItem
{
    public function __construct(
        public readonly string $app,
        public readonly string $label,
        public readonly string $route,
        public readonly ?string $permission = null,
        public readonly ?string $icon = null,
        public readonly int $order = 100,
    ) {}
}
