<?php

namespace BlackPrint\Commerce;

defined('ABSPATH') || exit;

use BlackPrint\Commerce\Database\SchemaManager;
use BlackPrint\Commerce\Database\Migrations\CreateMigrationsTable;
use BlackPrint\Commerce\Database\Migrations\CreateSyncJobsTable;
use BlackPrint\Commerce\Database\Migrations\CreateSnapshotsTable;
use BlackPrint\Commerce\Database\Migrations\CreateSnapshotPayloadsTable;
use BlackPrint\Commerce\Database\Migrations\CreateSyncLogsTable;

/**
 * BlackPrint Commerce Plugin Loader.
 *
 * Responsible for:
 *
 * - Loading plugin dependencies
 * - Booting database services
 * - Booting runtime components
 *
 * This class acts as the composition root of BlackPrint OS.
 */
class Loader
{
    /**
     * Singleton instance.
     */
    private static ?Loader $instance = null;

    /**
     * Get singleton instance.
     */
    public static function instance(): Loader
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
        $this->loadDependencies();

        $this->boot();
    }

    /**
     * Load all plugin dependencies.
     */
    private function loadDependencies(): void
    {
        $this->loadCoreDependencies();

$this->loadSupplierDependencies();

$this->loadDatabaseDependencies();

$this->loadSyncDependencies();

$this->loadAdminDependencies();
    }

    /**
     * Load core dependencies.
     */
    private function loadCoreDependencies(): void
    {
        require_once BP_COMMERCE_PATH
            . 'includes/bootstrap/class-core-dependencies.php';
    }

    /**
     * Load supplier dependencies.
     */
    private function loadSupplierDependencies(): void
    {
        require_once BP_COMMERCE_PATH
            . 'includes/bootstrap/class-supplier-dependencies.php';
    }

    /**
     * Load database dependencies.
     */
    private function loadDatabaseDependencies(): void
    {
        require_once BP_COMMERCE_PATH
            . 'includes/bootstrap/class-database-dependencies.php';
    }
/**
     * Load sync dependencies.
     */
    private function loadSyncDependencies(): void
{
    require_once BP_COMMERCE_PATH
        . 'includes/bootstrap/class-sync-dependencies.php';
}

    /**
     * Load admin dependencies.
     */
    private function loadAdminDependencies(): void
    {
        require_once BP_COMMERCE_PATH
            . 'includes/bootstrap/class-admin-dependencies.php';
    }

    /**
     * Boot database services.
     */
    private function bootDatabase(): void
    {
        /*
         * Database migrations are disabled for now.
         *
         * We will enable this once all migration classes
         * have been implemented.
         */

        /*
        $schema = new SchemaManager();

        $schema->register(
            new CreateMigrationsTable()
        );

        $schema->register(
            new CreateSyncJobsTable()
        );

        $schema->register(
            new CreateSnapshotsTable()
        );

        $schema->register(
            new CreateSnapshotPayloadsTable()
        );

        $schema->register(
            new CreateSyncLogsTable()
        );

        $schema->migrate();
        */
    }

    /**
     * Boot plugin components.
     */
    private function boot(): void
    {
        $this->bootDatabase();

        new Admin();
    }
}