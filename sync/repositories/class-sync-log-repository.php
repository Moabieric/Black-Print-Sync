<?php

namespace BlackPrint\Commerce\Sync\Repositories;

use BlackPrint\Commerce\Sync\Contracts\SyncLogRepositoryInterface;
use BlackPrint\Commerce\Sync\Entities\SyncLog;

defined('ABSPATH') || exit;

class SyncLogRepository implements SyncLogRepositoryInterface
{
    private \wpdb $db;

    public function __construct(
        \wpdb $db
    ) {
        $this->db = $db;
    }

    private function table(): string
    {
        return $this->db->prefix . 'bp_sync_logs';
    }

    public function create(
        SyncLog $log
    ): int {

        return 0;
    }

    public function findByJob(
        int $syncJobId
    ): array {

        return [];
    }

    public function findBySnapshot(
        int $snapshotId
    ): array {

        return [];
    }
}