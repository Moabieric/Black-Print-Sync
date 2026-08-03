<?php

declare(strict_types=1);

namespace BlackPrint\Sync;

use BlackPrint\Sync\Registry\ConnectorRegistry;
use BlackPrint\Sync\Registry\StageRegistry;
use BlackPrint\Sync\Stages\ProductsStage;

use BlackPrint\Suppliers\Amrod\AmrodConfig;
use BlackPrint\Suppliers\Amrod\AmrodConnector;
use BlackPrint\Suppliers\Amrod\AmrodHttpClient;

use BlackPrint\Sync\Repositories\SnapshotRepository;
use BlackPrint\Sync\Repositories\SnapshotPayloadRepository;
use BlackPrint\Sync\Services\SyncManager;

final class SyncServiceProvider
{
    public function register(): SyncPipeline
    {
        $connectors = new ConnectorRegistry();

        $stages = new StageRegistry();

        $config = new AmrodConfig(

            baseUrl: get_option('bp_amrod_base_url', ''),

            username: get_option('bp_amrod_username', ''),

            password: get_option('bp_amrod_password', '')

        );

        $httpClient = new AmrodHttpClient($config);

        $connector = new AmrodConnector($httpClient);

        $connectors->register($connector);

        $stages->register(

            new ProductsStage()

        );

        return new SyncPipeline(

            syncManager: new SyncManager(),

            stages: $stages,

            connectors: $connectors,

            snapshots: new SnapshotRepository(),

            payloads: new SnapshotPayloadRepository()

        );
    }
}