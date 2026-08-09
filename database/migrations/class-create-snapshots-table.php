<?php

namespace BlackPrint\Commerce\Database\Migrations;

use BlackPrint\Commerce\Database\Contracts\MigrationInterface;

defined('ABSPATH') || exit;

/**
 * Create Snapshots Table.
 *
 * Stores immutable snapshot metadata generated
 * during synchronization jobs.
 */
class CreateSnapshotsTable implements MigrationInterface
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

        $table = $wpdb->prefix . 'bp_snapshots';

        $charset = $wpdb->get_charset_collate();

        $sql = "
            CREATE TABLE {$table} (

                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

                uuid CHAR(36) NOT NULL,

                sync_job_uuid CHAR(36) NOT NULL,

                supplier VARCHAR(50) NOT NULL,

                resource VARCHAR(50) NOT NULL,

                type VARCHAR(50) NOT NULL,

                sequence_number INT UNSIGNED NOT NULL DEFAULT 1,

                checksum VARCHAR(64) NOT NULL,

                records_count INT UNSIGNED NOT NULL DEFAULT 0,

                metadata LONGTEXT NULL,

                created_at DATETIME NOT NULL,

                PRIMARY KEY (id),

                UNIQUE KEY idx_uuid (uuid),

                KEY idx_sync_job_uuid (sync_job_uuid),

                KEY idx_supplier (supplier),

                KEY idx_resource (resource),

                KEY idx_type (type),

                KEY idx_sequence (sequence_number),

                KEY idx_created_at (created_at)

            ) {$charset};
        ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta($sql);
    }
}