<?php

namespace BlackPrint\Commerce\Sync\Repositories;

use BlackPrint\Commerce\Sync\Contracts\SyncJobRepositoryInterface;
use BlackPrint\Commerce\Sync\Entities\SyncJob;

defined('ABSPATH') || exit;

class SyncJobRepository implements SyncJobRepositoryInterface
{
    private \wpdb $db;

    public function __construct(
        \wpdb $db
    ) {
        $this->db = $db;
    }

    private function table(): string
    {
        return $this->db->prefix . 'bp_sync_jobs';
    }

    public function create(
        SyncJob $job
    ): int {

        $this->db->insert(

            $this->table(),

            [

                'uuid' => $job->uuid(),

                'supplier' => $job->supplier(),

                'resource' => $job->resource(),

                'job_type' => $job->jobType(),

                'status' => $job->status(),

                'records_processed' => $job->recordsProcessed(),

                'started_at' => $job->startedAt(),

                'completed_at' => $job->completedAt(),

                'error_message' => $job->errorMessage(),

                'created_at' => gmdate('Y-m-d H:i:s'),

                'updated_at' => gmdate('Y-m-d H:i:s'),

            ]

        );

        return (int) $this->db->insert_id;
    }

    public function find(
        string $uuid
    ): ?SyncJob {

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

        return new SyncJob(

            uuid: $row['uuid'],

            supplier: $row['supplier'],

            resource: $row['resource'],

            jobType: $row['job_type'],

            status: $row['status'],

            recordsProcessed: (int) $row['records_processed'],

            startedAt: $row['started_at'],

            completedAt: $row['completed_at'],

            errorMessage: $row['error_message']

        );
    }

    public function markRunning(
        string $uuid
    ): void {

        $this->update(

            $uuid,

            [

                'status' => 'running',

                'started_at' => gmdate('Y-m-d H:i:s'),

            ]

        );
    }

    public function markCompleted(
        string $uuid,
        int $recordsProcessed
    ): void {

        $this->update(

            $uuid,

            [

                'status' => 'completed',

                'records_processed' => $recordsProcessed,

                'completed_at' => gmdate('Y-m-d H:i:s'),

            ]

        );
    }

    public function markFailed(
        string $uuid,
        string $message
    ): void {

        $this->update(

            $uuid,

            [

                'status' => 'failed',

                'error_message' => $message,

                'completed_at' => gmdate('Y-m-d H:i:s'),

            ]

        );
    }

    public function markCancelled(
        string $uuid
    ): void {

        $this->update(

            $uuid,

            [

                'status' => 'cancelled',

                'completed_at' => gmdate('Y-m-d H:i:s'),

            ]

        );
    }

    private function update(
        string $uuid,
        array $data
    ): void {

        $data['updated_at'] = gmdate('Y-m-d H:i:s');

        $this->db->update(

            $this->table(),

            $data,

            [

                'uuid' => $uuid,

            ]

        );
    }
}