<?php

namespace BlackPrint\Commerce\Database\Contracts;

interface MigrationInterface
{
    /**
     * Unique migration identifier.
     */
    public function version(): string;

    /**
     * Execute the migration.
     */
    public function up(): void;
}