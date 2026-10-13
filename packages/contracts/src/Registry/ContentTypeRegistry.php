<?php

namespace AppCore\Contracts\Registry;

interface ContentTypeRegistry
{
    public function register(ContentType $type): void;

    /** @return array<string, ContentType> tipos de apps activas, indexados por slug */
    public function all(): array;

    public function find(string $slug): ?ContentType;
}
