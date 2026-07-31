<?php

defined('ABSPATH') || exit;

/*
|--------------------------------------------------------------------------
| Amrod Connector
|--------------------------------------------------------------------------
*/

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-config.php';

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-auth.php';

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-api-client.php';

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-brand-service.php';

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-category-service.php';

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-product-service.php';

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-stock-service.php';

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-price-service.php';

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-branding-department-service.php';

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-inclusive-branding-service.php';

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-colour-swatch-service.php';

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-connector.php';

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-health-check.php';