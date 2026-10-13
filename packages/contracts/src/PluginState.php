<?php

namespace AppCore\Contracts;

interface PluginState
{
    public function isActive(string $slug): bool;

    public function accessPermissionFor(string $slug): ?string;
}
