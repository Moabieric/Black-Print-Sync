<?php

namespace BlackPrint\Commerce\Sync\Repositories;

use BlackPrint\Commerce\Sync\Contracts\SnapshotRepositoryInterface;
use BlackPrint\Commerce\Sync\Entities\Snapshot;

defined('ABSPATH') || exit;

class SnapshotRepository implements SnapshotRepositoryInterface
{
    private \wpdb $db;

    public function __construct(
        \wpdb $db
    ) {
        $this->db = $db;
    }

    private function table(): string
    {
        return $this->db->prefix . 'bp_snapshots';
    }

    public function create(
        Snapshot $snapshot
    ): int {

        $this->db->insert(

            $this->table(),

            [

                'uuid'            => $snapshot->id(),

                'sync_job_id'     => $snapshot->syncJobId(),

                'sequence_number' => $snapshot->sequenceNumber(),

                'supplier'        => $snapshot->supplier(),

                'resource'        => $snapshot->resource(),

                'checksum'        => $snapshot->checksum(),

                'record_count'    => $snapshot->recordCount(),

                'metadata'        => wp_json_encode(
                    $snapshot->metadata()
                ),

                'created_at'      => $snapshot->createdAt(),

            ]

        );

        return (int) $this->db->insert_id;
    }

    public function find(
        string $uuid
    ): ?Snapshot {

        return null;
    }

    public function findByJob(
        int $syncJobId
    ): array {

        return [];
    }
}