<?php

namespace BlackPrint\Commerce\Sync\Entities;

defined('ABSPATH') || exit;

/**
 * Immutable synchronization log entry.
 */
final class SyncLog
{
    public function __construct(
        private readonly string $syncJobUuid,
        private readonly ?string $snapshotUuid,
        private readonly string $level,
        private readonly string $component,
        private readonly string $code,
        private readonly string $message,
        private readonly array $context = [],
        private readonly ?string $createdAt = null
    ) {
    }

    public function syncJobUuid(): string
    {
        return $this->syncJobUuid;
    }

    public function snapshotUuid(): ?string
    {
        return $this->snapshotUuid;
    }

    public function level(): string
    {
        return $this->level;
    }

    public function component(): string
    {
        return $this->component;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function context(): array
    {
        return $this->context;
    }

    public function createdAt(): string
    {
        return $this->createdAt ?? gmdate('Y-m-d H:i:s');
    }

    public function toArray(): array
    {
        return [
            'sync_job_uuid' => $this->syncJobUuid,
            'snapshot_uuid' => $this->snapshotUuid,
            'level'         => $this->level,
            'component'     => $this->component,
            'code'          => $this->code,
            'message'       => $this->message,
            'context'       => $this->context,
            'created_at'    => $this->createdAt(),
        ];
    }
}