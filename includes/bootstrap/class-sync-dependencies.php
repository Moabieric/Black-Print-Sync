<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

/*
|--------------------------------------------------------------------------
| BlackPrint Commerce Sync Engine
|--------------------------------------------------------------------------
|
| Loads the canonical supplier ingestion and synchronization layer.
|
*/

/*
|--------------------------------------------------------------------------
| Kernel
|--------------------------------------------------------------------------
*/

require_once BP_COMMERCE_PATH
    . 'sync/kernel/class-job-context.php';

require_once BP_COMMERCE_PATH
    . 'sync/kernel/class-sync-result.php';

require_once BP_COMMERCE_PATH
    . 'sync/kernel/class-lock-manager.php';

require_once BP_COMMERCE_PATH
    . 'sync/kernel/class-job-dispatcher.php';

require_once BP_COMMERCE_PATH
    . 'sync/kernel/class-job-runner.php';

require_once BP_COMMERCE_PATH
    . 'sync/kernel/class-sync-manager.php';


/*
|--------------------------------------------------------------------------
| Contracts
|--------------------------------------------------------------------------
*/

require_once BP_COMMERCE_PATH
    . 'sync/contracts/SupplierConnector.php';

require_once BP_COMMERCE_PATH
    . 'sync/contracts/SupportsProducts.php';

require_once BP_COMMERCE_PATH
    . 'sync/contracts/SupportsStock.php';

require_once BP_COMMERCE_PATH
    . 'sync/contracts/SupportsPricing.php';

require_once BP_COMMERCE_PATH
    . 'sync/contracts/SupportsBranding.php';

require_once BP_COMMERCE_PATH
    . 'sync/contracts/IngestionStage.php';

require_once BP_COMMERCE_PATH
    . 'sync/contracts/interface-sync-job.php';

require_once BP_COMMERCE_PATH
    . 'sync/contracts/interface-snapshot-repository.php';

require_once BP_COMMERCE_PATH
    . 'sync/contracts/interface-snapshot-payload-repository.php';

require_once BP_COMMERCE_PATH
    . 'sync/contracts/interface-sync-job-repository.php';

require_once BP_COMMERCE_PATH
    . 'sync/contracts/interface-sync-log-repository.php';


/*
|--------------------------------------------------------------------------
| DTOs
|--------------------------------------------------------------------------
*/

require_once BP_COMMERCE_PATH
    . 'sync/dto/SupplierMetadata.php';

require_once BP_COMMERCE_PATH
    . 'sync/dto/SupplierResponse.php';


/*
|--------------------------------------------------------------------------
| Entities
|--------------------------------------------------------------------------
*/

require_once BP_COMMERCE_PATH
    . 'sync/entities/class-sync-job.php';

require_once BP_COMMERCE_PATH
    . 'sync/entities/class-snapshot.php';

require_once BP_COMMERCE_PATH
    . 'sync/entities/class-snapshot-type.php';

require_once BP_COMMERCE_PATH
    . 'sync/entities/class-sync-log.php';


/*
|--------------------------------------------------------------------------
| Registries
|--------------------------------------------------------------------------
*/

require_once BP_COMMERCE_PATH
    . 'sync/registry/ConnectorRegistry.php';

require_once BP_COMMERCE_PATH
    . 'sync/registry/StageRegistry.php';


/*
|--------------------------------------------------------------------------
| Repositories
|--------------------------------------------------------------------------
*/

require_once BP_COMMERCE_PATH
    . 'sync/repositories/class-sync-job-repository.php';

require_once BP_COMMERCE_PATH
    . 'sync/repositories/class-snapshot-repository.php';

require_once BP_COMMERCE_PATH
    . 'sync/repositories/class-sync-log-repository.php';

require_once BP_COMMERCE_PATH
    . 'sync/storage/class-snapshot-payload-repository.php';


/*
|--------------------------------------------------------------------------
| Stages
|--------------------------------------------------------------------------
*/

require_once BP_COMMERCE_PATH
    . 'sync/stages/ProductsStage.php';


/*
|--------------------------------------------------------------------------
| Jobs
|--------------------------------------------------------------------------
*/

require_once BP_COMMERCE_PATH
    . 'sync/jobs/product-sync-job.php';


/*
|--------------------------------------------------------------------------
| Sync Service Provider
|--------------------------------------------------------------------------
*/

require_once BP_COMMERCE_PATH
    . 'sync/services/SyncServiceProvider.php';