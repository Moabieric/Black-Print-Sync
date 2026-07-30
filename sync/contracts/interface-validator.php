<?php

namespace BlackPrint\Commerce\Sync\Contracts;

interface ValidatorInterface
{
    /**
     * Validate supplier payload.
     */
    public function validate(array $payload): bool;

    /**
     * Validation errors.
     */
    public function errors(): array;
}