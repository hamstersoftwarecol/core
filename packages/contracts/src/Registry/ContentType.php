<?php

namespace AppCore\Contracts\Registry;

final class ContentType
{
    /** @param array<string, mixed> $config */
    public function __construct(
        public readonly string $slug,
        public readonly string $app,
        public readonly string $label,
        public readonly array $config = [],
    ) {}
}
