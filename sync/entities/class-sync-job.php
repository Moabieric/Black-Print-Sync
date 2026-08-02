<?php

namespace BlackPrint\Commerce\Sync\Entities;

defined('ABSPATH') || exit;

/**
 * Immutable synchronization job.
 */
final class SyncJob
{
    public function __construct(
        private readonly string $uuid,
        private readonly string $supplier,
        private readonly string $resource,
        private readonly string $jobType,
        private readonly string $status,
        private readonly int $recordsProcessed = 0,
        private readonly ?string $startedAt = null,
        private readonly ?string $completedAt = null,
        private readonly ?string $errorMessage = null
    ) {
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function supplier(): string
    {
        return $this->supplier;
    }

    public function resource(): string
    {
        return $this->resource;
    }

    public function jobType(): string
    {
        return $this->jobType;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function recordsProcessed(): int
    {
        return $this->recordsProcessed;
    }

    public function startedAt(): ?string
    {
        return $this->startedAt;
    }

    public function completedAt(): ?string
    {
        return $this->completedAt;
    }

    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }
}