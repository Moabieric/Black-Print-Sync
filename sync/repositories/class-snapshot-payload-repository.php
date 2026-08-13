<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Sync\Repositories;

use BlackPrint\Commerce\Sync\Contracts\SnapshotPayloadRepositoryInterface;

defined('ABSPATH') || exit;

/**
 * Snapshot Payload Repository.
 *
 * Stores and retrieves immutable raw supplier payloads
 * associated with synchronization snapshots.
 */
class SnapshotPayloadRepository implements SnapshotPayloadRepositoryInterface
{
    public function __construct(
        private \wpdb $db
    ) {
    }

    /**
     * Database table.
     */
    private function table(): string
    {
        return $this->db->prefix . 'bp_snapshot_payloads';
    }

    /**
     * Store an immutable snapshot payload.
     *
     * Payloads are JSON encoded and gzip compressed
     * before being persisted.
     *
     * An existing payload must never be overwritten.
     *
     * @throws \RuntimeException When encoding, compression,
     *                           or persistence fails.
     */
    public function save(
        string $snapshotUuid,
        array $payload
    ): void {

        $json = wp_json_encode(
            $payload
        );

        if ($json === false) {
            throw new \RuntimeException(
                'Failed to encode snapshot payload as JSON.'
            );
        }

        $compressed = gzencode(
            $json
        );

        if ($compressed === false) {
            throw new \RuntimeException(
                'Failed to gzip snapshot payload.'
            );
        }

        $result = $this->db->insert(
            $this->table(),
            [
                'snapshot_uuid' => $snapshotUuid,
                'payload'       => $compressed,
                'compression'   => 'gzip',
                'payload_size'  => strlen($compressed),
                'created_at'    => gmdate('Y-m-d H:i:s'),
            ],
            [
                '%s',
                '%s',
                '%s',
                '%d',
                '%s',
            ]
        );

        if ($result === false) {
            throw new \RuntimeException(
                sprintf(
                    'Failed to persist immutable snapshot payload: %s',
                    $this->db->last_error !== ''
                        ? $this->db->last_error
                        : 'Unknown database error.'
                )
            );
        }
    }

    /**
     * Retrieve and decompress a snapshot payload.
     */
    public function find(
        string $snapshotUuid
    ): ?array {

        $row = $this->db->get_row(
            $this->db->prepare(
                "
                SELECT *
                FROM {$this->table()}
                WHERE snapshot_uuid = %s
                ",
                $snapshotUuid
            ),
            ARRAY_A
        );

        if (! is_array($row)) {
            return null;
        }

        $payload = (string) $row['payload'];

        if ($row['compression'] === 'gzip') {

            $payload = gzdecode(
                $payload
            );

            if ($payload === false) {
                throw new \RuntimeException(
                    'Failed to decompress snapshot payload.'
                );
            }
        }

        $decoded = json_decode(
            $payload,
            true
        );

        if (! is_array($decoded)) {
            throw new \RuntimeException(
                'Snapshot payload contains invalid JSON.'
            );
        }

        return $decoded;
    }

    /**
     * Delete a snapshot payload.
     *
     * Payload deletion is intentionally retained only for
     * administrative or recovery operations outside normal
     * ingestion. Normal synchronization never overwrites or
     * deletes an immutable payload.
     */
    public function delete(
        string $snapshotUuid
    ): void {

        $result = $this->db->delete(
            $this->table(),
            [
                'snapshot_uuid' => $snapshotUuid,
            ],
            [
                '%s',
            ]
        );

        if ($result === false) {
            throw new \RuntimeException(
                sprintf(
                    'Failed to delete snapshot payload: %s',
                    $this->db->last_error !== ''
                        ? $this->db->last_error
                        : 'Unknown database error.'
                )
            );
        }
    }
}