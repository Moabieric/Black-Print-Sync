<?php

namespace BlackPrint\Commerce\Sync\Kernel;

class SyncResult
{
    private bool $success;

    private int $fetched;

    private int $processed;

    private int $skipped;

    private int $failed;

    private float $duration;

    private ?string $snapshotId;

    private array $errors;

    private array $metadata;

    public function __construct(
        bool $success = true,
        int $fetched = 0,
        int $processed = 0,
        int $skipped = 0,
        int $failed = 0,
        float $duration = 0.0,
        ?string $snapshotId = null,
        array $errors = [],
        array $metadata = []
    ) {
        $this->success = $success;
        $this->fetched = $fetched;
        $this->processed = $processed;
        $this->skipped = $skipped;
        $this->failed = $failed;
        $this->duration = $duration;
        $this->snapshotId = $snapshotId;
        $this->errors = $errors;
        $this->metadata = $metadata;
    }

    public function success(): bool
    {
        return $this->success;
    }

    public function fetched(): int
    {
        return $this->fetched;
    }

    public function processed(): int
    {
        return $this->processed;
    }

    public function skipped(): int
    {
        return $this->skipped;
    }

    public function failed(): int
    {
        return $this->failed;
    }

    public function duration(): float
    {
        return $this->duration;
    }

    public function snapshotId(): ?string
    {
        return $this->snapshotId;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    public function toArray(): array
    {
        return [
            'success'     => $this->success,
            'fetched'     => $this->fetched,
            'processed'   => $this->processed,
            'skipped'     => $this->skipped,
            'failed'      => $this->failed,
            'duration'    => $this->duration,
            'snapshot_id' => $this->snapshotId,
            'errors'      => $this->errors,
            'metadata'    => $this->metadata,
        ];
    }
}