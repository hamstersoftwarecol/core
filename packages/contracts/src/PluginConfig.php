<?php

namespace AppCore\Contracts;

interface PluginConfig
{
    /** Valor guardado en /admin/apps/{slug}/config, o el default del manifest. */
    public function get(string $slug, string $key, mixed $default = null): mixed;
}
