<?php

defined('ABSPATH') || exit;

/*
|--------------------------------------------------------------------------
| Projection
|--------------------------------------------------------------------------
|
| Channel-specific projection planning and execution.
|
*/

require_once BP_COMMERCE_PATH
    . 'projection/contracts/ProductProjectorInterface.php';

require_once BP_COMMERCE_PATH
    . 'projection/contracts/ProjectionExecutorInterface.php';

require_once BP_COMMERCE_PATH
    . 'projection/dto/ProjectionResult.php';

require_once BP_COMMERCE_PATH
    . 'projection/woocommerce/WooCommerceProductProjector.php';

require_once BP_COMMERCE_PATH
    . 'projection/woocommerce/WooCommerceProjectionExecutor.php';


/*
|--------------------------------------------------------------------------
| DTO
|--------------------------------------------------------------------------
*/

require_once BP_COMMERCE_PATH
    . 'projection/dto/ProjectionResult.php';

 /*
|--------------------------------------------------------------------------
| Contracts
|--------------------------------------------------------------------------
*/

require_once BP_COMMERCE_PATH
    . 'projection/contracts/ProductProjectorInterface.php';

require_once BP_COMMERCE_PATH
    . 'projection/contracts/ProjectionExecutorInterface.php';


/*
|--------------------------------------------------------------------------
| WooCommerce Projection
|--------------------------------------------------------------------------
*/

require_once BP_COMMERCE_PATH
    . 'projection/woocommerce/WooCommerceProductProjector.php';

require_once BP_COMMERCE_PATH
    . 'projection/woocommerce/WooCommerceProjectionExecutor.php';