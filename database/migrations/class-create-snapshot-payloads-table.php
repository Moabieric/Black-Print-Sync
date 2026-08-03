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

        snapshot_uuid VARCHAR(64) NOT NULL,

        payload LONGTEXT NOT NULL,

        compression VARCHAR(20) NOT NULL DEFAULT 'gzip',

        payload_size BIGINT UNSIGNED NOT NULL DEFAULT 0,

        created_at DATETIME NOT NULL,

        PRIMARY KEY (snapshot_uuid),

        KEY idx_created_at (created_at)

    ) {$charset};

";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta($sql);
    }
}