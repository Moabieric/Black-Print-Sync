<?php

namespace BlackPrint\Commerce\Sync\Storage;

use BlackPrint\Commerce\Sync\Entities\Snapshot;

class SnapshotRepository
{
    /**
     * Save snapshot.
     */
    public function save(
        Snapshot $snapshot
    ): bool {

        // Phase 2.2:
        // Database implementation goes here.

        return true;
    }

    /**
     * Find snapshot by ID.
     */
    public function find(
        string $id
    ): ?Snapshot {

        // TODO

        return null;
    }

    /**
     * Get latest snapshot.
     */
    public function latest(
        string $supplier,
        string $resource
    ): ?Snapshot {

        // TODO

        return null;
    }

    /**
     * Check existence.
     */
    public function exists(
        string $checksum
    ): bool {

        // TODO

        return false;
    }

    /**
     * Compare snapshots.
     */
    public function compare(
        Snapshot $left,
        Snapshot $right
    ): array {

        return [

            'same' => (
                $left->checksum() ===
                $right->checksum()
            ),

            'left_checksum' => $left->checksum(),

            'right_checksum' => $right->checksum(),

            'left_records' => $left->recordCount(),

            'right_records' => $right->recordCount(),

        ];
    }

    /**
     * Get payload.
     */
    public function payload(
        string $snapshotId
    ): array {

        // TODO

        return [];
    }

    /**
     * Delete snapshot.
     */
    public function delete(
        string $snapshotId
    ): bool {

        // Snapshots should be immutable.
        // Soft delete only.

        return false;
    }
}