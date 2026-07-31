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
    /**
     * Unique migration name.
     */
    public function name(): string
    {
        return static::class;
    }

    /**
     * Migration version.
     */
    public function version(): string
    {
        return '1.0.0';
    }

    /**
     * Execute migration.
     */
    public function up(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'bp_sync_logs';

        $charset = $wpdb->get_charset_collate();

        $sql = "
            CREATE TABLE {$table} (

                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

                sync_job_id BIGINT UNSIGNED NOT NULL,

                snapshot_id BIGINT UNSIGNED NULL,

                level VARCHAR(20) NOT NULL,

                component VARCHAR(50) NOT NULL,

                code VARCHAR(100) NOT NULL,

                message TEXT NOT NULL,

                context LONGTEXT NULL,

                created_at DATETIME NOT NULL,

                PRIMARY KEY (id),

                KEY idx_sync_job_id (sync_job_id),

                KEY idx_snapshot_id (snapshot_id),

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