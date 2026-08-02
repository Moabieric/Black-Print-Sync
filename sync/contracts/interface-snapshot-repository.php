<?php

namespace BlackPrint\Commerce\Sync\Contracts;

use BlackPrint\Commerce\Sync\Entities\Snapshot;

interface SnapshotRepositoryInterface
{
    public function create(
        Snapshot $snapshot
    ): int;

    public function find(
        string $uuid
    ): ?Snapshot;

    /**
     * @return Snapshot[]
     */
    public function findByJob(
        int $syncJobId
    ): array;
}