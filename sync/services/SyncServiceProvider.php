<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Sync\Services;

use BlackPrint\Commerce\Sync\Jobs\ProductSyncJob;
use BlackPrint\Commerce\Sync\Kernel\JobDispatcher;
use BlackPrint\Commerce\Sync\Kernel\JobRunner;
use BlackPrint\Commerce\Sync\Kernel\SyncManager;
use BlackPrint\Commerce\Sync\Registry\ConnectorRegistry;
use BlackPrint\Commerce\Sync\Repositories\SnapshotPayloadRepository;
use BlackPrint\Commerce\Sync\Repositories\SnapshotRepository;
use BlackPrint\Commerce\Sync\Repositories\SyncJobRepository;
use BlackPrint\Commerce\Sync\Stages\ProductsStage;
use BlackPrint\Commerce\Suppliers\Amrod\Amrod_Api_Client;
use BlackPrint\Commerce\Suppliers\Amrod\Amrod_Auth;
use BlackPrint\Commerce\Suppliers\Amrod\Amrod_Config;
use BlackPrint\Commerce\Suppliers\Amrod\Amrod_Product_Service;
use BlackPrint\Commerce\Suppliers\Amrod\AmrodConnector;
use BlackPrint\Commerce\Sync\Replay\SnapshotIntegrityVerifier;

defined('ABSPATH') || exit;

final class SyncServiceProvider
{
    /**
     * Register the BlackPrint synchronization runtime.
     */
    public function register(): SyncManager
    {
        /*
        |--------------------------------------------------------------------------
        | Connector Registry
        |--------------------------------------------------------------------------
        */

        $connectors = new ConnectorRegistry();

        /*
        |--------------------------------------------------------------------------
        | Amrod Supplier Stack
        |--------------------------------------------------------------------------
        */

        $config = new Amrod_Config();

        $auth = new Amrod_Auth(
            $config
        );

        $apiClient = new Amrod_Api_Client(
            $auth,
            $config
        );

        $products = new Amrod_Product_Service(
            $apiClient
        );

        $amrodConnector = new AmrodConnector(
            $products
        );

        $connectors->register(
            $amrodConnector
        );

        /*
        |--------------------------------------------------------------------------
        | Ingestion Stage
        |--------------------------------------------------------------------------
        */

        $productsStage = new ProductsStage();

        /*
        |--------------------------------------------------------------------------
        | Persistence
        |--------------------------------------------------------------------------
        */

        global $wpdb;

        $jobs = new SyncJobRepository(
            $wpdb
        );

        $snapshots = new SnapshotRepository(
            $wpdb
        );

        $payloads = new SnapshotPayloadRepository(
            $wpdb
        );

        /*
        |--------------------------------------------------------------------------
        | Snapshot Integrity
        |--------------------------------------------------------------------------
        |
        | Read-only verification of immutable snapshots and their
        | associated raw payloads.
        |
        */

        $integrityVerifier = new SnapshotIntegrityVerifier(
            snapshots: $snapshots,
            payloads: $payloads
        );

        /*
        |--------------------------------------------------------------------------
        | Job Dispatcher
        |--------------------------------------------------------------------------
        */

        $dispatcher = new JobDispatcher();

        $productJob = new ProductSyncJob(
            stage: $productsStage,
            connectors: $connectors,
            snapshots: $snapshots,
            payloads: $payloads,
            db: $wpdb
        );

        $dispatcher->register(
            $productJob
        );

        /*
        |--------------------------------------------------------------------------
        | Job Runner
        |--------------------------------------------------------------------------
        */

        $runner = new JobRunner(
            dispatcher: $dispatcher,
            jobs: $jobs
        );

        /*
        |--------------------------------------------------------------------------
        | Sync Manager
        |--------------------------------------------------------------------------
        */

        return new SyncManager(
            runner: $runner,
            jobs: $jobs,
            integrityVerifier: $integrityVerifier
        );
    }
}
