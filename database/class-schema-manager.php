<?php

namespace BlackPrint\Commerce\Database;

use BlackPrint\Commerce\Database\Contracts\MigrationInterface;

defined('ABSPATH') || exit;

class SchemaManager
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
    error_log('BLACKPRINT: migrate started');

    foreach ($this->migrations as $migration) {

        error_log(
            'BLACKPRINT: checking ' . $migration->name()
        );

            /*
             * Bootstrap phase:
             * If the migrations table does not exist yet,
             * run only the migration that creates it.
             */

            if (! $this->migrationsTableExists()) {

                if (
                    str_contains(
                        $migration->name(),
                        'CreateMigrationsTable'
                    )
                ) {
                    $migration->up();

                    $this->record($migration);
                }

                continue;
            }

            /*
             * Normal phase:
             * Run only migrations that have not been executed.
             */

            if ($this->hasRun($migration)) {
                continue;
            }

            $migration->up();

            $this->record($migration);
        }
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
                'migration'  => $migration->name(),
                'version'    => $migration->version(),
                'executed_at' => current_time('mysql'),
            ]
        );
    }
}