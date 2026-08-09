<?php

namespace BlackPrint\Commerce\Sync\Repositories;

use BlackPrint\Commerce\Sync\Contracts\SnapshotPayloadRepositoryInterface;

defined('ABSPATH') || exit;

/**
 * Snapshot Payload Repository.
 *
 * Stores and retrieves raw supplier payloads associated
 * with immutable synchronization snapshots.
 */
class SnapshotPayloadRepository implements SnapshotPayloadRepositoryInterface
{
    private \wpdb $db;

    public function __construct(
        \wpdb $db
    ) {
        $this->db = $db;
    }

    /**
     * Database table.
     */
    private function table(): string
    {
        return $this->db->prefix . 'bp_snapshot_payloads';
    }

    /**
     * Store a snapshot payload.
     *
     * Payloads are JSON encoded and gzip compressed
     * before being persisted.
     */
    public function save(
        string $snapshotUuid,
        array $payload
    ): void {

        $json = wp_json_encode($payload);

        if ($json === false) {
            throw new \RuntimeException(
                'Failed to encode snapshot payload as JSON.'
            );
        }

        $compressed = gzencode($json);

        if ($compressed === false) {
            throw new \RuntimeException(
                'Failed to gzip snapshot payload.'
            );
        }

        $this->db->replace(
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

        if (! $row) {
            return null;
        }

        $payload = $row['payload'];

        if ($row['compression'] === 'gzip') {

            $payload = gzdecode($payload);

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
     */
    public function delete(
        string $snapshotUuid
    ): void {

        $this->db->delete(
            $this->table(),
            [
                'snapshot_uuid' => $snapshotUuid,
            ],
            [
                '%s',
            ]
        );
    }
}