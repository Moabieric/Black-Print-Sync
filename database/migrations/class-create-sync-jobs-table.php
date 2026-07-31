<?php

namespace BlackPrint\Commerce\Database\Migrations;

use BlackPrint\Commerce\Database\Contracts\MigrationInterface;

defined('ABSPATH') || exit;

/**
 * Create Sync Jobs Table.
 *
 * Stores immutable synchronization jobs.
 */
class CreateSyncJobsTable implements MigrationInterface
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

        $table = $wpdb->prefix . 'bp_sync_jobs';

        $charset = $wpdb->get_charset_collate();

        $sql = "
            CREATE TABLE {$table} (

                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

                uuid CHAR(36) NOT NULL,

                supplier VARCHAR(50) NOT NULL,

                resource VARCHAR(50) NOT NULL,

                job_type VARCHAR(50) NOT NULL,

                status VARCHAR(20) NOT NULL,

                records_processed INT UNSIGNED NOT NULL DEFAULT 0,

                started_at DATETIME NULL,

                completed_at DATETIME NULL,

                error_message LONGTEXT NULL,

                created_at DATETIME NOT NULL,

                updated_at DATETIME NOT NULL,

                PRIMARY KEY (id),

                UNIQUE KEY idx_uuid (uuid),

                KEY idx_supplier (supplier),

                KEY idx_resource (resource),

                KEY idx_status (status),

                KEY idx_created_at (created_at)

            ) {$charset};
        ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta($sql);
    }
}