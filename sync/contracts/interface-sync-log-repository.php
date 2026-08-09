<?php

namespace BlackPrint\Commerce\Sync\Contracts;

use BlackPrint\Commerce\Sync\Entities\SyncLog;

interface SyncLogRepositoryInterface
{
    public function create(
        SyncLog $log
    ): int;

    /**
     * @return SyncLog[]
     */
    public function findByJob(
        string $syncJobUuid
    ): array;

    /**
     * @return SyncLog[]
     */
    public function findBySnapshot(
        string $snapshotUuid
    ): array;
}