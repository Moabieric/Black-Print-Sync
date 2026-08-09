<?php

namespace BlackPrint\Commerce\Sync\Repositories;

use BlackPrint\Commerce\Sync\Contracts\SyncLogRepositoryInterface;
use BlackPrint\Commerce\Sync\Entities\SyncLog;

defined('ABSPATH') || exit;

/**
 * Sync Log Repository.
 *
 * Persists structured synchronization events.
 */
class SyncLogRepository implements SyncLogRepositoryInterface
{
    private \wpdb $db;

    public function __construct(
        \wpdb $db
    ) {
        $this->db = $db;
    }

    /**
     * Sync log table name.
     */
    private function table(): string
    {
        return $this->db->prefix . 'bp_sync_logs';
    }

    /**
     * Create a sync log entry.
     */
    public function create(
        SyncLog $log
    ): int {

        $this->db->insert(
            $this->table(),
            [
                'sync_job_uuid' => $log->syncJobUuid(),
                'snapshot_uuid' => $log->snapshotUuid(),
                'level'         => $log->level(),
                'component'     => $log->component(),
                'code'          => $log->code(),
                'message'       => $log->message(),
                'context'       => wp_json_encode(
                    $log->context()
                ),
                'created_at'    => $log->createdAt(),
            ]
        );

        return (int) $this->db->insert_id;
    }

    /**
     * Find logs belonging to a sync job.
     *
     * @return SyncLog[]
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
            ORDER BY created_at ASC, id ASC
            ",
            $syncJobUuid
        ),
        ARRAY_A
    );

    return array_map(
        fn(array $row): SyncLog => $this->hydrate($row),
        $rows
    );
}

public function findBySnapshot(
    string $snapshotUuid
): array {

    $rows = $this->db->get_results(
        $this->db->prepare(
            "
            SELECT *
            FROM {$this->table()}
            WHERE snapshot_uuid = %s
            ORDER BY created_at ASC, id ASC
            ",
            $snapshotUuid
        ),
        ARRAY_A
    );

    return array_map(
        fn(array $row): SyncLog => $this->hydrate($row),
        $rows
    );
}

private function hydrate(
    array $row
): SyncLog {

    $context = [];

    if (! empty($row['context'])) {

        $decoded = json_decode(
            $row['context'],
            true
        );

        if (is_array($decoded)) {
            $context = $decoded;
        }
    }

    return new SyncLog(
        syncJobUuid: $row['sync_job_uuid'],
        snapshotUuid: $row['snapshot_uuid'],
        level: $row['level'],
        component: $row['component'],
        code: $row['code'],
        message: $row['message'],
        context: $context,
        createdAt: $row['created_at']
    );
}
}