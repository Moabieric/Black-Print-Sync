<?php

namespace BlackPrint\Commerce\Sync\Repositories;

use BlackPrint\Commerce\Sync\Contracts\SnapshotRepositoryInterface;
use BlackPrint\Commerce\Sync\Entities\Snapshot;

defined('ABSPATH') || exit;

/**
 * Snapshot Repository.
 *
 * Persists and retrieves immutable synchronization snapshots.
 */
class SnapshotRepository implements SnapshotRepositoryInterface
{
    private \wpdb $db;

    public function __construct(
        \wpdb $db
    ) {
        $this->db = $db;
    }

    /**
     * Snapshot table name.
     */
    private function table(): string
    {
        return $this->db->prefix . 'bp_snapshots';
    }

    /**
     * Create a snapshot.
     */
    public function create(
        Snapshot $snapshot
    ): int {

        $this->db->insert(
            $this->table(),
            [
                'uuid'            => $snapshot->id(),
                'sync_job_uuid'   => $snapshot->jobId(),
                'supplier'        => $snapshot->supplier(),
                'resource'        => $snapshot->resource(),
                'type'            => $snapshot->type(),
                'sequence_number' => $snapshot->sequenceNumber(),
                'checksum'        => $snapshot->checksum(),
                'records_count'   => $snapshot->recordCount(),
                'metadata'        => wp_json_encode(
                    $snapshot->metadata()
                ),
                'created_at'      => $snapshot->createdAt(),
            ]
        );

        return (int) $this->db->insert_id;
    }

    /**
     * Find a snapshot by UUID.
     */
    public function find(
        string $uuid
    ): ?Snapshot {

        $row = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->table()} WHERE uuid = %s",
                $uuid
            ),
            ARRAY_A
        );

        if (! $row) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * Find all snapshots belonging to a sync job.
     *
     * @return Snapshot[]
     */
    public function findByJob(
        string $syncJobUuid
    ): array {

        $rows = $this->db->get_results(
            $this->db->prepare(
                "
                SELECT *
                FROM {$this->table()}
                WHERE sync_job_uuid = %s
                ORDER BY sequence_number ASC
                ",
                $syncJobUuid
            ),
            ARRAY_A
        );

        return array_map(
            fn(array $row): Snapshot => $this->hydrate($row),
            $rows
        );
    }

    /**
     * Hydrate a Snapshot entity from a database row.
     */
    private function hydrate(
        array $row
    ): Snapshot {

        $metadata = [];

        if (! empty($row['metadata'])) {

            $decoded = json_decode(
                $row['metadata'],
                true
            );

            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        return new Snapshot(
            id: $row['uuid'],
            jobId: $row['sync_job_uuid'],
            sequenceNumber: (int) $row['sequence_number'],
            supplier: $row['supplier'],
            resource: $row['resource'],
            type: $row['type'],
            checksum: $row['checksum'],
            recordCount: (int) $row['records_count'],
            metadata: $metadata,
            createdAt: $row['created_at']
        );
    }
}
