<?php

namespace BlackPrint\Commerce\Database\Migrations;

use BlackPrint\Commerce\Database\Contracts\MigrationInterface;

defined('ABSPATH') || exit;

class CreateMigrationsTable implements MigrationInterface
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

        $table = $wpdb->prefix . 'bp_migrations';

        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "
        CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            migration VARCHAR(255) NOT NULL,
            version VARCHAR(50) NOT NULL,
            executed_at DATETIME NOT NULL,

            PRIMARY KEY (id),
            UNIQUE KEY migration (migration)
        ) {$charsetCollate};
        ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta($sql);
    }
}