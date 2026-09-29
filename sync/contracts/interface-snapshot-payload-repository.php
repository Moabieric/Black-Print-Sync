<?php

namespace BlackPrint\Commerce\Sync\Contracts;

interface SnapshotPayloadRepositoryInterface
{
    /**
     * Store an immutable payload.
     */
    public function save(
        string $snapshotUuid,
        array $payload
    ): void;

    /**
     * Retrieve an immutable payload.
     */
    public function find(
        string $snapshotUuid
    ): ?array;
}