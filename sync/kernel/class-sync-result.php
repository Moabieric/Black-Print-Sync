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

private array $errors;

private array $metadata;

    public function __construct(
        bool $success = true,
        int $fetched = 0,
        int $processed = 0,
        int $skipped = 0,
        int $failed = 0,
        float $duration = 0.0,
        array $errors = [],
        array $metadata = []
    ) {
        int $updated = 0,
        int $unchanged = 0,
        int $failed = 0,
        float $duration = 0.0,
        array $errors = [],
        array $metadata = []
    ) {
        $this->success = $success;
        $this->fetched = $fetched;
        $this->processed = $processed;
        $this->skipped = $skipped;
        $this->failed = $failed;
        $this->updated = $updated;
        $this->unchanged = $unchanged;
        $this->failed = $failed;
        $this->duration = $duration;
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

    public function created(): int
    {
        return $this->created;
    }

    public function updated(): int
    {
        return $this->updated;
    }

    public function unchanged(): int
    {
        return $this->unchanged;
    }

    public function failed(): int
    {
        return $this->failed;
    }

    public function duration(): float
    {
        return $this->duration;
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
            'success'   => $this->success,
            'fetched'   => $this->fetched,
            'created'   => $this->created,
            'updated'   => $this->updated,
            'unchanged' => $this->unchanged,
            'failed'    => $this->failed,
            'duration'  => $this->duration,
            'errors'    => $this->errors,
            'metadata'  => $this->metadata,
        ];
    }
}