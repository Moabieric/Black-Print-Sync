<?php

namespace BlackPrint\Commerce\Database\Contracts;

defined('ABSPATH') || exit;

interface MigrationInterface
{
    /**
     * Unique migration name.
     */
    public function name(): string;

    /**
     * Migration version.
     */
    public function version(): string;

    /**
     * Execute migration.
     */
    public function up(): void;
}