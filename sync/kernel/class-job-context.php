<?php

namespace BlackPrint\Commerce\Sync\Kernel;

class JobContext
{
    private string $jobId;

    private string $jobName;

    private string $supplier;

    private int $attempt;

    private ?string $snapshotId;

    private string $startedAt;

    private array $metadata;

    public function __construct(
        string $jobId,
        string $jobName,
        string $supplier,
        int $attempt = 1,
        ?string $snapshotId = null,
        ?string $startedAt = null,
        array $metadata = []
    ) {
        $this->jobId = $jobId;
        $this->jobName = $jobName;
        $this->supplier = $supplier;
        $this->attempt = $attempt;
        $this->snapshotId = $snapshotId;
        $this->startedAt = $startedAt ?? gmdate('Y-m-d H:i:s');
        $this->metadata = $metadata;
    }

    public function jobId(): string
    {
        return $this->jobId;
    }

    public function jobName(): string
    {
        return $this->jobName;
    }

    public function supplier(): string
    {
        return $this->supplier;
    }

    public function attempt(): int
    {
        return $this->attempt;
    }

    public function snapshotId(): ?string
    {
        return $this->snapshotId;
    }

    public function startedAt(): string
    {
        return $this->startedAt;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function meta(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    public function toArray(): array
    {
        return [
            'job_id'      => $this->jobId,
            'job_name'    => $this->jobName,
            'supplier'    => $this->supplier,
            'attempt'     => $this->attempt,
            'snapshot_id' => $this->snapshotId,
            'started_at'  => $this->startedAt,
            'metadata'    => $this->metadata,
        ];
    }
}