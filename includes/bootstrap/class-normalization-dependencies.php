<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

/*
|--------------------------------------------------------------------------
| Normalization Contracts
|--------------------------------------------------------------------------
*/

require_once BP_COMMERCE_PATH
    . 'normalization/contracts/interface-canonical-normalizer.php';


/*
|--------------------------------------------------------------------------
| Normalization DTOs
|--------------------------------------------------------------------------
*/

require_once BP_COMMERCE_PATH
    . 'normalization/dto/class-canonical-product.php';

require_once BP_COMMERCE_PATH
    . 'normalization/dto/class-canonical-product-collection.php';

require_once BP_COMMERCE_PATH
    . 'normalization/dto/class-normalization-result.php';


/*
|--------------------------------------------------------------------------
| Normalization Registry
|--------------------------------------------------------------------------
*/

require_once BP_COMMERCE_PATH
    . 'normalization/registry/class-canonical-normalizer-registry.php';



    /*
|--------------------------------------------------------------------------
| Supplier Normalizers
|--------------------------------------------------------------------------
*/

require_once BP_COMMERCE_PATH
    . 'normalization/suppliers/amrod/class-amrod-products-normalizer.php';
/*
|--------------------------------------------------------------------------
| Normalization Services
|--------------------------------------------------------------------------
*/

require_once BP_COMMERCE_PATH
    . 'normalization/services/class-snapshot-normalization-service.php';

require_once BP_COMMERCE_PATH
    . 'normalization/services/class-normalization-service-provider.php';

