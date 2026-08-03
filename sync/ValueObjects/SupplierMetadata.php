<?php

declare(strict_types=1);

namespace BlackPrint\Sync\DTO;

final class SupplierMetadata
{
    public function __construct(

        private readonly string $supplier,

        private readonly string $resource,

        private readonly int $recordCount,

        private readonly string $checksum,

        private readonly int $payloadSize,

        private readonly int $durationMs,

        private readonly string $requestedAt,

        private readonly ?string $etag = null,

        private readonly ?string $cursor = null,

        private readonly array $extra = []

    ) {
    }

    public function supplier(): string
    {
        return $this->supplier;
    }

    public function resource(): string
    {
        return $this->resource;
    }

    public function recordCount(): int
    {
        return $this->recordCount;
    }

    public function checksum(): string
    {
        return $this->checksum;
    }

    public function payloadSize(): int
    {
        return $this->payloadSize;
    }

    public function durationMs(): int
    {
        return $this->durationMs;
    }

    public function requestedAt(): string
    {
        return $this->requestedAt;
    }

    public function etag(): ?string
    {
        return $this->etag;
    }

    public function cursor(): ?string
    {
        return $this->cursor;
    }

    public function extra(): array
    {
        return $this->extra;
    }
}