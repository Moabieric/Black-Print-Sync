<?php

namespace BlackPrint\Commerce\Sync\Storage;

class PayloadRepository
{
    /**
     * Store payload.
     */
    public function store(
        string $snapshotId,
        array $payload
    ): bool {

        // Database implementation comes later.

        return true;
    }

    /**
     * Retrieve payload.
     */
    public function retrieve(
        string $snapshotId
    ): array {

        // Database implementation comes later.

        return [];
    }

    /**
     * Check whether payload exists.
     */
    public function exists(
        string $snapshotId
    ): bool {

        // Database implementation comes later.

        return false;
    }

    /**
     * Compress payload before storage.
     */
    public function compress(
        array $payload
    ): string {

        return gzcompress(
            wp_json_encode($payload)
        );
    }

    /**
     * Decompress payload after retrieval.
     */
    public function decompress(
        string $payload
    ): array {

        $json = gzuncompress($payload);

        return json_decode(
            $json,
            true
        ) ?: [];
    }

    /**
     * Soft delete payload.
     */
    public function delete(
        string $snapshotId
    ): bool {

        // Snapshots are immutable.
        // Soft delete only.

        return false;
    }
}