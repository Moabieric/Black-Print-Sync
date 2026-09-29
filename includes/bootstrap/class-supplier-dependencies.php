<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

/*
|--------------------------------------------------------------------------
| Canonical Amrod Supplier Services
|--------------------------------------------------------------------------
|
| These services provide the established Amrod supplier integration used by
| the existing BlackPrint Commerce admin and supplier functionality.
|
| The canonical stack remains the supplier-specific source for:
|
| - Configuration
| - Authentication
| - HTTP/API communication
| - Products
| - Stock
| - Pricing
| - Branding
| - Categories
| - Colour swatches
|
*/

/*
|--------------------------------------------------------------------------
| Core Configuration & Authentication
|--------------------------------------------------------------------------
*/

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-config.php';

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-auth.php';

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-api-client.php';


/*
|--------------------------------------------------------------------------
| Supplier Domain Services
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| Legacy / Existing Amrod Connector
|--------------------------------------------------------------------------
|
| The existing Amrod_Connector remains loaded because the current admin
| screens and health-check functionality still depend on it.
|
| This is separate from the Sync Engine's AmrodConnector adapter below.
|
*/

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-connector.php';


/*
|--------------------------------------------------------------------------
| Sync Contracts Required by Supplier Adapters
|--------------------------------------------------------------------------
|
| The sync-facing Amrod adapter implements these contracts.
| They must be available before AmrodConnector is loaded.
|
*/

require_once BP_COMMERCE_PATH
    . 'sync/contracts/SupplierConnector.php';

require_once BP_COMMERCE_PATH
    . 'sync/contracts/SupportsProducts.php';


/*
|--------------------------------------------------------------------------
| Sync-Facing Amrod Adapter
|--------------------------------------------------------------------------
|
| This adapter is the boundary between the canonical Amrod supplier stack
| and the BlackPrint OS Sync Engine.
|
| It is read-only and does not write directly to WooCommerce.
|
*/

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/AmrodConnector.php';


/*
|--------------------------------------------------------------------------
| Supplier Health Check
|--------------------------------------------------------------------------
|
| Loaded last because it depends on the existing Amrod_Connector.
|
*/

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-health-check.php';