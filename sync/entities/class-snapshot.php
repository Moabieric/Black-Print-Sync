<?php

namespace BlackPrint\Commerce\Sync\Entities;

defined('ABSPATH') || exit;

/**
 * Immutable synchronization snapshot metadata.
 *
 * Raw payload data is stored separately by the
 * SnapshotPayloadRepository.
 */
final class Snapshot
{
    public function __construct(
        private readonly string $id,
        private readonly string $jobId,
        private readonly int $sequenceNumber,
        private readonly string $supplier,
        private readonly string $resource,
        private readonly string $type,
        private readonly string $checksum,
        private readonly int $recordCount,
        private readonly array $metadata = [],
        private readonly ?string $createdAt = null
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    /**
     * SyncJob UUID.
     */
    public function jobId(): string
    {
        return $this->jobId;
    }

    public function sequenceNumber(): int
    {
        return $this->sequenceNumber;
    }

    public function supplier(): string
    {
        return $this->supplier;
    }

    public function resource(): string
    {
        return $this->resource;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function checksum(): string
    {
        return $this->checksum;
    }

    public function recordCount(): int
    {
        return $this->recordCount;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function createdAt(): string
    {
        return $this->createdAt ?? gmdate('Y-m-d H:i:s');
    }

    public function isEmpty(): bool
    {
        return $this->recordCount === 0;
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'job_id'          => $this->jobId,
            'sequence_number' => $this->sequenceNumber,
            'supplier'        => $this->supplier,
            'resource'        => $this->resource,
            'type'             => $this->type,
            'checksum'        => $this->checksum,
            'record_count'    => $this->recordCount,
            'metadata'        => $this->metadata,
            'created_at'      => $this->createdAt(),
        ];
    }
}