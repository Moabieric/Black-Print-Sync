<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Sync\Services;

use BlackPrint\Commerce\Sync\Jobs\ProductSyncJob;
use BlackPrint\Commerce\Sync\Kernel\JobDispatcher;
use BlackPrint\Commerce\Sync\Kernel\JobRunner;
use BlackPrint\Commerce\Sync\Kernel\SyncManager;
use BlackPrint\Commerce\Sync\Registry\ConnectorRegistry;
use BlackPrint\Commerce\Sync\Registry\StageRegistry;
use BlackPrint\Commerce\Sync\Repositories\SnapshotRepository;
use BlackPrint\Commerce\Sync\Repositories\SnapshotPayloadRepository;
use BlackPrint\Commerce\Sync\Stages\ProductsStage;
use BlackPrint\Suppliers\Amrod\AmrodConfig;
use BlackPrint\Suppliers\Amrod\AmrodConnector;
use BlackPrint\Suppliers\Amrod\AmrodHttpClient;

final class SyncServiceProvider
{
    public function register(): SyncManager
    {
        /*
         * ---------------------------------------------------------
         * Connector Registry
         * ---------------------------------------------------------
         */

        $connectors = new ConnectorRegistry();

        $config = new AmrodConfig(

            baseUrl: get_option(
                'bp_amrod_base_url',
                ''
            ),

            username: get_option(
                'bp_amrod_username',
                ''
            ),

            password: get_option(
                'bp_amrod_password',
                ''
            )

        );

        $httpClient = new AmrodHttpClient(
            $config
        );

        $amrodConnector = new AmrodConnector(
            $httpClient
        );

        $connectors->register(
            $amrodConnector
        );


        /*
         * ---------------------------------------------------------
         * Stage Registry
         * ---------------------------------------------------------
         */

        $stages = new StageRegistry();

        $productsStage = new ProductsStage();

        $stages->register(
            $productsStage
        );


        /*
 * ---------------------------------------------------------
 * Persistence
 * ---------------------------------------------------------
 */

global $wpdb;

$snapshots = new SnapshotRepository(
    $wpdb
);

$payloads = new SnapshotPayloadRepository(
    $wpdb
);


        /*
         * ---------------------------------------------------------
         * Job Dispatcher
         * ---------------------------------------------------------
         */

        $dispatcher = new JobDispatcher();

        $productJob = new ProductSyncJob(

    stage: $productsStage,

    connectors: $connectors,

    snapshots: $snapshots,

    payloads: $payloads

);

        $dispatcher->register(
            $productJob
        );


        /*
         * ---------------------------------------------------------
         * Runner
         * ---------------------------------------------------------
         */

        $runner = new JobRunner(
            $dispatcher
        );


        /*
         * ---------------------------------------------------------
         * Sync Manager
         * ---------------------------------------------------------
         */

        return new SyncManager(
            $runner
        );
    }
}