<?php

namespace BlackPrint\Commerce\Database;

use BlackPrint\Commerce\Database\Contracts\MigrationInterface;

defined('ABSPATH') || exit;

/**
 * BlackPrint Database Schema Manager.
 *
 * Responsible for registering and executing database migrations.
 */
final class SchemaManager
{
    /**
     * Registered migrations.
     *
     * @var MigrationInterface[]
     */
    private array $migrations = [];

    /**
     * Register a migration.
     */
    public function register(
        MigrationInterface $migration
    ): void {
        $this->migrations[] = $migration;
    }

    /**
     * Execute pending migrations.
     */
    public function migrate(): void
    {
        error_log('BLACKPRINT: database migration started');

        $this->ensureMigrationsTable();

        foreach ($this->migrations as $migration) {

            /*
             * The migration registry itself has already
             * been created by ensureMigrationsTable().
             */
            if (
                $migration->name()
                === 'BlackPrint\\Commerce\\Database\\Migrations\\CreateMigrationsTable'
            ) {
                continue;
            }

            error_log(
                'BLACKPRINT: checking migration '
                . $migration->name()
            );

            if ($this->hasRun($migration)) {
                error_log(
                    'BLACKPRINT: migration already run '
                    . $migration->name()
                );

                continue;
            }

            error_log(
                'BLACKPRINT: running migration '
                . $migration->name()
            );

            $migration->up();

            $this->record($migration);

            error_log(
                'BLACKPRINT: migration completed '
                . $migration->name()
            );
        }

        error_log('BLACKPRINT: database migration finished');
    }

    /**
     * Ensure the migrations table exists.
     */
    private function ensureMigrationsTable(): void
    {
        if ($this->migrationsTableExists()) {
            return;
        }

        foreach ($this->migrations as $migration) {

            if (
                $migration->name()
                !== 'BlackPrint\\Commerce\\Database\\Migrations\\CreateMigrationsTable'
            ) {
                continue;
            }

            $migration->up();

            $this->record($migration);

            return;
        }

        error_log(
            'BLACKPRINT: CreateMigrationsTable migration not registered'
        );
    }

    /**
     * Check whether the migrations table exists.
     */
    private function migrationsTableExists(): bool
    {
        global $wpdb;

        $table = $wpdb->prefix . 'bp_migrations';

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $table
            )
        );

        return $exists === $table;
    }

    /**
     * Check whether a migration has already run.
     */
    private function hasRun(
        MigrationInterface $migration
    ): bool {
        global $wpdb;

        $table = $wpdb->prefix . 'bp_migrations';

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT COUNT(*)
                FROM {$table}
                WHERE migration = %s
                ",
                $migration->name()
            )
        );

        return (int) $count > 0;
    }

    /**
     * Record a completed migration.
     */
    private function record(
        MigrationInterface $migration
    ): void {
        global $wpdb;

        $table = $wpdb->prefix . 'bp_migrations';

        $wpdb->insert(
            $table,
            [
                'migration'   => $migration->name(),
                'version'     => $migration->version(),
                'executed_at' => current_time('mysql'),
            ],
            [
                '%s',
                '%s',
                '%s',
            ]
        );
    }
}