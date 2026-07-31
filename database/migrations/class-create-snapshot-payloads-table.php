<?php

namespace BlackPrint\Commerce\Database\Migrations;

use BlackPrint\Commerce\Database\Contracts\MigrationInterface;

defined('ABSPATH') || exit;

/**
 * Create Snapshot Payloads Table.
 *
 * Stores raw supplier payloads associated with
 * synchronization snapshots.
 */
class CreateSnapshotPayloadsTable implements MigrationInterface
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

        $table = $wpdb->prefix . 'bp_snapshot_payloads';

        $charset = $wpdb->get_charset_collate();

        $sql = "
            CREATE TABLE {$table} (

                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

                snapshot_id BIGINT UNSIGNED NOT NULL,

                payload LONGTEXT NOT NULL,

                compressed TINYINT(1) NOT NULL DEFAULT 0,

                payload_size BIGINT UNSIGNED NOT NULL DEFAULT 0,

                created_at DATETIME NOT NULL,

                PRIMARY KEY (id),

                KEY idx_snapshot_id (snapshot_id),

                KEY idx_created_at (created_at)

            ) {$charset};
        ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta($sql);
    }
}