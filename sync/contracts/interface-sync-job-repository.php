<?php

namespace BlackPrint\Commerce\Sync\Contracts;

use BlackPrint\Commerce\Sync\Entities\SyncJob;

interface SyncJobRepositoryInterface
{
    public function create(
        SyncJob $job
    ): int;

    public function find(
        string $uuid
    ): ?SyncJob;

    public function updateStatus(
        string $uuid,
        string $status
    ): void;

    public function markCompleted(
        string $uuid,
        int $recordsProcessed
    ): void;

    public function markFailed(
        string $uuid,
        string $message
    ): void;
}