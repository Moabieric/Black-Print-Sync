<?php

namespace BlackPrint\Commerce\Sync\Entities;

final class Snapshot
{
    private string $id;

    private string $jobId;

    private int $sequenceNumber;

    private string $supplier;

    private string $resource;

    private string $type;

    private string $checksum;

    private int $recordCount;

    private array $metadata;

    private string $createdAt;

    public function __construct(
        string $id,
        string $jobId,
        int $sequenceNumber,
        string $supplier,
        string $resource,
        string $type,
        string $checksum,
        int $recordCount,
        array $metadata = [],
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->jobId = $jobId;
        $this->sequenceNumber = $sequenceNumber;
        $this->supplier = $supplier;
        $this->resource = $resource;
        $this->type = $type;
        $this->checksum = $checksum;
        $this->recordCount = $recordCount;
        $this->metadata = $metadata;
        $this->createdAt = $createdAt ?? gmdate('Y-m-d H:i:s');
    }



    public function id(): string
    {
        return $this->id;
    }

    public function jobId(): string
    {
        return $this->jobId;
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

    public function payload(): array
    {
        return $this->payload;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function createdAt(): string
    {
        return $this->createdAt;
    }

    public function hasPayload(): bool
    {
        return ! empty($this->payload);
    }

    public function isEmpty(): bool
    {
        return $this->recordCount === 0;
    }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'job_id'       => $this->jobId,
            'supplier'     => $this->supplier,
            'resource'     => $this->resource,
            'type'         => $this->type,
            'checksum'     => $this->checksum,
            'record_count' => $this->recordCount,
            'payload'      => $this->payload,
            'metadata'     => $this->metadata,
            'created_at'   => $this->createdAt,
        ];
    }
}