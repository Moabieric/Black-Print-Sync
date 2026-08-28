<?php

defined('ABSPATH') || exit;

/*
|--------------------------------------------------------------------------
| Projection
|--------------------------------------------------------------------------
|
| Channel-specific projection planning.
|
*/


require_once BP_COMMERCE_PATH
    . 'projection/contracts/ProductProjectorInterface.php';

require_once BP_COMMERCE_PATH
    . 'projection/dto/ProjectionResult.php';

require_once BP_COMMERCE_PATH
    . 'projection/woocommerce/WooCommerceProductProjector.php';