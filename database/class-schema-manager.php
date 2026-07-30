<?php

namespace BlackPrint\Commerce\Database;

use BlackPrint\Commerce\Database\Contracts\MigrationInterface;

class SchemaManager
{
    private const OPTION_KEY = 'bp_schema_version';

    /**
     * @var MigrationInterface[]
     */
    private array $migrations = [];

    public function register(
        MigrationInterface $migration
    ): void {

        $this->migrations[] = $migration;
    }

    public function migrate(): void
    {
        $installed = get_option(
            self::OPTION_KEY,
            '0.0.0'
        );

        foreach ($this->migrations as $migration) {

            if (
                version_compare(
                    $migration->version(),
                    $installed,
                    '>'
                )
            ) {

                $migration->up();

                update_option(
                    self::OPTION_KEY,
                    $migration->version()
                );
            }
        }
    }
}