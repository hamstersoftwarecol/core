<?php

namespace AppCore\Core\Exceptions;

use RuntimeException;

class ActivationFailed extends RuntimeException
{
    public function __construct(
        string $slug,
        string $reason,
        public readonly ?\Throwable $previous = null,
    ) {
        parent::__construct("No se pudo activar \"{$slug}\": {$reason}", 0, $previous);
    }
}
