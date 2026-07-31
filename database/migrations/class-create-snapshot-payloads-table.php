<?php

namespace BlackPrint\Commerce\Database\Migrations;

use BlackPrint\Commerce\Database\Contracts\MigrationInterface;

defined('ABSPATH') || exit;

class CreateSnapshotPayloadsTable implements MigrationInterface
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
        // TODO
    }
}