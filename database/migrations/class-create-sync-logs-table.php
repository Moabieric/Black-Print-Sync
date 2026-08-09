<?php

namespace BlackPrint\Commerce\Database\Migrations;

use BlackPrint\Commerce\Database\Contracts\MigrationInterface;

defined('ABSPATH') || exit;

/**
 * Create Sync Logs Table.
 *
 * Stores structured synchronization events.
 */
class CreateSyncLogsTable implements MigrationInterface
{
    public function name(): string
    {
        return static::class;
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function up(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'bp_sync_logs';

        $charset = $wpdb->get_charset_collate();

        $sql = "
            CREATE TABLE {$table} (

                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

                sync_job_uuid CHAR(36) NOT NULL,

                snapshot_uuid CHAR(36) NULL,

                level VARCHAR(20) NOT NULL,

                component VARCHAR(50) NOT NULL,

                code VARCHAR(100) NOT NULL,

                message TEXT NOT NULL,

                context LONGTEXT NULL,

                created_at DATETIME NOT NULL,

                PRIMARY KEY (id),

                KEY idx_sync_job_uuid (sync_job_uuid),

                KEY idx_snapshot_uuid (snapshot_uuid),

                KEY idx_level (level),

                KEY idx_component (component),

                KEY idx_code (code),

                KEY idx_created_at (created_at)

            ) {$charset};
        ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta($sql);
    }
}