<?php

namespace BlackPrint\Commerce\Database\Migrations;

use BlackPrint\Commerce\Database\Contracts\MigrationInterface;

defined('ABSPATH') || exit;

/**
 * Create Migrations Table.
 *
 * Tracks executed database migrations so that
 * schema updates are only applied once.
 */
class CreateMigrationsTable implements MigrationInterface
{
    /**
     * Migration version.
     *
     * @return string
     */
    public function version(): string
    {
        return '1.0.0';
    }

    /**
     * Migration name.
     *
     * @return string
     */
    public function name(): string
    {
        return static::class;
    }

    /**
     * Execute migration.
     *
     * @return void
     */
    public function up(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'bp_migrations';

        $charset = $wpdb->get_charset_collate();

        $sql = "
            CREATE TABLE {$table} (

                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

                migration VARCHAR(255) NOT NULL,

                version VARCHAR(50) NOT NULL,

                executed_at DATETIME NOT NULL,

                PRIMARY KEY (id),

                UNIQUE KEY idx_migration (migration)

            ) {$charset};
        ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta($sql);
    }
}