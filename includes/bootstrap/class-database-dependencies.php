<?php

defined('ABSPATH') || exit;

/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
|
| Schema management and migrations.
|
*/

require_once BP_COMMERCE_PATH
    . 'database/contracts/interface-migration.php';

require_once BP_COMMERCE_PATH
    . 'database/class-schema-manager.php';

require_once BP_COMMERCE_PATH
    . 'database/migrations/class-create-migrations-table.php';

require_once BP_COMMERCE_PATH
    . 'database/migrations/class-create-sync-jobs-table.php';

require_once BP_COMMERCE_PATH
    . 'database/migrations/class-create-snapshots-table.php';

require_once BP_COMMERCE_PATH
    . 'database/migrations/class-create-snapshot-payloads-table.php';

require_once BP_COMMERCE_PATH
    . 'database/migrations/class-create-sync-logs-table.php';