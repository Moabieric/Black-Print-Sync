<?php

namespace BlackPrint\Commerce\Sync\Contracts;

interface SnapshotPayloadRepositoryInterface
{
    /**
     * Store a payload.
     */
    public function save(
        string $snapshotUuid,
        array $payload
    ): void;

    /**
     * Retrieve a payload.
     */
    public function find(
        string $snapshotUuid
    ): ?array;

    /**
     * Delete a payload.
     */
    public function delete(
        string $snapshotUuid
    ): void;
}