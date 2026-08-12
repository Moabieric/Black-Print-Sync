<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Sync\DTO;

final class SupplierResponse
{
    public function __construct(

        private readonly array $payload,

        private readonly SupplierMetadata $metadata

    ) {
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function metadata(): SupplierMetadata
    {
        return $this->metadata;
    }

    public function recordCount(): int
    {
        return $this->metadata->recordCount();
    }
}
