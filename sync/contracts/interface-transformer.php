<?php

namespace BlackPrint\Commerce\Sync\Contracts;

interface TransformerInterface
{
    /**
     * Convert supplier payload into canonical format.
     */
    public function transform(array $payload): array;
}