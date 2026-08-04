<?php

defined('ABSPATH') || exit;

/*
|--------------------------------------------------------------------------
| Sync Engine
|--------------------------------------------------------------------------
|
| Supplier ingestion and synchronization.
|
*/

require_once BP_COMMERCE_PATH
    . 'sync/kernel/class-job-context.php';

require_once BP_COMMERCE_PATH
    . 'sync/kernel/class-sync-result.php';

require_once BP_COMMERCE_PATH
    . 'sync/kernel/class-job-runner.php';

require_once BP_COMMERCE_PATH
    . 'sync/kernel/class-job-dispatcher.php';

require_once BP_COMMERCE_PATH
    . 'sync/kernel/class-sync-manager.php';

require_once BP_COMMERCE_PATH
    . 'sync/entities/class-sync-job.php';

require_once BP_COMMERCE_PATH
    . 'sync/entities/class-snapshot.php';

require_once BP_COMMERCE_PATH
    . 'sync/repositories/class-sync-job-repository.php';

require_once BP_COMMERCE_PATH
    . 'sync/repositories/class-snapshot-repository.php';

require_once BP_COMMERCE_PATH
    . 'sync/storage/class-snapshot-payload-repository.php';