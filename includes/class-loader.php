<?php

declare(strict_types=1);

namespace BlackPrint\Commerce;

defined('ABSPATH') || exit;

use BlackPrint\Commerce\Database\Migrations\CreateMigrationsTable;
use BlackPrint\Commerce\Database\Migrations\CreateSnapshotPayloadsTable;
use BlackPrint\Commerce\Database\Migrations\CreateSnapshotsTable;
use BlackPrint\Commerce\Database\Migrations\CreateSyncJobsTable;
use BlackPrint\Commerce\Database\Migrations\CreateSyncLogsTable;
use BlackPrint\Commerce\Database\SchemaManager;
use BlackPrint\Commerce\Normalization\Services\NormalizationServiceProvider;
use BlackPrint\Commerce\Normalization\Services\SnapshotNormalizationService;
use BlackPrint\Commerce\Sync\Kernel\SyncManager;
use BlackPrint\Commerce\Sync\Services\SyncServiceProvider;

/**
 * BlackPrint Commerce Plugin Loader.
 *
 * Responsible for:
 *
 * - Loading plugin dependencies.
 * - Booting database services.
 * - Booting runtime components.
 *
 * This class acts as the composition root of BlackPrint OS.
 */
final class Loader
{
    /**
     * Singleton instance.
     */
    private static ?self $instance = null;

    /**
     * Sync runtime manager.
     */
    private SyncManager $syncManager;

    /**
     * Normalization runtime service.
     */
    private SnapshotNormalizationService $normalizationService;


    /**
     * Get singleton instance.
     */
    public static function instance(): self
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

    $this->loadNormalizationDependencies();

    $this->loadProjectionDependencies();

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
     * Load normalization dependencies.
     */
    private function loadNormalizationDependencies(): void
    {
        require_once BP_COMMERCE_PATH
            . 'includes/bootstrap/class-normalization-dependencies.php';
    }

    /**
     * Load projection dependencies.
     */
    private function loadProjectionDependencies(): void
    {
        require_once BP_COMMERCE_PATH
            . 'includes/bootstrap/class-projection-dependencies.php';
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
     * Boot plugin components.
     */
    private function boot(): void
    {
        $this->bootDatabase();

        $this->bootSync();

        $this->bootNormalization();

        $this->bootAdmin();
    }


    /**
     * Boot database services.
     */
    private function bootDatabase(): void
    {
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
    }


    /**
     * Boot the synchronization runtime.
     */
    private function bootSync(): void
    {
        $provider = new SyncServiceProvider();

        $this->syncManager = $provider->register();
    }

    /**
     * Boot the normalization runtime.
     */
    private function bootNormalization(): void
    {
        $provider = new NormalizationServiceProvider();

        $this->normalizationService = $provider->register();
    }

    /**
     * Boot admin components.
     */
    private function bootAdmin(): void
    {
        new Admin();
    }


    /**
 * Get the synchronization manager.
 */
public function syncManager(): SyncManager
{
    return $this->syncManager;
}

/**
 * Get the snapshot normalization service.
 */
public function normalization(): SnapshotNormalizationService
{
    return $this->normalizationService;
}
}