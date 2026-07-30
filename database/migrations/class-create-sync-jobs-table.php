<?php

namespace BlackPrint\Commerce\Database\Migrations;

use BlackPrint\Commerce\Database\Contracts\MigrationInterface;

class CreateSyncJobsTable implements MigrationInterface
{
    public function version(): string
    {
        return '2.0.0';
    }

    public function up(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = $wpdb->prefix . 'bp_sync_jobs';

        $charset = $wpdb->get_charset_collate();

        $sql = "

        CREATE TABLE {$table} (

            id CHAR(36) NOT NULL,

            supplier VARCHAR(50) NOT NULL,

            job_name VARCHAR(100) NOT NULL,

            status VARCHAR(30) NOT NULL,

            attempt INT NOT NULL DEFAULT 1,

            snapshot_id CHAR(36) NULL,

            started_at DATETIME NOT NULL,

            finished_at DATETIME NULL,

            duration_ms BIGINT NULL,

            error_message TEXT NULL,

            metadata LONGTEXT NULL,

            PRIMARY KEY (id),

            KEY idx_supplier (supplier),

            KEY idx_job_name (job_name),

            KEY idx_status (status)

        ) {$charset};

        ";

        dbDelta($sql);
    }
}