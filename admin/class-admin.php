<?php

declare(strict_types=1);

namespace BlackPrint\Commerce;

defined('ABSPATH') || exit;

/**
 * BlackPrint Commerce Admin.
 *
 * Responsible for registering the BlackPrint Commerce
 * administration menu and dashboard pages.
 *
 * @package BlackPrint\Commerce
 */
final class Admin
{
    /**
     * Constructor.
     *
     * Registers WordPress admin hooks.
     */
    public function __construct()
    {
        add_action(
            'admin_menu',
            [
                $this,
                'register_menu',
            ]
        );

        add_action(
            'admin_post_bp_run_amrod_product_ingestion_test',
            [
                $this,
                'run_amrod_product_ingestion_test',
            ]
        );
        add_action(
            'admin_post_bp_verify_snapshot_integrity',
            [
                $this,
                'verify_snapshot_integrity',
            ]
        );

        add_action(
            'admin_post_bp_test_snapshot_normalization',
            [
                $this,
                'test_snapshot_normalization',
            ]
        );

        add_action(
            'admin_post_bp_test_woocommerce_projection',
            [
                $this,
                'test_woocommerce_projection',
            ]
        );


        add_action(
            'admin_post_bp_test_woocommerce_variant_creation',
            [
                $this,
                'test_woocommerce_variant_creation',
            ]
        );
    }


    /**
     * Register BlackPrint Commerce admin menus.
     */
    public function register_menu(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Main BlackPrint Commerce Menu
        |--------------------------------------------------------------------------
        */

        add_menu_page(
            'BlackPrint Commerce',
            'BlackPrint',
            'manage_woocommerce',
            'blackprint-commerce',
            [
                $this,
                'dashboard',
            ],
            'dashicons-store',
            56
        );


        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        add_submenu_page(
            'blackprint-commerce',
            'BlackPrint Commerce Dashboard',
            'Dashboard',
            'manage_woocommerce',
            'blackprint-commerce',
            [
                $this,
                'dashboard',
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Amrod Connector
        |--------------------------------------------------------------------------
        */

        add_submenu_page(
            'blackprint-commerce',
            'Amrod Connector',
            'Amrod Connector',
            'manage_woocommerce',
            'blackprint-amrod',
            [
                $this,
                'amrod_connector',
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Amrod Brands
        |--------------------------------------------------------------------------
        */

        add_submenu_page(
            'blackprint-commerce',
            'Amrod Brands',
            'Amrod Brands',
            'manage_woocommerce',
            'blackprint-amrod-brands',
            [
                $this,
                'amrod_brands',
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Amrod Categories
        |--------------------------------------------------------------------------
        */

        add_submenu_page(
            'blackprint-commerce',
            'Amrod Categories',
            'Amrod Categories',
            'manage_woocommerce',
            'blackprint-amrod-categories',
            [
                $this,
                'amrod_categories',
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Amrod Products
        |--------------------------------------------------------------------------
        |
        | Read-only access to product data returned by the
        | Amrod Vendor API.
        |
        */

        add_submenu_page(
            'blackprint-commerce',
            'Amrod Products',
            'Amrod Products',
            'manage_woocommerce',
            'blackprint-amrod-products',
            [
                $this,
                'amrod_products',
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Sync Ingestion Test
        |--------------------------------------------------------------------------
        |
        | Controlled one-time test of the BlackPrint OS
        | supplier ingestion pipeline.
        |
        */

        add_submenu_page(
            'blackprint-commerce',
            'Sync Ingestion Test',
            'Sync Ingestion Test',
            'manage_woocommerce',
            'blackprint-sync-ingestion-test',
            [
                $this,
                'sync_ingestion_test',
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Amrod Stock
        |--------------------------------------------------------------------------
        */

        add_submenu_page(
            'blackprint-commerce',
            'Amrod Stock',
            'Amrod Stock',
            'manage_woocommerce',
            'blackprint-amrod-stock',
            [
                $this,
                'amrod_stock',
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Amrod Prices
        |--------------------------------------------------------------------------
        */

        add_submenu_page(
            'blackprint-commerce',
            'Amrod Prices',
            'Amrod Prices',
            'manage_woocommerce',
            'blackprint-amrod-prices',
            [
                $this,
                'amrod_prices',
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Amrod Branding
        |--------------------------------------------------------------------------
        */

        add_submenu_page(
            'blackprint-commerce',
            'Amrod Branding',
            'Amrod Branding',
            'manage_woocommerce',
            'blackprint-amrod-branding',
            [
                $this,
                'amrod_branding',
            ]
        );
    }


    /**
     * Render the main BlackPrint Commerce dashboard.
     */
    public function dashboard(): void
    {
        include BP_COMMERCE_PATH
            . 'admin/views/dashboard.php';
    }


    /**
     * Render the Amrod Connector administration page.
     */
    public function amrod_connector(): void
    {
        include BP_COMMERCE_PATH
            . 'admin/views/amrod-connector.php';
    }


    /**
     * Render the Amrod Brands Explorer page.
     *
     * This page is read-only.
     */
    public function amrod_brands(): void
    {
        $connector = new \BlackPrint\Commerce\Suppliers\Amrod\Amrod_Connector();

        $brand_service = new \BlackPrint\Commerce\Suppliers\Amrod\Amrod_Brand_Service(
            $connector->get_api_client()
        );

        $brands = [];

        $error = '';

        if (
            isset($_GET['bp_amrod_refresh'])
            && check_admin_referer(
                'bp_amrod_refresh_brands'
            )
        ) {
            try {

                $brand_service->clear_cache();

                $brands = $brand_service->refresh();

            } catch (\Throwable $exception) {

                $error = $exception->getMessage();
            }

        } else {

            try {

                $brands = $brand_service->get_brands();

            } catch (\Throwable $exception) {

                $error = $exception->getMessage();
            }
        }

        include BP_COMMERCE_PATH
            . 'admin/views/amrod-brands.php';
    }


    /**
     * Render the Amrod Categories Explorer page.
     *
     * This page is read-only.
     */
    public function amrod_categories(): void
    {
        $connector = new \BlackPrint\Commerce\Suppliers\Amrod\Amrod_Connector();

        $category_service = new \BlackPrint\Commerce\Suppliers\Amrod\Amrod_Category_Service(
            $connector->get_api_client()
        );

        $categories = [];

        $error = '';

        if (
            isset($_GET['bp_amrod_refresh'])
            && check_admin_referer(
                'bp_amrod_refresh_categories'
            )
        ) {
            try {

                $category_service->clear_cache();

                $categories = $category_service->refresh();

            } catch (\Throwable $exception) {

                $error = $exception->getMessage();
            }

        } else {

            try {

                $categories = $category_service->get_categories();

            } catch (\Throwable $exception) {

                $error = $exception->getMessage();
            }
        }

        include BP_COMMERCE_PATH
            . 'admin/views/amrod-categories.php';
    }


    /**
     * Render the Amrod Products Explorer page.
     *
     * This page is read-only.
     *
     * No WooCommerce products are created or modified.
     */
    public function amrod_products(): void
    {
        $connector = new \BlackPrint\Commerce\Suppliers\Amrod\Amrod_Connector();

        $product_service = $connector->get_product_service();

        $result = [];

        $error = '';

        $action = isset($_GET['bp_amrod_product_action'])
            ? sanitize_key(
                wp_unslash(
                    $_GET['bp_amrod_product_action']
                )
            )
            : '';


        /*
        |--------------------------------------------------------------------------
        | Execute Read-Only Product API Test
        |--------------------------------------------------------------------------
        */

        if (
            isset($_GET['bp_amrod_products_test'])
            && check_admin_referer(
                'bp_amrod_products_test'
            )
        ) {
            try {

                switch ($action) {

                    case 'products':

                        $result = $product_service->get_products();

                        break;

                    case 'updated_products':

                        $result = $product_service->get_updated_products();

                        break;

                    case 'products_with_branding':

                        $result = $product_service->get_products_with_branding();

                        break;

                    case 'updated_products_with_branding':

                        $result = $product_service
                            ->get_updated_products_with_branding();

                        break;

                    default:

                        $error =
                            'Invalid Amrod product API action.';

                        break;
                }

            } catch (\Throwable $exception) {

                $error = $exception->getMessage();
            }
        }

        include BP_COMMERCE_PATH
            . 'admin/views/amrod-products.php';
    }


    /**
     * Render the controlled sync ingestion test page.
     *
     * This page triggers one manual Amrod product ingestion
     * through the BlackPrint OS Sync Engine.
     *
     * No WooCommerce products are created or modified.
     */
    public function sync_ingestion_test(): void
    {
        include BP_COMMERCE_PATH
            . 'admin/views/sync-ingestion-test.php';
    }


    /**
     * Run one controlled Amrod product ingestion.
     *
     * This action:
     *
     * - Creates a SyncJob.
     * - Fetches raw Amrod product data.
     * - Persists an immutable Snapshot.
     * - Persists the immutable raw payload.
     * - Does not write to WooCommerce.
     */
    public function run_amrod_product_ingestion_test(): void
    {
        if (
            ! current_user_can(
                'manage_woocommerce'
            )
        ) {
            wp_die(
                'You do not have permission to run this ingestion test.'
            );
        }


        check_admin_referer(
            'bp_run_amrod_product_ingestion_test'
        );


        try {

            $result = bp_commerce()
                ->syncManager()
                ->dispatch(
                    'amrod',
                    'products',
                    [
                        'job_type' => 'manual',

                        'triggered_by' =>
                            'admin_ingestion_test',
                    ]
                );


            $query_args = [

                'page' =>
                    'blackprint-sync-ingestion-test',

                'bp_sync_test' =>
                    $result->success()
                        ? 'success'
                        : 'failed',

                'snapshot_uuid' =>
                    $result->snapshotId() ?? '',

            ];


            if ($result->hasErrors()) {

                $query_args['errors'] = implode(
                    ' | ',
                    $result->errors()
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Preserve Job UUID When Available
            |--------------------------------------------------------------------------
            |
            | The SyncResult currently carries metadata generated
            | by the Sync Engine. When job_uuid is available there,
            | expose it on the test page for end-to-end verification.
            |
            */

            $metadata = $result->metadata();

            if (
                isset($metadata['job_uuid'])
                && is_string(
                    $metadata['job_uuid']
                )
            ) {
                $query_args['job_uuid'] =
                    $metadata['job_uuid'];
            }


            $redirect = add_query_arg(
                $query_args,
                admin_url(
                    'admin.php'
                )
            );

        } catch (\Throwable $exception) {

            $redirect = add_query_arg(
                [

                    'page' =>
                        'blackprint-sync-ingestion-test',

                    'bp_sync_test' =>
                        'exception',

                    'errors' =>
                        $exception->getMessage(),

                ],
                admin_url(
                    'admin.php'
                )
            );
        }


        wp_safe_redirect(
            $redirect
        );

        exit;
    }

    /**
 * Verify the integrity of an immutable snapshot.
 *
 * This action is strictly read-only.
 *
 * It:
 *
 * - Loads immutable snapshot metadata.
 * - Restores the immutable raw payload.
 * - Verifies the record count.
 * - Recalculates the SHA-256 checksum.
 * - Does not call the supplier API.
 * - Does not modify the database.
 * - Does not modify WooCommerce.
 */
public function verify_snapshot_integrity(): void
{
    if (
        ! current_user_can(
            'manage_woocommerce'
        )
    ) {
        wp_die(
            'You do not have permission to verify snapshot integrity.'
        );
    }

    check_admin_referer(
        'bp_verify_snapshot_integrity'
    );

    $snapshotUuid = isset(
        $_POST['snapshot_uuid']
    )
        ? sanitize_text_field(
            wp_unslash(
                $_POST['snapshot_uuid']
            )
        )
        : '';

    if ($snapshotUuid === '') {

        $redirect = add_query_arg(
            [
                'page' =>
                    'blackprint-sync-ingestion-test',

                'bp_integrity' =>
                    'invalid',

                'errors' =>
                    'A Snapshot UUID is required.',
            ],
            admin_url(
                'admin.php'
            )
        );

        wp_safe_redirect(
            $redirect
        );

        exit;
    }

    try {

        $result = bp_commerce()
            ->syncManager()
            ->verifySnapshot(
                $snapshotUuid
            );

        $queryArgs = [

            'page' =>
                'blackprint-sync-ingestion-test',

            'bp_integrity' =>
                $result['success']
                    ? 'success'
                    : 'failed',

            'snapshot_uuid' =>
                $snapshotUuid,

            'snapshot_found' =>
                $result['snapshot_found']
                    ? '1'
                    : '0',

            'payload_found' =>
                $result['payload_found']
                    ? '1'
                    : '0',

            'records_expected' =>
                $result['records_expected'] ?? '',

            'records_actual' =>
                $result['records_actual'] ?? '',

            'records_valid' =>
                $result['records_valid']
                    ? '1'
                    : '0',

            'checksum_expected' =>
                $result['checksum_expected'] ?? '',

            'checksum_actual' =>
                $result['checksum_actual'] ?? '',

            'checksum_valid' =>
                $result['checksum_valid']
                    ? '1'
                    : '0',

        ];

        if (! empty($result['errors'])) {

            $queryArgs['integrity_errors'] = implode(
                ' | ',
                $result['errors']
            );
        }

        $redirect = add_query_arg(
            $queryArgs,
            admin_url(
                'admin.php'
            )
        );

    } catch (\Throwable $exception) {

        $redirect = add_query_arg(
            [
                'page' =>
                    'blackprint-sync-ingestion-test',

                'bp_integrity' =>
                    'exception',

                'snapshot_uuid' =>
                    $snapshotUuid,

                'integrity_errors' =>
                    $exception->getMessage(),

            ],
            admin_url(
                'admin.php'
            )
        );
    }

    wp_safe_redirect(
        $redirect
    );

    exit;
}

/**
 * Run the snapshot normalization verification test.
 *
 * This action:
 *
 * - Requires manage_woocommerce capability.
 * - Verifies the admin nonce.
 * - Loads an existing immutable snapshot.
 * - Restores its immutable payload.
 * - Resolves the supplier canonical normalizer.
 * - Normalizes all supplier records.
 * - Inspects the resulting canonical product collection.
 * - Does not persist canonical products.
 * - Does not modify WooCommerce.
 */
public function test_snapshot_normalization(): void
{
    if (
        ! current_user_can(
            'manage_woocommerce'
        )
    ) {
        wp_die(
            'You do not have permission to run this normalization test.'
        );
    }

    check_admin_referer(
        'bp_test_snapshot_normalization'
    );


    /*
    |--------------------------------------------------------------------------
    | Existing Verified Snapshot
    |--------------------------------------------------------------------------
    */

    $snapshotUuid =
        'e1feb722-4844-4561-bb22-a199a57522d9';


    /*
    |--------------------------------------------------------------------------
    | Execute Normalization
    |--------------------------------------------------------------------------
    */

    try {

        $result = bp_commerce()
            ->normalization()
            ->normalize(
                $snapshotUuid
            );


        /*
        |--------------------------------------------------------------------------
        | Extract Result
        |--------------------------------------------------------------------------
        */

        $sourceRecords =
            $result->sourceRecords();

        $normalized =
            $result->normalized();

        $skipped =
            $result->skipped();

        $failed =
            $result->failed();

        $errors =
            $result->errors();

        $metadata =
            $result->metadata();

        $products =
            $result->products();


        /*
        |--------------------------------------------------------------------------
        | Coverage Statistics
        |--------------------------------------------------------------------------
        */

        $productsWithIdentity = 0;

        $productsWithCategories = 0;

        $productsWithImages = 0;

        $productsWithColourImages = 0;

        $productsWithVariants = 0;

        $productsWithBrandingTemplates = 0;

        $productsWithBrandingGuide = 0;

        $productsWithRelationships = 0;


        /*
        |--------------------------------------------------------------------------
        | Identity / Variant Analysis
        |--------------------------------------------------------------------------
        */

        $seenSupplierProductIds = [];

        $duplicateSupplierProductIds = [];

        $seenVariantFullCodes = [];

        $duplicateVariantFullCodes = [];

        $productsWithSingleVariant = 0;

        $productsWithMultipleVariants = 0;

        $missingSupplierProductIds = 0;

        $missingSupplierProductCodes = 0;

        $missingVariantSimpleCodes = 0;

        $missingVariantFullCodes = 0;

        $supplierProductIdSimpleCodeMismatches = [];

        $supplierProductCodeSimpleCodeMismatches = [];

        $variantSimpleCodeMismatches = [];

        $decoupledProducts = 0;

        $decoupledWithSingleVariant = 0;

        $decoupledWithMultipleVariants = 0;

$decoupledWithNoVariants = 0;

$invalidDecoupledFlags = 0;

/*
|--------------------------------------------------------------------------
| Legacy WooCommerce Product Reconciliation
|--------------------------------------------------------------------------
|
| Read-only reconciliation of the existing WooCommerce catalogue against
| the canonical BlackPrint product and variant identities.
|
| This does NOT:
| - write ownership metadata
| - modify products
| - modify SKUs
| - modify images
| - create products
| - update products
|
*/

$legacyCanonicalProductIdLookup   = [];
$legacyCanonicalProductCodeLookup = [];
$legacyCanonicalVariantLookup     = [];

$legacyWooCommerceProducts = [];
$legacyWooCommerceSkuIndex = [];

/*
|--------------------------------------------------------------------------
| Build canonical identity lookups.
|--------------------------------------------------------------------------
*/

if ($products !== null) {
    foreach ($products->all() as $product) {
        $data     = $product->toArray();
        $identity = $data['identity'] ?? [];
        $variants = $data['variant']['items'] ?? [];

        $supplierProductId =
            $identity['supplier_product_id'] ?? null;

        $supplierProductCode =
            $identity['supplier_product_code'] ?? null;

        if (
            !is_string($supplierProductId) ||
            $supplierProductId === ''
        ) {
            continue;
        }

        $canonicalIdentity = [
            'supplier_product_id'   => $supplierProductId,
            'supplier_product_code' => (
                is_string($supplierProductCode) &&
                $supplierProductCode !== ''
            )
                ? $supplierProductCode
                : null,
            'variant_count' => is_array($variants)
                ? count($variants)
                : 0,
        ];

        /*
         * Product ID lookup.
         */
        $legacyCanonicalProductIdLookup[
            $supplierProductId
        ][$supplierProductId] = $canonicalIdentity;

        /*
         * Product code lookup.
         */
        if (
            is_string($supplierProductCode) &&
            $supplierProductCode !== ''
        ) {
            $legacyCanonicalProductCodeLookup[
                $supplierProductCode
            ][$supplierProductId] = $canonicalIdentity;
        }

        /*
         * Variant fullCode lookup.
         */
        if (is_array($variants)) {
            foreach ($variants as $variant) {
                $fullCode = $variant['fullCode'] ?? null;

                if (
                    !is_string($fullCode) ||
                    $fullCode === ''
                ) {
                    continue;
                }

                $legacyCanonicalVariantLookup[
                    $fullCode
                ][$supplierProductId] = [
                    ...$canonicalIdentity,
                    'full_code' => $fullCode,
                ];
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Read all published WooCommerce products.
|--------------------------------------------------------------------------
*/

$legacyWooCommerceProductIds = get_posts([
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
]);

foreach ($legacyWooCommerceProductIds as $productId) {
    $productId = (int) $productId;

    $sku = get_post_meta(
        $productId,
        '_sku',
        true
    );

    if (!is_string($sku)) {
        $sku = '';
    }

    $sku = trim($sku);

    $wcProduct = wc_get_product($productId);

    $productType = $wcProduct
        ? $wcProduct->get_type()
        : 'unknown';

    $legacyWooCommerceProducts[$productId] = [
        'product_id'   => $productId,
        'sku'          => $sku,
        'product_type' => $productType,
        'title'        => get_the_title($productId),
    ];

    if ($sku !== '') {
        $legacyWooCommerceSkuIndex[$sku][] = $productId;
    }
}

/*
|--------------------------------------------------------------------------
| Reconcile WooCommerce products against canonical identities.
|--------------------------------------------------------------------------
*/

$legacyDeterministicMatches        = [];
$legacyAmbiguousMatches            = [];
$legacyUnmatchedProducts            = [];
$legacyMatchedByProductIdentity     = [];
$legacyMatchedByVariantFullCode     = [];
$legacyDuplicateWooCommerceSkus     = [];

foreach ($legacyWooCommerceProducts as $productId => $wooProduct) {
    $sku = $wooProduct['sku'];

    if ($sku === '') {
        $legacyUnmatchedProducts[$productId] = [
            ...$wooProduct,
            'reason' => 'missing_sku',
        ];

        continue;
    }

    /*
     * A duplicate WooCommerce SKU cannot be adopted
     * deterministically.
     */
    $wooSkuProductIds =
        $legacyWooCommerceSkuIndex[$sku] ?? [];

    $isDuplicateWooCommerceSku =
        count($wooSkuProductIds) > 1;

    if ($isDuplicateWooCommerceSku) {
        $legacyDuplicateWooCommerceSkus[$sku] =
            $wooSkuProductIds;
    }

    /*
     * Collect canonical candidates.
     */
    $canonicalCandidates = [];

    /*
     * Match by supplier product ID.
     */
    if (
        isset(
            $legacyCanonicalProductIdLookup[$sku]
        )
    ) {
        foreach (
            $legacyCanonicalProductIdLookup[$sku]
            as $supplierProductId => $identity
        ) {
            $canonicalCandidates[$supplierProductId] ??= [
                'identity' => $identity,
                'methods'  => [],
            ];

            $canonicalCandidates[
                $supplierProductId
            ]['methods'][] = 'supplier_product_id';
        }
    }

    /*
     * Match by supplier product code.
     */
    if (
        isset(
            $legacyCanonicalProductCodeLookup[$sku]
        )
    ) {
        foreach (
            $legacyCanonicalProductCodeLookup[$sku]
            as $supplierProductId => $identity
        ) {
            $canonicalCandidates[$supplierProductId] ??= [
                'identity' => $identity,
                'methods'  => [],
            ];

            $canonicalCandidates[
                $supplierProductId
            ]['methods'][] = 'supplier_product_code';
        }
    }

    /*
     * Match by variant fullCode.
     */
    if (
        isset(
            $legacyCanonicalVariantLookup[$sku]
        )
    ) {
        foreach (
            $legacyCanonicalVariantLookup[$sku]
            as $supplierProductId => $identity
        ) {
            $canonicalCandidates[$supplierProductId] ??= [
                'identity' => $identity,
                'methods'  => [],
            ];

            $canonicalCandidates[
                $supplierProductId
            ]['methods'][] = 'variant_full_code';
        }
    }

    /*
     * No canonical candidate.
     */
    if ($canonicalCandidates === []) {
        $legacyUnmatchedProducts[$productId] = [
            ...$wooProduct,
            'reason' => 'no_canonical_match',
        ];

        continue;
    }

    /*
     * More than one canonical product candidate means
     * the relationship is ambiguous.
     */
    if (
        count($canonicalCandidates) > 1 ||
        $isDuplicateWooCommerceSku
    ) {
        $legacyAmbiguousMatches[$productId] = [
            ...$wooProduct,
            'canonical_candidates' => $canonicalCandidates,
            'wooCommerce_sku_ids'  => $wooSkuProductIds,
        ];

        continue;
    }

    /*
     * Exactly one canonical product candidate.
     */
    $candidate = reset($canonicalCandidates);

    $canonicalIdentity = $candidate['identity'];
    $methods            = array_values(
        array_unique($candidate['methods'])
    );

    $match = [
        ...$wooProduct,
        'canonical' => $canonicalIdentity,
        'methods'   => $methods,
    ];

    $legacyDeterministicMatches[$productId] = $match;

    if (
        in_array(
            'supplier_product_id',
            $methods,
            true
        ) ||
        in_array(
            'supplier_product_code',
            $methods,
            true
        )
    ) {
        $legacyMatchedByProductIdentity[$productId] =
            $match;
    }

    if (
        in_array(
            'variant_full_code',
            $methods,
            true
        )
    ) {
        $legacyMatchedByVariantFullCode[$productId] =
            $match;
    }
}

/*
|--------------------------------------------------------------------------
| Reconciliation samples.
|--------------------------------------------------------------------------
*/

$legacyDeterministicSample = array_slice(
    array_values($legacyDeterministicMatches),
    0,
    20
);

$legacyAmbiguousSample = array_slice(
    array_values($legacyAmbiguousMatches),
    0,
    20
);

$legacyUnmatchedSample = array_slice(
    array_values($legacyUnmatchedProducts),
    0,
    20
);

/*
|--------------------------------------------------------------------------
| Legacy WooCommerce Product Reconciliation Report.
|--------------------------------------------------------------------------
*/

echo '<h3>Legacy WooCommerce Product Reconciliation</h3>';

echo '<pre>';

echo "LEGACY WOOCOMMERCE PRODUCT RECONCILIATION\n";
echo "----------------------------------------------------------\n";

echo "Published WooCommerce products: "
    . count($legacyWooCommerceProducts)
    . "\n";

echo "Products with SKU: "
    . count(
        array_filter(
            $legacyWooCommerceProducts,
            static fn(array $product): bool =>
                $product['sku'] !== ''
        )
    )
    . "\n";

echo "Products without SKU: "
    . count(
        array_filter(
            $legacyWooCommerceProducts,
            static fn(array $product): bool =>
                $product['sku'] === ''
        )
    )
    . "\n";

echo "Deterministic canonical matches: "
    . count($legacyDeterministicMatches)
    . "\n";

echo "Matched by product identity: "
    . count($legacyMatchedByProductIdentity)
    . "\n";

echo "Matched by variant fullCode: "
    . count($legacyMatchedByVariantFullCode)
    . "\n";

echo "Ambiguous matches: "
    . count($legacyAmbiguousMatches)
    . "\n";

echo "Unmatched products: "
    . count($legacyUnmatchedProducts)
    . "\n";

echo "Duplicate WooCommerce SKUs: "
    . count($legacyDuplicateWooCommerceSkus)
    . "\n";

echo "\n";

echo "DETERMINISTIC MATCH SAMPLE\n";
echo "----------------------------------------------------------\n";

foreach ($legacyDeterministicSample as $match) {
    echo "WooCommerce Product ID: "
        . $match['product_id']
        . "\n";

    echo "Type: "
        . $match['product_type']
        . "\n";

    echo "SKU: "
        . $match['sku']
        . "\n";

    echo "Title: "
        . $match['title']
        . "\n";

    echo "Match method(s): "
        . implode(', ', $match['methods'])
        . "\n";

    echo "Canonical supplier_product_id: "
        . (
            $match['canonical']['supplier_product_id']
            ?? 'N/A'
        )
        . "\n";

    echo "Canonical supplier_product_code: "
        . (
            $match['canonical']['supplier_product_code']
            ?? 'N/A'
        )
        . "\n";

    echo "Canonical variant count: "
        . (
            $match['canonical']['variant_count']
            ?? 0
        )
        . "\n";

    echo "\n";
}

echo "AMBIGUOUS MATCH SAMPLE\n";
echo "----------------------------------------------------------\n";

foreach ($legacyAmbiguousSample as $match) {
    echo "WooCommerce Product ID: "
        . $match['product_id']
        . "\n";

    echo "Type: "
        . $match['product_type']
        . "\n";

    echo "SKU: "
        . $match['sku']
        . "\n";

    echo "Title: "
        . $match['title']
        . "\n";

    echo "Canonical candidates:\n";

    foreach (
        $match['canonical_candidates']
        as $candidate
    ) {
        $identity = $candidate['identity'];

        echo "  - "
            . (
                $identity['supplier_product_id']
                ?? 'N/A'
            )
            . " / "
            . (
                $identity['supplier_product_code']
                ?? 'N/A'
            )
            . " ["
            . implode(
                ', ',
                $candidate['methods']
            )
            . "]\n";
    }

    if (
        count($match['wooCommerce_sku_ids']) > 1
    ) {
        echo "WooCommerce products sharing SKU: "
            . implode(
                ', ',
                $match['wooCommerce_sku_ids']
            )
            . "\n";
    }

    echo "\n";
}

echo "UNMATCHED PRODUCT SAMPLE\n";
echo "----------------------------------------------------------\n";

foreach ($legacyUnmatchedSample as $match) {
    echo "WooCommerce Product ID: "
        . $match['product_id']
        . "\n";

    echo "Type: "
        . $match['product_type']
        . "\n";

    echo "SKU: "
        . (
            $match['sku'] !== ''
                ? $match['sku']
                : '[NO SKU]'
        )
        . "\n";

    echo "Title: "
        . $match['title']
        . "\n";

    echo "Reason: "
        . $match['reason']
        . "\n";

    echo "\n";
}

echo '</pre>';

/*
|--------------------------------------------------------------------------
| WooCommerce Variant SKU Reconciliation
|--------------------------------------------------------------------------
*/

$canonicalVariantFullCodes = [];

$wooCommerceVariantSkus = [];

$matchedVariantSkus = [];

$duplicateWooCommerceVariantSkus = [];


        /*
        |--------------------------------------------------------------------------
        | Inspect Canonical Products
        |--------------------------------------------------------------------------
        */

        if ($products !== null) {

            foreach (
                $products->all()
                as $product
            ) {

                $data =
                    $product->toArray();


                /*
                |--------------------------------------------------------------------------
                | Identity
                |--------------------------------------------------------------------------
                */

                $identity =
                    $data['identity']
                    ?? [];

                $supplierProductId =
                    $identity['supplier_product_id']
                    ?? null;

                $supplierProductCode =
                    $identity['supplier_product_code']
                    ?? null;


                if (
                    is_string(
                        $supplierProductId
                    )
                    && $supplierProductId !== ''
                ) {

                    $productsWithIdentity++;

                    if (
                        isset(
                            $seenSupplierProductIds[
                                $supplierProductId
                            ]
                        )
                    ) {

                        $duplicateSupplierProductIds[] =
                            $supplierProductId;

                    } else {

                        $seenSupplierProductIds[
                            $supplierProductId
                        ] = true;
                    }

                } else {

                    $missingSupplierProductIds++;
                }


                if (
                    ! is_string(
                        $supplierProductCode
                    )
                    || $supplierProductCode === ''
                ) {

                    $missingSupplierProductCodes++;
                }


               /*
|--------------------------------------------------------------------------
| Hierarchy
|--------------------------------------------------------------------------
*/

$hierarchy =
    $data['hierarchy']
    ?? [];


                /*
                |--------------------------------------------------------------------------
                | Classification
                |--------------------------------------------------------------------------
                */

                $classification =
                    $data['classification']
                    ?? [];

                $categories =
                    $classification['categories']
                    ?? [];

                if (
                    is_array($categories)
                    && ! empty($categories)
                ) {

                    $productsWithCategories++;
                }


                /*
                |--------------------------------------------------------------------------
                | Media
                |--------------------------------------------------------------------------
                */

                $media =
                    $data['media']
                    ?? [];

                $images =
                    $media['images']
                    ?? [];

                $colourImages =
                    $media['colour_images']
                    ?? [];

                if (
                    is_array($images)
                    && ! empty($images)
                ) {

                    $productsWithImages++;
                }

                if (
                    is_array($colourImages)
                    && ! empty($colourImages)
                ) {

                    $productsWithColourImages++;
                }


                /*
                |--------------------------------------------------------------------------
                | Variants
                |--------------------------------------------------------------------------
                */

                $variant =
                    $data['variant']
                    ?? [];

                $variants =
                    $variant['items']
                    ?? [];

                if (
                    is_array($variants)
                    && ! empty($variants)
                ) {

                    $productsWithVariants++;

                    $variantCount =
                        count($variants);

                    if ($variantCount === 1) {

                        $productsWithSingleVariant++;

                    } elseif ($variantCount > 1) {

                        $productsWithMultipleVariants++;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Variant Identity Analysis
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $variants
                        as $variantItem
                    ) {

                        if (
                            ! is_array(
                                $variantItem
                            )
                        ) {
                            continue;
                        }


                        $simpleCode =
                            $variantItem['simpleCode']
                            ?? null;

                        $fullCode =
                            $variantItem['fullCode']
                            ?? null;


                        /*
                        |--------------------------------------------------------------------------
                        | Missing Variant Codes
                        |--------------------------------------------------------------------------
                        */

                        if (
                            ! is_string(
                                $simpleCode
                            )
                            || $simpleCode === ''
                        ) {

                            $missingVariantSimpleCodes++;

                        } else {

                            /*
                            |--------------------------------------------------------------------------
                            | Variant simpleCode ↔ Product Identity
                            |--------------------------------------------------------------------------
                            |
                            | For a canonical product, the supplier
                            | product ID and supplier product code
                            | should identify the same base product
                            | represented by variant simpleCode.
                            |
                            */

                            if (
                                is_string(
                                    $supplierProductId
                                )
                                && $supplierProductId !== ''
                                && $simpleCode !==
                                    $supplierProductId
                            ) {

                                $supplierProductIdSimpleCodeMismatches[] =
                                    [
                                        'supplier_product_id' =>
                                            $supplierProductId,
                                        'simpleCode' =>
                                            $simpleCode,
                                    ];
                            }


                            if (
                                is_string(
                                    $supplierProductCode
                                )
                                && $supplierProductCode !== ''
                                && $simpleCode !==
                                    $supplierProductCode
                            ) {

                                $supplierProductCodeSimpleCodeMismatches[] =
                                    [
                                        'supplier_product_code' =>
                                            $supplierProductCode,
                                        'simpleCode' =>
                                            $simpleCode,
                                    ];
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Variant simpleCode Consistency
                            |--------------------------------------------------------------------------
                            */

                            if (
                                $simpleCode !==
                                (
                                    is_string(
                                        $supplierProductId
                                    )
                                    ? $supplierProductId
                                    : ''
                                )
                            ) {

                                $variantSimpleCodeMismatches[] =
                                    [
                                        'supplier_product_id' =>
                                            $supplierProductId,
                                        'simpleCode' =>
                                            $simpleCode,
                                        'fullCode' =>
                                            $fullCode,
                                    ];
                            }
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | fullCode
                        |--------------------------------------------------------------------------
                        */

                        if (
                            ! is_string(
                                $fullCode
                            )
                            || $fullCode === ''
                        ) {

                            $missingVariantFullCodes++;

                            } else {

                                $canonicalVariantFullCodes[
                                    $fullCode
                                ] = true;

                            if (
                                isset(
                                    $seenVariantFullCodes[
                                        $fullCode
                                    ]
                                )
                            ) {

                                $duplicateVariantFullCodes[] =
                                    $fullCode;

                            } else {

                                $seenVariantFullCodes[
                                    $fullCode
                                ] = true;
                            }
                        }
                    }

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Product Without Variants
                    |--------------------------------------------------------------------------
                    */

                    $missingVariantSimpleCodes++;

                    $missingVariantFullCodes++;
                }

                /*
|--------------------------------------------------------------------------
| Decoupled Product Analysis
|--------------------------------------------------------------------------
*/

$decoupled =
    $hierarchy['decoupled']
    ?? null;

if (
    $decoupled !== null
    && ! is_bool($decoupled)
) {

    $invalidDecoupledFlags++;

} elseif ($decoupled === true) {

    $decoupledProducts++;

    if (
        ! is_array($variants)
        || empty($variants)
    ) {

        $decoupledWithNoVariants++;

    } elseif (count($variants) === 1) {

        $decoupledWithSingleVariant++;

    } else {

        $decoupledWithMultipleVariants++;
    }
}



                /*
                |--------------------------------------------------------------------------
                | Branding
                |--------------------------------------------------------------------------
                */

                $branding =
                    $data['branding']
                    ?? [];

                $templates =
                    $branding['templates']
                    ?? [];

                $brandingGuide =
                    $branding['full_branding_guide']
                    ?? null;

                if (
                    is_array($templates)
                    && ! empty($templates)
                ) {

                    $productsWithBrandingTemplates++;
                }

                if (
                    is_string($brandingGuide)
                    && $brandingGuide !== ''
                ) {

                    $productsWithBrandingGuide++;
                }


                /*
                |--------------------------------------------------------------------------
                | Relationships
                |--------------------------------------------------------------------------
                */

                $relationships =
                    $data['relationships']
                    ?? [];

                foreach (
                    [
                        'companion_codes',
                        'related_codes',
                        'matching_codes',
                        'grouping_codes',
                    ] as $relationshipType
                ) {

                    $values =
                        $relationships[
                            $relationshipType
                        ]
                        ?? [];

                    if (
                        is_array($values)
                        && ! empty($values)
                    ) {

                        $productsWithRelationships++;

                        break;
                    }
                }
            }
        }

/*
|--------------------------------------------------------------------------
| Legacy WooCommerce Product Family Reconciliation
|--------------------------------------------------------------------------
|
| Read-only analysis of unmatched legacy WooCommerce products.
|
| This determines whether unmatched WooCommerce products can still be
| associated with a canonical BlackPrint product through their child
| variation fullCodes.
|
| This does NOT:
| - write ownership metadata
| - modify products
| - modify SKUs
| - modify images
| - create products
| - update products
|
*/

$legacyFamilyDeterministicMatches = [];
$legacyFamilyCandidateMatches     = [];
$legacyFamilyNoCanonicalEvidence  = [];

$legacyFamilyStats = [
    'unmatched_products'                 => 0,
    'unmatched_variable_parents'         => 0,
    'unmatched_simple_products'          => 0,
    'variable_with_matched_variants'     => 0,
    'variable_with_no_matched_variants'  => 0,
    'simple_with_variant_match'          => 0,
    'canonical_families_with_evidence'   => 0,
];

/*
|--------------------------------------------------------------------------
| Analyse every unmatched WooCommerce product.
|--------------------------------------------------------------------------
*/

foreach ($legacyUnmatchedProducts as $wooProductId => $wooProduct) {
    $legacyFamilyStats['unmatched_products']++;

    $productType = $wooProduct['product_type'];
    $parentSku   = $wooProduct['sku'];

    if ($productType === 'variable') {
        $legacyFamilyStats['unmatched_variable_parents']++;
    } elseif ($productType === 'simple') {
        $legacyFamilyStats['unmatched_simple_products']++;
    }

    /*
     * Simple products do not have child variations.
     *
     * A simple product can still be related to a canonical variant if
     * its own SKU is a canonical fullCode. That possibility was already
     * checked by the product reconciliation above. Since this product
     * reached the unmatched set, there is no direct canonical evidence.
     */
    if ($productType !== 'variable') {
        $legacyFamilyNoCanonicalEvidence[$wooProductId] = [
            ...$wooProduct,
            'evidence_type' => 'no_child_variations',
            'canonical_candidates' => [],
        ];

        continue;
    }

    /*
     * Find published WooCommerce child variations.
     */
    $variationIds = get_posts([
        'post_type'      => 'product_variation',
        'post_status'    => 'publish',
        'post_parent'    => (int) $wooProductId,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    $variationEvidence = [];
    $canonicalFamilies = [];

    foreach ($variationIds as $variationId) {
        $variationId = (int) $variationId;

        $variationSku = get_post_meta(
            $variationId,
            '_sku',
            true
        );

        if (!is_string($variationSku)) {
            continue;
        }

        $variationSku = trim($variationSku);

        if ($variationSku === '') {
            continue;
        }

        /*
         * Exact canonical fullCode lookup.
         */
        if (
            isset(
                $legacyCanonicalVariantLookup[
                    $variationSku
                ]
            )
        ) {
            foreach (
                $legacyCanonicalVariantLookup[
                    $variationSku
                ] as $supplierProductId => $canonicalVariant
            ) {
                $canonicalFamilies[
                    $supplierProductId
                ]['identity'] = [
                    'supplier_product_id' =>
                        $canonicalVariant[
                            'supplier_product_id'
                        ],

                    'supplier_product_code' =>
                        $canonicalVariant[
                            'supplier_product_code'
                        ],

                    'variant_count' =>
                        $canonicalVariant[
                            'variant_count'
                        ],
                ];

                $canonicalFamilies[
                    $supplierProductId
                ]['variants'][] = [
                    'full_code' => $variationSku,
                    'variation_id' => $variationId,
                ];
            }
        }

        $variationEvidence[] = [
            'variation_id' => $variationId,
            'sku'          => $variationSku,
            'canonical'    => isset(
                $legacyCanonicalVariantLookup[
                    $variationSku
                ]
            ),
        ];
    }

    /*
     * No child variant provides canonical evidence.
     */
    if ($canonicalFamilies === []) {
        $legacyFamilyStats[
            'variable_with_no_matched_variants'
        ]++;

        $legacyFamilyNoCanonicalEvidence[$wooProductId] = [
            ...$wooProduct,
            'evidence_type' => 'no_matching_child_variants',
            'variation_count' => count($variationEvidence),
            'variations' => $variationEvidence,
            'canonical_candidates' => [],
        ];

        continue;
    }

    $legacyFamilyStats[
        'variable_with_matched_variants'
    ]++;

    /*
     * Exactly one canonical family has matching variants.
     */
    if (count($canonicalFamilies) === 1) {
        $canonicalFamily = reset($canonicalFamilies);

        $legacyFamilyDeterministicMatches[$wooProductId] = [
            ...$wooProduct,
            'evidence_type' => 'child_variant_full_code',
            'variation_count' => count($variationEvidence),
            'matched_variation_count' => count(
                $canonicalFamily['variants']
            ),
            'canonical' => $canonicalFamily['identity'],
            'matched_variants' => $canonicalFamily['variants'],
            'all_variations' => $variationEvidence,
        ];

        continue;
    }

    /*
     * Multiple canonical families are represented by the child SKUs.
     *
     * This is NOT safe for automatic adoption.
     */
    $legacyFamilyCandidateMatches[$wooProductId] = [
        ...$wooProduct,
        'evidence_type' => 'multiple_canonical_families',
        'variation_count' => count($variationEvidence),
        'canonical_candidates' => $canonicalFamilies,
        'all_variations' => $variationEvidence,
    ];
}

/*
|--------------------------------------------------------------------------
| Count canonical families represented by unmatched WooCommerce
| variation evidence.
|--------------------------------------------------------------------------
*/

$legacyCanonicalFamiliesWithEvidence = [];

foreach (
    $legacyFamilyDeterministicMatches
    as $familyMatch
) {
    $canonicalId =
        $familyMatch['canonical']['supplier_product_id']
        ?? null;

    if (
        is_string($canonicalId) &&
        $canonicalId !== ''
    ) {
        $legacyCanonicalFamiliesWithEvidence[
            $canonicalId
        ] = true;
    }
}

foreach (
    $legacyFamilyCandidateMatches
    as $familyMatch
) {
    foreach (
        $familyMatch['canonical_candidates']
        as $candidate
    ) {
        $canonicalId =
            $candidate['identity'][
                'supplier_product_id'
            ] ?? null;

        if (
            is_string($canonicalId) &&
            $canonicalId !== ''
        ) {
            $legacyCanonicalFamiliesWithEvidence[
                $canonicalId
            ] = true;
        }
    }
}

$legacyFamilyStats[
    'canonical_families_with_evidence'
] = count(
    $legacyCanonicalFamiliesWithEvidence
);

/*
|--------------------------------------------------------------------------
| Family reconciliation samples.
|--------------------------------------------------------------------------
*/

$legacyFamilyDeterministicSample = array_slice(
    array_values($legacyFamilyDeterministicMatches),
    0,
    20
);

$legacyFamilyCandidateSample = array_slice(
    array_values($legacyFamilyCandidateMatches),
    0,
    20
);

$legacyFamilyNoEvidenceSample = array_slice(
    array_values($legacyFamilyNoCanonicalEvidence),
    0,
    20
);

/*
|--------------------------------------------------------------------------
| Legacy WooCommerce Product Family Reconciliation Report.
|--------------------------------------------------------------------------
*/

echo '<h3>Legacy WooCommerce Product Family Reconciliation</h3>';

echo '<pre>';

echo "LEGACY WOOCOMMERCE PRODUCT FAMILY RECONCILIATION\n";
echo "----------------------------------------------------------\n";

echo "Unmatched WooCommerce products: "
    . $legacyFamilyStats['unmatched_products']
    . "\n";

echo "Unmatched variable parents: "
    . $legacyFamilyStats['unmatched_variable_parents']
    . "\n";

echo "Unmatched simple products: "
    . $legacyFamilyStats['unmatched_simple_products']
    . "\n";

echo "Variable parents with matched child variants: "
    . $legacyFamilyStats[
        'variable_with_matched_variants'
    ]
    . "\n";

echo "Variable parents with no matched child variants: "
    . $legacyFamilyStats[
        'variable_with_no_matched_variants'
    ]
    . "\n";

echo "Deterministic family matches: "
    . count($legacyFamilyDeterministicMatches)
    . "\n";

echo "Multiple canonical family candidates: "
    . count($legacyFamilyCandidateMatches)
    . "\n";

echo "No canonical family evidence: "
    . count($legacyFamilyNoCanonicalEvidence)
    . "\n";

echo "Canonical families represented by unmatched variants: "
    . $legacyFamilyStats[
        'canonical_families_with_evidence'
    ]
    . "\n";

echo "\n";

echo "DETERMINISTIC FAMILY MATCH SAMPLE\n";
echo "----------------------------------------------------------\n";

foreach (
    $legacyFamilyDeterministicSample
    as $familyMatch
) {
    echo "WooCommerce Parent ID: "
        . $familyMatch['product_id']
        . "\n";

    echo "Parent SKU: "
        . (
            $familyMatch['sku'] !== ''
                ? $familyMatch['sku']
                : '[NO SKU]'
        )
        . "\n";

    echo "Parent title: "
        . $familyMatch['title']
        . "\n";

    echo "WooCommerce variation count: "
        . $familyMatch['variation_count']
        . "\n";

    echo "Matched canonical variants: "
        . $familyMatch['matched_variation_count']
        . "\n";

    echo "Canonical supplier_product_id: "
        . (
            $familyMatch['canonical'][
                'supplier_product_id'
            ] ?? 'N/A'
        )
        . "\n";

    echo "Canonical supplier_product_code: "
        . (
            $familyMatch['canonical'][
                'supplier_product_code'
            ] ?? 'N/A'
        )
        . "\n";

    echo "Canonical variant count: "
        . (
            $familyMatch['canonical'][
                'variant_count'
            ] ?? 0
        )
        . "\n";

    echo "Matched canonical fullCodes:\n";

    foreach (
        $familyMatch['matched_variants']
        as $matchedVariant
    ) {
        echo "  - "
            . $matchedVariant['full_code']
            . " (Variation ID "
            . $matchedVariant['variation_id']
            . ")\n";
    }

    echo "\n";
}

echo "MULTIPLE CANONICAL FAMILY CANDIDATES\n";
echo "----------------------------------------------------------\n";

foreach (
    $legacyFamilyCandidateSample
    as $familyMatch
) {
    echo "WooCommerce Parent ID: "
        . $familyMatch['product_id']
        . "\n";

    echo "Parent SKU: "
        . (
            $familyMatch['sku'] !== ''
                ? $familyMatch['sku']
                : '[NO SKU]'
        )
        . "\n";

    echo "Parent title: "
        . $familyMatch['title']
        . "\n";

    echo "Canonical candidates:\n";

    foreach (
        $familyMatch['canonical_candidates']
        as $candidate
    ) {
        echo "  - "
            . (
                $candidate['identity'][
                    'supplier_product_id'
                ] ?? 'N/A'
            )
            . " / "
            . (
                $candidate['identity'][
                    'supplier_product_code'
                ] ?? 'N/A'
            )
            . " | matched variants: "
            . count($candidate['variants'])
            . "\n";
    }

    echo "\n";
}

echo "NO CANONICAL FAMILY EVIDENCE SAMPLE\n";
echo "----------------------------------------------------------\n";

foreach (
    $legacyFamilyNoEvidenceSample
    as $familyMatch
) {
    echo "WooCommerce Product ID: "
        . $familyMatch['product_id']
        . "\n";

    echo "Type: "
        . $familyMatch['product_type']
        . "\n";

    echo "SKU: "
        . (
            $familyMatch['sku'] !== ''
                ? $familyMatch['sku']
                : '[NO SKU]'
        )
        . "\n";

    echo "Title: "
        . $familyMatch['title']
        . "\n";

    echo "Evidence type: "
        . $familyMatch['evidence_type']
        . "\n";

    if (
        isset($familyMatch['variation_count'])
    ) {
        echo "WooCommerce variation count: "
            . $familyMatch['variation_count']
            . "\n";
    }

    echo "\n";
}

echo '</pre>';

        /*
|--------------------------------------------------------------------------
| WooCommerce Variant SKU Reconciliation
|--------------------------------------------------------------------------
|
| This comparison runs once, after the complete canonical variant
| identity set has been built.
|
| It is intentionally read-only and does not modify WooCommerce.
|
*/

$wooCommerceVariationIds = get_posts([
    'post_type'      => 'product_variation',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
]);

foreach ($wooCommerceVariationIds as $variationId) {

    $sku = get_post_meta(
        $variationId,
        '_sku',
        true
    );

    if (
        ! is_string($sku)
        || $sku === ''
    ) {
        continue;
    }

    if (
        isset(
            $wooCommerceVariantSkus[$sku]
        )
    ) {

        $duplicateWooCommerceVariantSkus[] =
            $sku;

        continue;
    }

    $wooCommerceVariantSkus[$sku] = true;

    if (
        isset(
            $canonicalVariantFullCodes[$sku]
        )
    ) {

        $matchedVariantSkus[$sku] = true;
    }
}


        /*
        |--------------------------------------------------------------------------
        | Determine Verification Status
        |--------------------------------------------------------------------------
        */

        $status =
            $result->success()
            ? 'PASS'
            : 'FAILED';


        /*
        |--------------------------------------------------------------------------
        | Build Human-Readable Report
        |--------------------------------------------------------------------------
        */

        $output = [];


        $output[] =
            'BlackPrint OS — Snapshot Normalization Verification';

        $output[] =
            str_repeat(
                '=',
                58
            );

        $output[] = '';


        /*
        |--------------------------------------------------------------------------
        | Snapshot
        |--------------------------------------------------------------------------
        */

        $output[] =
            'SNAPSHOT';

        $output[] =
            str_repeat(
                '-',
                58
            );

        $output[] =
            'UUID: ' .
            (
                $metadata['snapshot_uuid']
                ?? $snapshotUuid
            );

        $output[] =
            'Supplier: ' .
            (
                $metadata['supplier']
                ?? 'unknown'
            );

        $output[] =
            'Resource: ' .
            (
                $metadata['resource']
                ?? 'unknown'
            );

        $output[] =
            'Snapshot records: ' .
            (
                $metadata['snapshot_records_count']
                ?? 'unknown'
            );

        $output[] = '';


        /*
        |--------------------------------------------------------------------------
        | Normalization
        |--------------------------------------------------------------------------
        */

        $output[] =
            'NORMALIZATION';

        $output[] =
            str_repeat(
                '-',
                58
            );

        $output[] =
            'Source records:       ' .
            $sourceRecords;

        $output[] =
            'Normalized:           ' .
            $normalized;

        $output[] =
            'Skipped:              ' .
            $skipped;

        $output[] =
            'Failed:               ' .
            $failed;

        $output[] =
            'Status:               ' .
            $status;

        $output[] = '';


        /*
        |--------------------------------------------------------------------------
        | Canonical Product Coverage
        |--------------------------------------------------------------------------
        */

        $output[] =
            'CANONICAL PRODUCT COVERAGE';

        $output[] =
            str_repeat(
                '-',
                58
            );

        $output[] =
            'Products with identity:           ' .
            $productsWithIdentity;

        $output[] =
            'Products with categories:         ' .
            $productsWithCategories;

        $output[] =
            'Products with images:             ' .
            $productsWithImages;

        $output[] =
            'Products with colour images:      ' .
            $productsWithColourImages;

        $output[] =
            'Products with variants:            ' .
            $productsWithVariants;

        $output[] =
            'Products with branding templates: ' .
            $productsWithBrandingTemplates;

        $output[] =
            'Products with branding guide:     ' .
            $productsWithBrandingGuide;

        $output[] =
            'Products with relationships:      ' .
            $productsWithRelationships;

        $output[] = '';


        /*
        |--------------------------------------------------------------------------
        | Identity Check
        |--------------------------------------------------------------------------
        */

        $output[] =
            'IDENTITY CHECK';

        $output[] =
            str_repeat(
                '-',
                58
            );

        $output[] =
            'Unique supplier product IDs:      ' .
            count(
                $seenSupplierProductIds
            );

        $output[] =
            'Duplicate supplier product IDs:   ' .
            count(
                array_unique(
                    $duplicateSupplierProductIds
                )
            );

        $output[] = '';


        /*
        |--------------------------------------------------------------------------
        | Step 3 — Product & Variant Identity Analysis
        |--------------------------------------------------------------------------
        */

        $output[] =
            'PRODUCT & VARIANT IDENTITY ANALYSIS';

        $output[] =
            str_repeat(
                '-',
                58
            );

        $output[] =
            'Products with single variant:     ' .
            $productsWithSingleVariant;

        $output[] =
            'Products with multiple variants:  ' .
            $productsWithMultipleVariants;

        $output[] =
            'Unique variant fullCodes:         ' .
            count(
                $seenVariantFullCodes
            );

        $output[] =
            'Duplicate variant fullCodes:      ' .
            count(
                array_unique(
                    $duplicateVariantFullCodes
                )
            );

        $output[] =
            'Missing supplier product IDs:     ' .
            $missingSupplierProductIds;

        $output[] =
            'Missing supplier product codes:   ' .
            $missingSupplierProductCodes;

        $output[] =
            'Missing variant simpleCodes:      ' .
            $missingVariantSimpleCodes;

        $output[] =
            'Missing variant fullCodes:        ' .
            $missingVariantFullCodes;

        $output[] =
            'ID ↔ simpleCode mismatches:       ' .
            count(
                $supplierProductIdSimpleCodeMismatches
            );

        $output[] =
            'Code ↔ simpleCode mismatches:     ' .
            count(
                $supplierProductCodeSimpleCodeMismatches
            );

        $output[] =
            'Variant simpleCode mismatches:    ' .
            count(
                $variantSimpleCodeMismatches
            );

        $output[] =
            'Decoupled products:               ' .
            $decoupledProducts;

        $output[] = '';


        /*
        |--------------------------------------------------------------------------
        | Identity Analysis Details
        |--------------------------------------------------------------------------
        */

        $hasIdentityDetails =
            ! empty(
                $duplicateSupplierProductIds
            )
            || ! empty(
                $duplicateVariantFullCodes
            )
            || ! empty(
                $supplierProductIdSimpleCodeMismatches
            )
            || ! empty(
                $supplierProductCodeSimpleCodeMismatches
            )
            || ! empty(
                $variantSimpleCodeMismatches
            );


        if ($hasIdentityDetails) {

            $output[] =
                'IDENTITY ANALYSIS DETAILS';

            $output[] =
                str_repeat(
                    '-',
                    58
                );


            if (
                ! empty(
                    $duplicateSupplierProductIds
                )
            ) {

                $output[] =
                    'Duplicate supplier product IDs:';

                foreach (
                    array_unique(
                        $duplicateSupplierProductIds
                    ) as $value
                ) {

                    $output[] =
                        '  - ' .
                        $value;
                }

                $output[] = '';
            }


            if (
                ! empty(
                    $duplicateVariantFullCodes
                )
            ) {

                $output[] =
                    'Duplicate variant fullCodes:';

                foreach (
                    array_unique(
                        $duplicateVariantFullCodes
                    ) as $value
                ) {

                    $output[] =
                        '  - ' .
                        $value;
                }

                $output[] = '';
            }


            if (
                ! empty(
                    $supplierProductIdSimpleCodeMismatches
                )
            ) {

                $output[] =
                    'Supplier product ID ↔ simpleCode mismatches:';

                foreach (
                    $supplierProductIdSimpleCodeMismatches
                    as $mismatch
                ) {

                    $output[] =
                        '  - ID: ' .
                        (
                            $mismatch[
                                'supplier_product_id'
                            ]
                            ?? ''
                        ) .
                        ' | simpleCode: ' .
                        (
                            $mismatch[
                                'simpleCode'
                            ]
                            ?? ''
                        );
                }

                $output[] = '';
            }


            if (
                ! empty(
                    $supplierProductCodeSimpleCodeMismatches
                )
            ) {

                $output[] =
                    'Supplier product code ↔ simpleCode mismatches:';

                foreach (
                    $supplierProductCodeSimpleCodeMismatches
                    as $mismatch
                ) {

                    $output[] =
                        '  - Code: ' .
                        (
                            $mismatch[
                                'supplier_product_code'
                            ]
                            ?? ''
                        ) .
                        ' | simpleCode: ' .
                        (
                            $mismatch[
                                'simpleCode'
                            ]
                            ?? ''
                        );
                }

                $output[] = '';
            }


            if (
                ! empty(
                    $variantSimpleCodeMismatches
                )
            ) {

                $output[] =
                    'Variant simpleCode mismatches:';

                foreach (
                    $variantSimpleCodeMismatches
                    as $mismatch
                ) {

                    $output[] =
                        '  - ID: ' .
                        (
                            $mismatch[
                                'supplier_product_id'
                            ]
                            ?? ''
                        ) .
                        ' | simpleCode: ' .
                        (
                            $mismatch[
                                'simpleCode'
                            ]
                            ?? ''
                        ) .
                        ' | fullCode: ' .
                        (
                            $mismatch[
                                'fullCode'
                            ]
                            ?? ''
                        );
                }

                $output[] = '';
            }
        }

        /*
|--------------------------------------------------------------------------
| Decoupled Product Analysis Report
|--------------------------------------------------------------------------
*/

$output[] =
    'DECOUPLED PRODUCT ANALYSIS';

$output[] =
    str_repeat(
        '-',
        58
    );

$output[] =
    'Decoupled products:                 ' .
    $decoupledProducts;

$output[] =
    'Decoupled with single variant:      ' .
    $decoupledWithSingleVariant;

$output[] =
    'Decoupled with multiple variants:   ' .
    $decoupledWithMultipleVariants;

$output[] =
    'Decoupled with no variants:         ' .
    $decoupledWithNoVariants;

$output[] =
    'Invalid decoupled flags:            ' .
    $invalidDecoupledFlags;

$output[] = '';


/*
|--------------------------------------------------------------------------
| WooCommerce Variant SKU Reconciliation Report
|--------------------------------------------------------------------------
*/

$canonicalOnlyVariantSkus =
    array_diff_key(
        $canonicalVariantFullCodes,
        $wooCommerceVariantSkus
    );

$wooCommerceOnlyVariantSkus =
    array_diff_key(
        $wooCommerceVariantSkus,
        $canonicalVariantFullCodes
    );

$output[] =
    'VARIANT SKU RECONCILIATION';

$output[] =
    str_repeat(
        '-',
        58
    );

$output[] =
    'Canonical variants:                ' .
    count($canonicalVariantFullCodes);

$output[] =
    'WooCommerce variations:            ' .
    count($wooCommerceVariantSkus);

$output[] =
    'Matched variants:                   ' .
    count($matchedVariantSkus);

$output[] =
    'New supplier variants:              ' .
    count($canonicalOnlyVariantSkus);

$output[] =
    'WooCommerce-only variants:          ' .
    count($wooCommerceOnlyVariantSkus);

$output[] =
    'Duplicate WooCommerce SKUs:         ' .
    count($duplicateWooCommerceVariantSkus);

$output[] = '';

/*
|--------------------------------------------------------------------------
| Unmatched Variant Sampling
|--------------------------------------------------------------------------
|
| Read-only diagnostic.
|
| Samples a small number of unmatched canonical and WooCommerce
| variant SKUs for manual inspection.
|
| This intentionally does NOT load the WooCommerce variation
| catalogue again.
|
*/

$unmatchedVariantSampleSize = 20;

$canonicalOnlyVariantSample =
    array_slice(
        array_keys(
            $canonicalOnlyVariantSkus
        ),
        0,
        $unmatchedVariantSampleSize
    );

$wooCommerceOnlyVariantSample =
    array_slice(
        array_keys(
            $wooCommerceOnlyVariantSkus
        ),
        0,
        $unmatchedVariantSampleSize
    );


/*
|--------------------------------------------------------------------------
| Build canonical metadata lookup for sampled unmatched variants
|--------------------------------------------------------------------------
*/

$canonicalVariantSampleData = [];

if ($products !== null) {

    foreach (
        $products->all()
        as $product
    ) {

        $data =
            $product->toArray();

        $identity =
            $data['identity']
            ?? [];

        $variantData =
    $data['variant']
    ?? [];

$variants =
    $variantData['items']
    ?? [];

        if (
            ! is_array($variants)
            || empty($variants)
        ) {
            continue;
        }

        foreach (
            $variants
            as $variant
        ) {

            if (
                ! is_array($variant)
            ) {
                continue;
            }

            $fullCode =
                $variant['fullCode']
                ?? null;

            if (
                ! is_string($fullCode)
                || $fullCode === ''
            ) {
                continue;
            }

            if (
                ! isset(
                    $canonicalOnlyVariantSkus[
                        $fullCode
                    ]
                )
            ) {
                continue;
            }

            if (
                ! in_array(
                    $fullCode,
                    $canonicalOnlyVariantSample,
                    true
                )
            ) {
                continue;
            }

            $canonicalVariantSampleData[
                $fullCode
            ] = [
                'supplier_product_id' =>
                    $identity[
                        'supplier_product_id'
                    ] ?? null,

                'supplier_product_code' =>
                    $identity[
                        'supplier_product_code'
                    ] ?? null,

                'simpleCode' =>
                    $variant[
                        'simpleCode'
                    ] ?? null,

                'fullCode' =>
                    $fullCode,
            ];
        }
    }
}


/*
|--------------------------------------------------------------------------
| Report
|--------------------------------------------------------------------------
*/

$output[] =
    'UNMATCHED VARIANT SAMPLE';

$output[] =
    str_repeat(
        '-',
        58
    );

$output[] =
    'New supplier variants sampled:       ' .
    count(
        $canonicalOnlyVariantSample
    );

$output[] =
    'WooCommerce-only variants sampled:   ' .
    count(
        $wooCommerceOnlyVariantSample
    );

$output[] = '';


/*
|--------------------------------------------------------------------------
| New Supplier Variants
|--------------------------------------------------------------------------
*/

$output[] =
    'NEW SUPPLIER VARIANTS';

$output[] =
    str_repeat(
        '-',
        58
    );

if (
    empty(
        $canonicalOnlyVariantSample
    )
) {

    $output[] =
        'None';

} else {

    foreach (
        $canonicalOnlyVariantSample
        as $sku
    ) {

        $sample =
            $canonicalVariantSampleData[
                $sku
            ] ?? [];

        $output[] =
            'SKU: ' .
            $sku;

        $output[] =
            '  Supplier product ID: ' .
            (
                $sample[
                    'supplier_product_id'
                ] ?? ''
            );

        $output[] =
            '  Supplier product code: ' .
            (
                $sample[
                    'supplier_product_code'
                ] ?? ''
            );

        $output[] =
            '  simpleCode: ' .
            (
                $sample[
                    'simpleCode'
                ] ?? ''
            );

        $output[] =
            '  fullCode: ' .
            (
                $sample[
                    'fullCode'
                ] ?? $sku
            );

        $output[] =
            '  WooCommerce: NOT FOUND';

        $output[] = '';
    }
}


/*
|--------------------------------------------------------------------------
| WooCommerce-only Variants
|--------------------------------------------------------------------------
*/

$output[] =
    'WOOCOMMERCE-ONLY VARIANTS';

$output[] =
    str_repeat(
        '-',
        58
    );

if (
    empty(
        $wooCommerceOnlyVariantSample
    )
) {

    $output[] =
        'None';

} else {

    foreach (
        $wooCommerceOnlyVariantSample
        as $sku
    ) {

        $variationId =
            wc_get_product_id_by_sku(
                $sku
            );

        $productId = null;

        if (
            $variationId
            && function_exists(
                'wp_get_post_parent_id'
            )
        ) {

            $productId =
                wp_get_post_parent_id(
                    $variationId
                );
        }

        $output[] =
            'SKU: ' .
            $sku;

        $output[] =
            '  Variation ID: ' .
            (
                $variationId
                ?: 'NOT FOUND'
            );

        $output[] =
            '  Parent product ID: ' .
            (
                $productId
                ?: 'NOT FOUND'
            );

        $output[] =
            '  Canonical: NOT FOUND';

        $output[] = '';
    }
}

$output[] = '';

/*
|--------------------------------------------------------------------------
| WooCommerce-only Product Family Analysis
|--------------------------------------------------------------------------
|
| Read-only diagnostic.
|
| Groups the sampled WooCommerce-only variations by their parent
| WooCommerce product and checks whether the corresponding canonical
| product identity exists.
|
| This intentionally does not load the WooCommerce catalogue again.
|
*/

$wooCommerceOnlyFamilySamples = [];

foreach (
    $wooCommerceOnlyVariantSample
    as $sku
) {

    $variationId =
        wc_get_product_id_by_sku(
            $sku
        );

    if (
        ! $variationId
    ) {
        continue;
    }

    $parentProductId =
        wp_get_post_parent_id(
            $variationId
        );

    if (
        ! $parentProductId
    ) {
        continue;
    }

    if (
        ! isset(
            $wooCommerceOnlyFamilySamples[
                $parentProductId
            ]
        )
    ) {

        $parentSku =
            get_post_meta(
                $parentProductId,
                '_sku',
                true
            );

        $parentTitle =
            get_the_title(
                $parentProductId
            );

        $wooCommerceOnlyFamilySamples[
            $parentProductId
        ] = [
            'parent_sku' =>
                is_string($parentSku)
                ? $parentSku
                : '',

            'parent_title' =>
                is_string($parentTitle)
                ? $parentTitle
                : '',

            'variation_skus' =>
                [],
        ];
    }

    $wooCommerceOnlyFamilySamples[
        $parentProductId
    ]['variation_skus'][] =
        $sku;
}


/*
|--------------------------------------------------------------------------
| Build canonical product identity lookup
|--------------------------------------------------------------------------
*/

$canonicalProductIdentityLookup = [];

if ($products !== null) {

    foreach (
        $products->all()
        as $product
    ) {

        $data =
            $product->toArray();

        $identity =
            $data['identity']
            ?? [];

        $supplierProductId =
            $identity[
                'supplier_product_id'
            ]
            ?? null;

        $supplierProductCode =
            $identity[
                'supplier_product_code'
            ]
            ?? null;

        if (
            is_string(
                $supplierProductId
            )
            && $supplierProductId !== ''
        ) {

            $canonicalProductIdentityLookup[
                $supplierProductId
            ] = true;
        }

        if (
            is_string(
                $supplierProductCode
            )
            && $supplierProductCode !== ''
        ) {

            $canonicalProductIdentityLookup[
                $supplierProductCode
            ] = true;
        }
    }
}


/*
|--------------------------------------------------------------------------
| Report
|--------------------------------------------------------------------------
*/

$output[] =
    'WOOCOMMERCE-ONLY PRODUCT FAMILIES';

$output[] =
    str_repeat(
        '-',
        58
    );

$output[] =
    'Sampled families: ' .
    count(
        $wooCommerceOnlyFamilySamples
    );

$output[] = '';

if (
    empty(
        $wooCommerceOnlyFamilySamples
    )
) {

    $output[] =
        'None';

} else {

    foreach (
        $wooCommerceOnlyFamilySamples
        as $parentProductId =>
        $family
    ) {

        $parentSku =
            $family['parent_sku']
            ?? '';

        $parentTitle =
            $family['parent_title']
            ?? '';

        $canonicalParentFound =
            isset(
                $canonicalProductIdentityLookup[
                    $parentSku
                ]
            );

        $output[] =
            'Parent ID: ' .
            $parentProductId;

        $output[] =
            '  Parent SKU: ' .
            (
                $parentSku !== ''
                ? $parentSku
                : 'NONE'
            );

        $output[] =
            '  Parent title: ' .
            (
                $parentTitle !== ''
                ? $parentTitle
                : 'NONE'
            );

        $output[] =
            '  Canonical parent: ' .
            (
                $canonicalParentFound
                ? 'FOUND'
                : 'NOT FOUND'
            );

        $output[] =
            '  Sampled unmatched variations: ' .
            count(
                $family[
                    'variation_skus'
                ]
            );

        foreach (
            $family[
                'variation_skus'
            ]
            as $variationSku
        ) {

            $output[] =
                '    - ' .
                $variationSku;
        }

        $output[] = '';
    }
}

$output[] = '';


        /*
        |--------------------------------------------------------------------------
        | Errors
        |--------------------------------------------------------------------------
        */

        $output[] =
            'ERRORS';

        $output[] =
            str_repeat(
                '-',
                58
            );

        if (empty($errors)) {

            $output[] =
                'None';

        } else {

            foreach (
                $errors
                as $error
            ) {

                $output[] =
                    '- ' .
                    $error;
            }
        }

        $output[] = '';


        /*
        |--------------------------------------------------------------------------
        | First Canonical Product Sample
        |--------------------------------------------------------------------------
        */

        $output[] =
            'FIRST CANONICAL PRODUCT SAMPLE';

        $output[] =
            str_repeat(
                '-',
                58
            );


        if (
            $products !== null
            && ! $products->isEmpty()
        ) {

            $firstProduct =
                $products->get(0);

            if ($firstProduct !== null) {

                $output[] =
                    print_r(
                        $firstProduct->toArray(),
                        true
                    );
            }

        } else {

            $output[] =
                'No canonical products were produced.';
        }


        /*
        |--------------------------------------------------------------------------
        | Output
        |--------------------------------------------------------------------------
        */

        wp_die(
            '<pre>' .
            esc_html(
                implode(
                    "\n",
                    $output
                )
            ) .
            '</pre>'
        );

    } catch (\Throwable $exception) {

        wp_die(
            '<pre>' .
            esc_html(
                'Normalization failed: ' .
                $exception->getMessage()
            ) .
            '</pre>'
        );
    }
}

/**
 * Run the WooCommerce projection verification test.
 *
 * This action:
 *
 * - Requires manage_woocommerce capability.
 * - Verifies the admin nonce.
 * - Loads an existing immutable snapshot.
 * - Normalizes the snapshot into canonical products.
 * - Projects every canonical product into a WooCommerce projection plan.
 * - Verifies parent and variant structure.
 * - Verifies identity and SKU consistency.
 * - Verifies BlackPrint ownership metadata.
 * - Does not persist canonical products.
 * - Does not create or modify WooCommerce products.
 */
public function test_woocommerce_projection(): void
{
    if (
        ! current_user_can(
            'manage_woocommerce'
        )
    ) {
        wp_die(
            'You do not have permission to run this projection test.'
        );
    }

    check_admin_referer(
        'bp_test_woocommerce_projection'
    );

    /*
    |--------------------------------------------------------------------------
    | Existing Verified Snapshot
    |--------------------------------------------------------------------------
    */

    $snapshotUuid =
        'e1feb722-4844-4561-bb22-a199a57522d9';

    /*
    |--------------------------------------------------------------------------
    | Execute Normalization
    |--------------------------------------------------------------------------
    */

    try {

        $result = bp_commerce()
            ->normalization()
            ->normalize(
                $snapshotUuid
            );

        if (
            ! $result->success()
        ) {
            wp_die(
                esc_html(
                    'Normalization failed: ' .
                    (
                        $result->errors()[0]
                        ?? 'Unknown normalization error.'
                    )
                )
            );
        }

        $products =
            $result->products();

        if (
            $products === null
        ) {
            wp_die(
                'Projection test aborted: no canonical product collection was returned.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Projector
        |--------------------------------------------------------------------------
        */

        $projector =
            new \BlackPrint\Commerce\Projection\WooCommerce\WooCommerceProductProjector();

        /*
        |--------------------------------------------------------------------------
        | Projection Statistics
        |--------------------------------------------------------------------------
        */

        $canonicalProducts = 0;
        $plannedProducts = 0;
        $failedProducts = 0;

        $variableParents = 0;
        $variationChildren = 0;

        $singleVariantProducts = 0;
        $multipleVariantProducts = 0;

        $missingParentIdentity = 0;
        $missingVariantIdentity = 0;

        $skuMismatches = 0;

        $attributeProducts = 0;
        $attributeVariants = 0;

        $ownershipFailures = 0;
        $decoupledProducts = 0;

        $createdActions = 0;
        $updatedActions = 0;
        $skippedActions = 0;

        $failures = [];
        $skuMismatchDetails = [];
        $ownershipFailureDetails = [];

        /*
        |--------------------------------------------------------------------------
        | Project Every Canonical Product
        |--------------------------------------------------------------------------
        */

        foreach (
            $products->all() as $product
        ) {

            $canonicalProducts++;

            $canonical =
                $product->toArray();

            $projectionResult =
                $projector->project(
                    $canonical
                );

            /*
            |--------------------------------------------------------------------------
            | Projection Result
            |--------------------------------------------------------------------------
            */

            if (
                ! $projectionResult->success()
            ) {

                $failedProducts++;

                $failures[] = [
                    'message' =>
                        $projectionResult->message(),

                    'identity' =>
                        $canonical['identity']
                        ?? [],
                ];

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Projection Must Be Planned
            |--------------------------------------------------------------------------
            */

            if (
                $projectionResult->action() === 'planned'
            ) {

                $plannedProducts++;

            } elseif (
                $projectionResult->action() === 'created'
            ) {

                $createdActions++;

            } elseif (
                $projectionResult->action() === 'updated'
            ) {

                $updatedActions++;

            } elseif (
                $projectionResult->action() === 'skipped'
            ) {

                $skippedActions++;
            }

            $projection =
                $projectionResult->data();

            /*
            |--------------------------------------------------------------------------
            | Parent Verification
            |--------------------------------------------------------------------------
            */

            $parent =
                $projection['parent']
                ?? null;

            if (
                ! is_array($parent)
            ) {

                $failures[] = [
                    'message' =>
                        'Projection parent is missing or invalid.',

                    'identity' =>
                        $canonical['identity']
                        ?? [],
                ];

                $failedProducts++;

                continue;
            }

            if (
                ($parent['type'] ?? null)
                === 'variable'
            ) {

                $variableParents++;

            } else {

                $failures[] = [
                    'message' =>
                        'Projection parent type is not variable.',

                    'identity' =>
                        $canonical['identity']
                        ?? [],
                ];

                $failedProducts++;
            }

            /*
            |--------------------------------------------------------------------------
            | Parent Identity Verification
            |--------------------------------------------------------------------------
            */

            $parentIdentity =
                $parent['identity']
                ?? [];

            if (
                ! is_array($parentIdentity)
                || ! is_string(
                    $parentIdentity['supplier_product_id']
                    ?? null
                )
                || $parentIdentity['supplier_product_id'] === ''
                || ! is_string(
                    $parentIdentity['supplier_product_code']
                    ?? null
                )
                || $parentIdentity['supplier_product_code'] === ''
            ) {

                $missingParentIdentity++;

                $failures[] = [
                    'message' =>
                        'Projection parent identity is incomplete.',

                    'identity' =>
                        $canonical['identity']
                        ?? [],
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Decoupled Product Preservation
            |--------------------------------------------------------------------------
            */

            $hierarchy =
                $parent['hierarchy']
                ?? [];

            if (
                is_array($hierarchy)
                && ! empty(
                    $hierarchy['decoupled']
                    ?? false
                )
            ) {

                $decoupledProducts++;
            }

            /*
            |--------------------------------------------------------------------------
            | Parent Attributes
            |--------------------------------------------------------------------------
            */

            $classification =
                $parent['classification']
                ?? [];

            $parentAttributes =
                $classification['attributes']
                ?? [];

            if (
                is_array($parentAttributes)
                && $parentAttributes !== []
            ) {

                $attributeProducts++;
            }

            /*
            |--------------------------------------------------------------------------
            | Variant Verification
            |--------------------------------------------------------------------------
            */

            $variants =
                $projection['variants']
                ?? [];

            if (
                ! is_array($variants)
                || $variants === []
            ) {

                $failures[] = [
                    'message' =>
                        'Projection contains no variants.',

                    'identity' =>
                        $canonical['identity']
                        ?? [],
                ];

                $failedProducts++;

                continue;
            }

            if (
                count($variants) === 1
            ) {

                $singleVariantProducts++;

            } else {

                $multipleVariantProducts++;
            }

            foreach (
                $variants as $variant
            ) {

                $variationChildren++;

                if (
                    ! is_array($variant)
                    || ($variant['type'] ?? null)
                    !== 'variation'
                ) {

                    $failures[] = [
                        'message' =>
                            'Projection variant is not a variation.',

                        'identity' =>
                            $canonical['identity']
                            ?? [],
                    ];

                    $failedProducts++;

                    continue;
                }

                $variantIdentity =
                    $variant['identity']
                    ?? [];

                if (
                    ! is_array($variantIdentity)
                    || ! is_string(
                        $variantIdentity['simple_code']
                        ?? null
                    )
                    || $variantIdentity['simple_code'] === ''
                    || ! is_string(
                        $variantIdentity['full_code']
                        ?? null
                    )
                    || $variantIdentity['full_code'] === ''
                ) {

                    $missingVariantIdentity++;

                    $failures[] = [
                        'message' =>
                            'Projection variant identity is incomplete.',

                        'identity' =>
                            $canonical['identity']
                            ?? [],
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | SKU Verification
                |--------------------------------------------------------------------------
                */

                $sku =
                    $variant['sku']
                    ?? null;

                $fullCode =
                    $variantIdentity['full_code']
                    ?? null;

                if (
                    ! is_string($sku)
                    || ! is_string($fullCode)
                    || $sku !== $fullCode
                ) {

                    $skuMismatches++;

                    $skuMismatchDetails[] = [
                        'sku' => $sku,
                        'full_code' => $fullCode,
                        'identity' =>
                            $canonical['identity']
                            ?? [],
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | Variant Attributes
                |--------------------------------------------------------------------------
                */

                $variantAttributes =
                    $variant['attributes']
                    ?? [];

                if (
                    is_array($variantAttributes)
                    && $variantAttributes !== []
                ) {

                    $attributeVariants++;
                }

                /*
                |--------------------------------------------------------------------------
                | Ownership Metadata
                |--------------------------------------------------------------------------
                */

                $meta =
                    $variant['meta']
                    ?? [];

                if (
                    ! is_array($meta)
                    || ($meta['_blackprint_managed'] ?? null)
                    !== 'yes'
                    || ($meta['_blackprint_supplier'] ?? null)
                    !== (
                        $canonical['provenance']['supplier']
                        ?? null
                    )
                    || ($meta['_blackprint_variant_code'] ?? null)
                    !== $fullCode
                ) {

                    $ownershipFailures++;

                    $ownershipFailureDetails[] = [
                        'identity' =>
                            $canonical['identity']
                            ?? [],

                        'variant' =>
                            $variantIdentity,
                    ];
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Parent Ownership Metadata
            |--------------------------------------------------------------------------
            */

            $parentMeta =
                $parent['meta']
                ?? [];

            if (
                ! is_array($parentMeta)
                || ($parentMeta['_blackprint_managed'] ?? null)
                !== 'yes'
                || ($parentMeta['_blackprint_supplier'] ?? null)
                !== (
                    $canonical['provenance']['supplier']
                    ?? null
                )
                || ($parentMeta['_blackprint_product_id'] ?? null)
                !== (
                    $canonical['identity']['supplier_product_id']
                    ?? null
                )
                || ($parentMeta['_blackprint_product_code'] ?? null)
                !== (
                    $canonical['identity']['supplier_product_code']
                    ?? null
                )
            ) {

                $ownershipFailures++;

                $ownershipFailureDetails[] = [
                    'identity' =>
                        $canonical['identity']
                        ?? [],
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Determine Verification Status
        |--------------------------------------------------------------------------
        */

        $status =
            (
                $canonicalProducts > 0
                && $failedProducts === 0
                && $plannedProducts === $canonicalProducts
                && $createdActions === 0
                && $updatedActions === 0
                && $skippedActions === 0
                && $missingParentIdentity === 0
                && $missingVariantIdentity === 0
                && $skuMismatches === 0
                && $ownershipFailures === 0
            )
                ? 'PASS'
                : 'FAILED';

        /*
        |--------------------------------------------------------------------------
        | Build Human-Readable Report
        |--------------------------------------------------------------------------
        */

        $output = [];

        $output[] =
            'TEST VERSION: WOOCOMMERCE PROJECTION v1';

        $output[] = '';

        $output[] =
            'BlackPrint OS — WooCommerce Projection Verification';

        $output[] =
            str_repeat('=', 64);

        $output[] = '';

        $output[] = 'SNAPSHOT';

        $output[] =
            str_repeat('-', 64);

        $output[] =
            'UUID: ' .
            $snapshotUuid;

        $output[] = '';

        $output[] = 'PROJECTION';

        $output[] =
            str_repeat('-', 64);

        $output[] =
            'Canonical products:       ' .
            $canonicalProducts;

        $output[] =
            'Planned projections:      ' .
            $plannedProducts;

        $output[] =
            'Failed projections:       ' .
            $failedProducts;

        $output[] =
            'Created actions:          ' .
            $createdActions;

        $output[] =
            'Updated actions:          ' .
            $updatedActions;

        $output[] =
            'Skipped actions:          ' .
            $skippedActions;

        $output[] =
            'Status:                   ' .
            $status;

        $output[] = '';

        $output[] = 'PARENT STRUCTURE';

        $output[] =
            str_repeat('-', 64);

        $output[] =
            'Variable parents:         ' .
            $variableParents;

        $output[] =
            'Decoupled products:       ' .
            $decoupledProducts;

        $output[] =
            'Single-variant products:  ' .
            $singleVariantProducts;

        $output[] =
            'Multi-variant products:   ' .
            $multipleVariantProducts;

        $output[] = '';

        $output[] = 'VARIATION STRUCTURE';

        $output[] =
            str_repeat('-', 64);

        $output[] =
            'Variation children:       ' .
            $variationChildren;

        $output[] =
            'Missing variant identity: ' .
            $missingVariantIdentity;

        $output[] =
            'SKU mismatches:            ' .
            $skuMismatches;

        $output[] = '';

        $output[] = 'ATTRIBUTES';

        $output[] =
            str_repeat('-', 64);

        $output[] =
            'Products with attributes: ' .
            $attributeProducts;

        $output[] =
            'Variants with attributes:  ' .
            $attributeVariants;

        $output[] = '';

        $output[] = 'OWNERSHIP';

        $output[] =
            str_repeat('-', 64);

        $output[] =
            'Ownership failures:        ' .
            $ownershipFailures;

        $output[] = '';

        if (
            $skuMismatchDetails !== []
        ) {

            $output[] =
                'SKU MISMATCH DETAILS';

            $output[] =
                str_repeat('-', 64);

            foreach (
                array_slice(
                    $skuMismatchDetails,
                    0,
                    20
                ) as $detail
            ) {

                $output[] =
                    'SKU=' .
                    wp_json_encode(
                        $detail['sku']
                    ) .
                    ' fullCode=' .
                    wp_json_encode(
                        $detail['full_code']
                    ) .
                    ' identity=' .
                    wp_json_encode(
                        $detail['identity']
                    );
            }

            $output[] = '';
        }

        if (
            $ownershipFailureDetails !== []
        ) {

            $output[] =
                'OWNERSHIP FAILURE DETAILS';

            $output[] =
                str_repeat('-', 64);

            foreach (
                array_slice(
                    $ownershipFailureDetails,
                    0,
                    20
                ) as $detail
            ) {

                $output[] =
                    'identity=' .
                    wp_json_encode(
                        $detail['identity']
                    );

                if (
                    isset(
                        $detail['variant']
                    )
                ) {

                    $output[] =
                        'variant=' .
                        wp_json_encode(
                            $detail['variant']
                        );
                }
            }

            $output[] = '';
        }

        if (
            $failures !== []
        ) {

            $output[] =
                'FAILURES';

            $output[] =
                str_repeat('-', 64);

            foreach (
                array_slice(
                    $failures,
                    0,
                    20
                ) as $failure
            ) {

                $output[] =
                    'message=' .
                    (
                        $failure['message']
                        ?? 'Unknown failure'
                    ) .
                    ' identity=' .
                    wp_json_encode(
                        $failure['identity']
                        ?? []
                    );
            }

            $output[] = '';
        }

        $output[] =
            str_repeat('=', 64);

        $output[] =
            'FINAL STATUS: ' .
            $status;

        echo '<pre>';

        echo esc_html(
            implode(
                "\n",
                $output
            )
        );

        echo '</pre>';

    } catch (
        \Throwable $e
    ) {

        echo '<pre>';

        echo esc_html(
            'Projection test exception: ' .
            $e->getMessage()
        );

        echo "\n";

        echo esc_html(
            $e->getFile() .
            ':' .
            $e->getLine()
        );

        echo '</pre>';
    }
}

    /**
     * Render the Amrod Stock Explorer page.
     *
     * This page is read-only.
     *
     * No WooCommerce products or stock values are
     * created or modified.
     */

    public function amrod_stock(): void
    {
        $connector = new \BlackPrint\Commerce\Suppliers\Amrod\Amrod_Connector();

        $stock_service = $connector->get_stock_service();

        $result = [];

        $error = '';

        $action = isset($_GET['bp_amrod_stock_action'])
            ? sanitize_key(
                wp_unslash(
                    $_GET['bp_amrod_stock_action']
                )
            )
            : '';


        /*
        |--------------------------------------------------------------------------
        | Execute Read-Only Stock API Test
        |--------------------------------------------------------------------------
        */

        if (
            isset($_GET['bp_amrod_stock_test'])
            && check_admin_referer(
                'bp_amrod_stock_test'
            )
        ) {
            try {

                switch ($action) {

                    case 'stock':

                        $result = $stock_service->get_stock();

                        break;

                    case 'updated_stock':

                        $result =
                            $stock_service->get_updated_stock();

                        break;

                    default:

                        $error =
                            'Invalid Amrod stock API action.';

                        break;
                }

            } catch (\Throwable $exception) {

                $error = $exception->getMessage();
            }
        }

        include BP_COMMERCE_PATH
            . 'admin/views/amrod-stock.php';
    }


    /**
     * Render the Amrod Prices Explorer page.
     *
     * This page is read-only.
     *
     * No WooCommerce products or prices are
     * created or modified.
     */
    public function amrod_prices(): void
    {
        $connector = new \BlackPrint\Commerce\Suppliers\Amrod\Amrod_Connector();

        $price_service = $connector->get_price_service();

        $result = [];

        $error = '';

        $action = isset($_GET['bp_amrod_prices_action'])
            ? sanitize_key(
                wp_unslash(
                    $_GET['bp_amrod_prices_action']
                )
            )
            : '';


        /*
        |--------------------------------------------------------------------------
        | Execute Read-Only Price API Test
        |--------------------------------------------------------------------------
        */

        if (
            isset($_GET['bp_amrod_prices_test'])
            && check_admin_referer(
                'bp_amrod_prices_test'
            )
        ) {
            try {

                switch ($action) {

                    case 'prices':

                        $result = $price_service->get_prices();

                        break;

                    case 'updated_prices':

                        $result =
                            $price_service->get_updated_prices();

                        break;

                    default:

                        $error =
                            'Invalid Amrod price API action.';

                        break;
                }

            } catch (\Throwable $exception) {

                $error = $exception->getMessage();
            }
        }

        include BP_COMMERCE_PATH
            . 'admin/views/amrod-prices.php';
    }


    /**
     * Render the Amrod Branding Explorer page.
     *
     * This page is read-only.
     */
    public function amrod_branding(): void
    {
        $connector = new \BlackPrint\Commerce\Suppliers\Amrod\Amrod_Connector();

        $branding_department_service =
            $connector->get_branding_department_service();

        $inclusive_branding_service =
            $connector->get_inclusive_branding_service();

        $result = [];

        $error = '';

        $action = isset($_GET['bp_amrod_branding_action'])
            ? sanitize_key(
                wp_unslash(
                    $_GET['bp_amrod_branding_action']
                )
            )
            : '';


        /*
        |--------------------------------------------------------------------------
        | Execute Read-Only Branding API Test
        |--------------------------------------------------------------------------
        */

        if (
            isset($_GET['bp_amrod_branding_test'])
            && check_admin_referer(
                'bp_amrod_branding_test'
            )
        ) {
            try {

                switch ($action) {

                    case 'branding_departments':

                        $result =
                            $branding_department_service
                                ->get_branding_departments();

                        break;

                    case 'updated_branding_departments':

                        $result =
                            $branding_department_service
                                ->get_updated_branding_departments();

                        break;

                    case 'inclusive_branding':

                        $result =
                            $inclusive_branding_service
                                ->get_inclusive_branding();

                        break;

                    case 'updated_inclusive_branding':

                        $result =
                            $inclusive_branding_service
                                ->get_updated_inclusive_branding();

                        break;

                    default:

                        $error =
                            'Invalid branding action.';

                        break;
                }

            } catch (\Throwable $exception) {

                $error = $exception->getMessage();
            }
        }

        include BP_COMMERCE_PATH
            . 'admin/views/amrod-branding.php';
    }

    /**
     * Run the WooCommerce controlled variant creation verification test.
     *
     * 12.1.2
     *
     * This test intentionally creates exactly one WooCommerce variation
     * beneath an existing BlackPrint-managed variable parent.
     *
     * It:
     *
     * - Requires manage_woocommerce capability.
     * - Verifies the admin nonce.
     * - Loads the existing verified snapshot.
     * - Normalizes the snapshot into canonical products.
     * - Selects exactly one canonical product.
     * - Builds one WooCommerce projection.
     * - Confirms that the BlackPrint-managed parent already exists.
     * - Confirms the parent is a valid variable product.
     * - Selects exactly one projected variant.
     * - Confirms that the selected variation does not already exist.
     * - Executes the projection.
     * - Verifies that exactly one variation was created.
     * - Verifies the parent relationship.
     * - Verifies SKU identity.
     * - Verifies BlackPrint ownership metadata.
     * - Verifies projected variant attributes.
     *
     * It does not process the remaining canonical products or variants.
     *
     * No CLI dependency is used.
     */
    public function test_woocommerce_variant_creation(): void
    {
        if (
            ! current_user_can(
                'manage_woocommerce'
            )
        ) {
            wp_die(
                'You do not have permission to run the WooCommerce variant creation test.'
            );
        }

        check_admin_referer(
            'bp_test_woocommerce_variant_creation'
        );

        /*
        |--------------------------------------------------------------------------
        | Existing Verified Snapshot
        |--------------------------------------------------------------------------
        */

        $snapshotUuid =
            'e1feb722-4844-4561-bb22-a199a57522d9';

        try {

            /*
            |--------------------------------------------------------------------------
            | Normalize Snapshot
            |--------------------------------------------------------------------------
            */

            $normalizationResult =
                bp_commerce()
                    ->normalization()
                    ->normalize(
                        $snapshotUuid
                    );

            if (
                ! $normalizationResult->success()
            ) {

                wp_die(
                    esc_html(
                        'Normalization failed: ' .
                        (
                            $normalizationResult->errors()[0]
                            ?? 'Unknown normalization error.'
                        )
                    )
                );
            }

            $products =
                $normalizationResult->products();

            if (
                $products === null
                || $products->isEmpty()
            ) {

                wp_die(
                    'Variant creation test aborted: no canonical products were returned.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Select Exactly One Canonical Product
            |--------------------------------------------------------------------------
            */

            $canonicalProduct =
                $products->get(0);

            if (
                $canonicalProduct === null
            ) {

                wp_die(
                    'Variant creation test aborted: first canonical product could not be loaded.'
                );
            }

            $canonical =
                $canonicalProduct->toArray();

            /*
            |--------------------------------------------------------------------------
            | Projection Components
            |--------------------------------------------------------------------------
            */

            $projector =
                new \BlackPrint\Commerce\Projection\WooCommerce\WooCommerceProductProjector();

            $executor =
                new \BlackPrint\Commerce\Projection\WooCommerce\WooCommerceProjectionExecutor();

            /*
            |--------------------------------------------------------------------------
            | Build Projection
            |--------------------------------------------------------------------------
            */

            $projectionResult =
                $projector->project(
                    $canonical
                );

            if (
                ! $projectionResult->success()
            ) {

                wp_die(
                    esc_html(
                        'Projection failed: ' .
                        (
                            $projectionResult->message()
                            ?? 'Unknown projection error.'
                        )
                    )
                );
            }

            $projection =
                $projectionResult->data();

            if (
                ! is_array($projection)
            ) {

                wp_die(
                    'Variant creation test aborted: projection data is invalid.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Projection Parent
            |--------------------------------------------------------------------------
            */

            $parent =
                $projection['parent']
                ?? null;

            if (
                ! is_array($parent)
            ) {

                wp_die(
                    'Variant creation test aborted: projection parent is invalid.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Projection Variants
            |--------------------------------------------------------------------------
            */

            $variants =
                $projection['variants']
                ?? null;

            if (
                ! is_array($variants)
                || $variants === []
            ) {

                wp_die(
                    'Variant creation test aborted: projection contains no variants.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Parent Identity
            |--------------------------------------------------------------------------
            */

            $parentIdentity =
                $parent['identity']
                ?? null;

            if (
                ! is_array($parentIdentity)
            ) {

                wp_die(
                    'Variant creation test aborted: parent identity is invalid.'
                );
            }

            $expectedSupplier =
                $parentIdentity['supplier']
                ?? null;

            $expectedProductId =
                $parentIdentity['supplier_product_id']
                ?? null;

            $expectedProductCode =
                $parentIdentity['supplier_product_code']
                ?? null;

            if (
                ! is_string($expectedSupplier)
                || $expectedSupplier === ''
                || ! is_string($expectedProductId)
                || $expectedProductId === ''
                || ! is_string($expectedProductCode)
                || $expectedProductCode === ''
            ) {

                wp_die(
                    'Variant creation test aborted: parent identity is incomplete.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Select Exactly One Projected Variant
            |--------------------------------------------------------------------------
            */

            $variant =
                $variants[0]
                ?? null;

            if (
                ! is_array($variant)
            ) {

                wp_die(
                    'Variant creation test aborted: first projected variant is invalid.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Variant Identity
            |--------------------------------------------------------------------------
            */

            $variantIdentity =
                $variant['identity']
                ?? null;

            if (
                ! is_array($variantIdentity)
            ) {

                wp_die(
                    'Variant creation test aborted: variant identity is invalid.'
                );
            }

            $expectedVariantSupplier =
                $variantIdentity['supplier']
                ?? null;

            $expectedSimpleCode =
                $variantIdentity['simple_code']
                ?? null;

            $expectedFullCode =
                $variantIdentity['full_code']
                ?? null;

            $expectedSku =
                $variant['sku']
                ?? null;

            if (
                ! is_string($expectedVariantSupplier)
                || $expectedVariantSupplier === ''
            ) {

                wp_die(
                    'Variant creation test aborted: variant supplier identity is missing.'
                );
            }

            if (
                ! is_string($expectedSimpleCode)
                || $expectedSimpleCode === ''
            ) {

                wp_die(
                    'Variant creation test aborted: variant simple_code is missing.'
                );
            }

            if (
                ! is_string($expectedFullCode)
                || $expectedFullCode === ''
            ) {

                wp_die(
                    'Variant creation test aborted: variant full_code is missing.'
                );
            }

            if (
                ! is_string($expectedSku)
                || $expectedSku === ''
            ) {

                wp_die(
                    'Variant creation test aborted: variant SKU is missing.'
                );
            }

            if (
                $expectedSku !== $expectedFullCode
            ) {

                wp_die(
                    'Variant creation test aborted: projected SKU does not match full_code.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Expected Variant Attributes
            |--------------------------------------------------------------------------
            */

            $expectedAttributes =
                $variant['attributes']
                ?? [];

            if (
                ! is_array($expectedAttributes)
            ) {

                wp_die(
                    'Variant creation test aborted: projected variant attributes are invalid.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Locate Existing BlackPrint Parent
            |--------------------------------------------------------------------------
            |
            | Unlike 12.1.1, the existence of the parent is required.
            |
            | The test must never adopt an arbitrary WooCommerce product.
            |
            */

            $existingParentIds =
                get_posts(
                    [
                        'post_type' =>
                            'product',

                        'post_status' =>
                            'any',

                        'posts_per_page' =>
                            2,

                        'fields' =>
                            'ids',

                        'meta_query' =>
                            [
                                'relation' =>
                                    'AND',

                                [
                                    'key' =>
                                        '_blackprint_managed',

                                    'value' =>
                                        'yes',
                                ],

                                [
                                    'key' =>
                                        '_blackprint_supplier',

                                    'value' =>
                                        $expectedSupplier,
                                ],

                                [
                                    'key' =>
                                        '_blackprint_product_id',

                                    'value' =>
                                        $expectedProductId,
                                ],
                            ],
                    ]
                );

            /*
            |--------------------------------------------------------------------------
            | Parent Must Exist
            |--------------------------------------------------------------------------
            */

            if (
                ! is_array($existingParentIds)
                || $existingParentIds === []
            ) {

                wp_die(
                    '<pre>' .
                    esc_html(
                        implode(
                            "\n",
                            [
                                '12.1.2 CONTROLLED VARIANT CREATION',
                                str_repeat(
                                    '=',
                                    64
                                ),
                                '',
                                'TEST ABORTED — REQUIRED BLACKPRINT PARENT DOES NOT EXIST.',
                                '',
                                'Supplier: ' .
                                    $expectedSupplier,
                                'Supplier product ID: ' .
                                    $expectedProductId,
                                'Supplier product code: ' .
                                    $expectedProductCode,
                                '',
                                'No WooCommerce variation was created.',
                                '',
                                'FINAL STATUS: ABORTED',
                            ]
                        )
                    ) .
                    '</pre>'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Duplicate Parent Safety
            |--------------------------------------------------------------------------
            */

            if (
                count($existingParentIds) > 1
            ) {

                wp_die(
                    '<pre>' .
                    esc_html(
                        implode(
                            "\n",
                            [
                                '12.1.2 CONTROLLED VARIANT CREATION',
                                str_repeat(
                                    '=',
                                    64
                                ),
                                '',
                                'TEST ABORTED — MULTIPLE BLACKPRINT PARENTS FOUND.',
                                '',
                                'Supplier: ' .
                                    $expectedSupplier,
                                'Supplier product ID: ' .
                                    $expectedProductId,
                                'Supplier product code: ' .
                                    $expectedProductCode,
                                'Existing WooCommerce ID(s): ' .
                                    wp_json_encode(
                                        $existingParentIds
                                    ),
                                '',
                                'No WooCommerce variation was created.',
                                '',
                                'FINAL STATUS: ABORTED',
                            ]
                        )
                    ) .
                    '</pre>'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Existing Parent ID
            |--------------------------------------------------------------------------
            */

            $parentId =
                $existingParentIds[0]
                ?? null;

            if (
                ! is_numeric($parentId)
            ) {

                wp_die(
                    'Variant creation test aborted: existing parent ID is invalid.'
                );
            }

            $parentId =
                (int) $parentId;

            if (
                $parentId <= 0
            ) {

                wp_die(
                    'Variant creation test aborted: existing parent ID is invalid.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Reload Existing Parent
            |--------------------------------------------------------------------------
            */

            $existingParent =
                wc_get_product(
                    $parentId
                );

            if (
                ! $existingParent
            ) {

                wp_die(
                    'Variant creation test aborted: existing parent could not be reloaded.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Parent Verification
            |--------------------------------------------------------------------------
            */

            $parentFailures = [];

            if (
                $existingParent->get_type()
                !== 'variable'
            ) {

                $parentFailures[] =
                    'Existing parent is not a variable product.';
            }

            if (
                $existingParent->get_meta(
                    '_blackprint_managed'
                )
                !== 'yes'
            ) {

                $parentFailures[] =
                    '_blackprint_managed is not yes.';
            }

            if (
                $existingParent->get_meta(
                    '_blackprint_supplier'
                )
                !== $expectedSupplier
            ) {

                $parentFailures[] =
                    '_blackprint_supplier does not match.';
            }

            if (
                $existingParent->get_meta(
                    '_blackprint_product_id'
                )
                !== $expectedProductId
            ) {

                $parentFailures[] =
                    '_blackprint_product_id does not match.';
            }

            if (
                $existingParent->get_meta(
                    '_blackprint_product_code'
                )
                !== $expectedProductCode
            ) {

                $parentFailures[] =
                    '_blackprint_product_code does not match.';
            }

            if (
                $parentFailures !== []
            ) {

                wp_die(
                    '<pre>' .
                    esc_html(
                        implode(
                            "\n",
                            [
                                '12.1.2 CONTROLLED VARIANT CREATION',
                                str_repeat(
                                    '=',
                                    64
                                ),
                                '',
                                'TEST ABORTED — EXISTING PARENT FAILED VERIFICATION.',
                                '',
                                'WooCommerce parent ID: ' .
                                    $parentId,
                                '',
                                'FAILURES:',
                                ...$parentFailures,
                                '',
                                'No WooCommerce variation was created.',
                                '',
                                'FINAL STATUS: ABORTED',
                            ]
                        )
                    ) .
                    '</pre>'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Pre-Creation Variation Safety Check
            |--------------------------------------------------------------------------
            |
            | The executor also enforces this rule.
            |
            | We check here so the admin test can distinguish a deliberately
            | aborted duplicate test from an unexpected execution failure.
            |
            */

            $existingVariationIds =
                get_posts(
                    [
                        'post_type' =>
                            'product_variation',

                        'post_status' =>
                            'any',

                        'posts_per_page' =>
                            2,

                        'fields' =>
                            'ids',

                        'post_parent' =>
                            $parentId,

                        'meta_query' =>
                            [
                                'relation' =>
                                    'AND',

                                [
                                    'key' =>
                                        '_blackprint_managed',

                                    'value' =>
                                        'yes',
                                ],

                                [
                                    'key' =>
                                        '_blackprint_supplier',

                                    'value' =>
                                        $expectedVariantSupplier,
                                ],

                                [
                                    'key' =>
                                        '_blackprint_variant_code',

                                    'value' =>
                                        $expectedFullCode,
                                ],
                            ],
                    ]
                );

            if (
                is_array($existingVariationIds)
                && $existingVariationIds !== []
            ) {

                wp_die(
                    '<pre>' .
                    esc_html(
                        implode(
                            "\n",
                            [
                                '12.1.2 CONTROLLED VARIANT CREATION',
                                str_repeat(
                                    '=',
                                    64
                                ),
                                '',
                                'TEST ABORTED — SELECTED VARIANT ALREADY EXISTS.',
                                '',
                                'Parent WooCommerce ID: ' .
                                    $parentId,
                                'Supplier: ' .
                                    $expectedVariantSupplier,
                                'Simple code: ' .
                                    $expectedSimpleCode,
                                'Full code: ' .
                                    $expectedFullCode,
                                'SKU: ' .
                                    $expectedSku,
                                'Existing variation ID(s): ' .
                                    wp_json_encode(
                                        $existingVariationIds
                                    ),
                                '',
                                'No WooCommerce variation was created.',
                                '',
                                'FINAL STATUS: ABORTED',
                            ]
                        )
                    ) .
                    '</pre>'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Capture Parent Variation State
            |--------------------------------------------------------------------------
            */

            $beforeVariationIds =
                $existingParent->get_children();

            if (
                ! is_array($beforeVariationIds)
            ) {

                $beforeVariationIds = [];
            }

            /*
            |--------------------------------------------------------------------------
            | Execute Controlled Variant Creation
            |--------------------------------------------------------------------------
            */

            $executionResult =
                $executor->execute(
                    $projection
                );

            if (
                ! $executionResult->success()
            ) {

                wp_die(
                    '<pre>' .
                    esc_html(
                        implode(
                            "\n",
                            [
                                '12.1.2 CONTROLLED VARIANT CREATION',
                                str_repeat(
                                    '=',
                                    64
                                ),
                                '',
                                'EXECUTION FAILED',
                                '',
                                'Parent WooCommerce ID: ' .
                                    $parentId,
                                'Variant full code: ' .
                                    $expectedFullCode,
                                'Message: ' .
                                    (
                                        $executionResult->message()
                                        ?? 'Unknown execution error.'
                                    ),
                                '',
                                'FINAL STATUS: FAILED',
                            ]
                        )
                    ) .
                    '</pre>'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Verify Result Action
            |--------------------------------------------------------------------------
            */

            if (
                $executionResult->action()
                !== 'created'
            ) {

                wp_die(
                    '<pre>' .
                    esc_html(
                        implode(
                            "\n",
                            [
                                '12.1.2 CONTROLLED VARIANT CREATION',
                                str_repeat(
                                    '=',
                                    64
                                ),
                                '',
                                'EXECUTION DID NOT CREATE A VARIATION.',
                                '',
                                'Returned action: ' .
                                    $executionResult->action(),
                                'Message: ' .
                                    (
                                        $executionResult->message()
                                        ?? 'None'
                                    ),
                                '',
                                'FINAL STATUS: FAILED',
                            ]
                        )
                    ) .
                    '</pre>'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Created Variation ID
            |--------------------------------------------------------------------------
            */

            $createdVariationId =
                $executionResult->productId();

            if (
                $createdVariationId === null
                || $createdVariationId <= 0
            ) {

                wp_die(
                    'Variant creation test failed: executor returned an invalid variation ID.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Reload Created Variation
            |--------------------------------------------------------------------------
            */

            $createdVariation =
                wc_get_product(
                    $createdVariationId
                );

            if (
                ! $createdVariation
            ) {

                wp_die(
                    'Variant creation test failed: created WooCommerce variation could not be reloaded.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Reload Parent
            |--------------------------------------------------------------------------
            */

            $verifiedParent =
                wc_get_product(
                    $parentId
                );

            if (
                ! $verifiedParent
            ) {

                wp_die(
                    'Variant creation test failed: parent could not be reloaded after variation creation.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Verification
            |--------------------------------------------------------------------------
            */

            $failures = [];

            /*
            |--------------------------------------------------------------------------
            | Variation Type
            |--------------------------------------------------------------------------
            */

            if (
                $createdVariation->get_type()
                !== 'variation'
            ) {

                $failures[] =
                    'Created product is not a variation.';
            }

            /*
            |--------------------------------------------------------------------------
            | Parent Relationship
            |--------------------------------------------------------------------------
            */

            if (
                $createdVariation->get_parent_id()
                !== $parentId
            ) {

                $failures[] =
                    'Variation parent relationship does not match the selected parent.';
            }

            /*
            |--------------------------------------------------------------------------
            | Parent Children
            |--------------------------------------------------------------------------
            */

            $afterVariationIds =
                $verifiedParent->get_children();

            if (
                ! is_array($afterVariationIds)
            ) {

                $afterVariationIds = [];
            }

            if (
                ! in_array(
                    $createdVariationId,
                    $afterVariationIds,
                    true
                )
            ) {

                $failures[] =
                    'Created variation is not registered as a child of the parent.';
            }

            /*
            |--------------------------------------------------------------------------
            | Exactly One New Variation
            |--------------------------------------------------------------------------
            */

            $newVariationIds =
                array_values(
                    array_diff(
                        $afterVariationIds,
                        $beforeVariationIds
                    )
                );

            if (
                count($newVariationIds) !== 1
            ) {

                $failures[] =
                    'Expected exactly one new variation, found ' .
                    count($newVariationIds) .
                    '.';
            }

            if (
                $newVariationIds !== []
                && $newVariationIds[0] !== $createdVariationId
            ) {

                $failures[] =
                    'Executor returned a variation ID different from the newly created child.';
            }

            /*
            |--------------------------------------------------------------------------
            | SKU
            |--------------------------------------------------------------------------
            */

            if (
                $createdVariation->get_sku()
                !== $expectedSku
            ) {

                $failures[] =
                    'Variation SKU does not match the projected SKU.';
            }

            /*
            |--------------------------------------------------------------------------
            | Full Code Identity
            |--------------------------------------------------------------------------
            */

            if (
                $createdVariation->get_meta(
                    '_blackprint_variant_code'
                )
                !== $expectedFullCode
            ) {

                $failures[] =
                    '_blackprint_variant_code does not match.';
            }

            /*
            |--------------------------------------------------------------------------
            | Simple Code Identity
            |--------------------------------------------------------------------------
            */

            if (
                $createdVariation->get_meta(
                    '_blackprint_simple_code'
                )
                !== $expectedSimpleCode
            ) {

                $failures[] =
                    '_blackprint_simple_code does not match.';
            }

            /*
            |--------------------------------------------------------------------------
            | Supplier Ownership
            |--------------------------------------------------------------------------
            */

            if (
                $createdVariation->get_meta(
                    '_blackprint_supplier'
                )
                !== $expectedVariantSupplier
            ) {

                $failures[] =
                    '_blackprint_supplier does not match.';
            }

            /*
            |--------------------------------------------------------------------------
            | Managed Ownership
            |--------------------------------------------------------------------------
            */

            if (
                $createdVariation->get_meta(
                    '_blackprint_managed'
                )
                !== 'yes'
            ) {

                $failures[] =
                    '_blackprint_managed is not yes.';
            }

            /*
            |--------------------------------------------------------------------------
            | Projected Attributes
            |--------------------------------------------------------------------------
            */

            $actualAttributes =
                $createdVariation->get_attributes();

            if (
                ! is_array($actualAttributes)
            ) {

                $actualAttributes = [];
            }

            foreach (
                $expectedAttributes
                as $attributeName => $expectedAttributeValue
            ) {

                if (
                    ! array_key_exists(
                        $attributeName,
                        $actualAttributes
                    )
                ) {

                    $failures[] =
                        'Missing projected attribute: ' .
                        $attributeName .
                        '.';

                    continue;
                }

                if (
                    $actualAttributes[$attributeName]
                    !== $expectedAttributeValue
                ) {

                    $failures[] =
                        'Projected attribute does not match: ' .
                        $attributeName .
                        '.';
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Final Status
            |--------------------------------------------------------------------------
            */

            $status =
                $failures === []
                    ? 'PASS'
                    : 'FAILED';

            /*
            |--------------------------------------------------------------------------
            | Report
            |--------------------------------------------------------------------------
            */

            $output = [];

            $output[] =
                'TEST VERSION: WOOCOMMERCE CONTROLLED VARIANT CREATION v1';

            $output[] = '';

            $output[] =
                'BlackPrint OS — 12.1.2 Controlled Variant Creation';

            $output[] =
                str_repeat(
                    '=',
                    64
                );

            $output[] = '';

            $output[] =
                'SNAPSHOT';

            $output[] =
                str_repeat(
                    '-',
                    64
                );

            $output[] =
                'UUID: ' .
                $snapshotUuid;

            $output[] = '';

            $output[] =
                'PARENT';

            $output[] =
                str_repeat(
                    '-',
                    64
                );

            $output[] =
                'Supplier:              ' .
                $expectedSupplier;

            $output[] =
                'Supplier product ID:   ' .
                $expectedProductId;

            $output[] =
                'Supplier product code: ' .
                $expectedProductCode;

            $output[] =
                'WooCommerce parent ID: ' .
                $parentId;

            $output[] =
                'Parent type:           ' .
                $verifiedParent->get_type();

            $output[] =
                'BlackPrint managed:    ' .
                (
                    $verifiedParent->get_meta(
                        '_blackprint_managed'
                    ) === 'yes'
                        ? 'YES'
                        : 'NO'
                );

            $output[] = '';

            $output[] =
                'VARIANT';

            $output[] =
                str_repeat(
                    '-',
                    64
                );

            $output[] =
                'Supplier:              ' .
                $expectedVariantSupplier;

            $output[] =
                'Simple code:           ' .
                $expectedSimpleCode;

            $output[] =
                'Full code:             ' .
                $expectedFullCode;

            $output[] =
                'SKU:                   ' .
                $expectedSku;

            $output[] = '';

            $output[] =
                'WOOCOMMERCE CREATION';

            $output[] =
                str_repeat(
                    '-',
                    64
                );

            $output[] =
                'Created variation ID:  ' .
                $createdVariationId;

            $output[] =
                'Variation type:        ' .
                $createdVariation->get_type();

            $output[] =
                'Parent relationship:   ' .
                (
                    $createdVariation->get_parent_id()
                    === $parentId
                        ? 'YES'
                        : 'NO'
                );

            $output[] =
                'SKU verified:          ' .
                (
                    $createdVariation->get_sku()
                    === $expectedSku
                        ? 'YES'
                        : 'NO'
                );

            $output[] =
                'New variation count:   ' .
                count($newVariationIds);

            $output[] = '';

            $output[] =
                'BLACKPRINT OWNERSHIP';

            $output[] =
                str_repeat(
                    '-',
                    64
                );

            $output[] =
                '_blackprint_managed:       ' .
                $createdVariation->get_meta(
                    '_blackprint_managed'
                );

            $output[] =
                '_blackprint_supplier:      ' .
                $createdVariation->get_meta(
                    '_blackprint_supplier'
                );

            $output[] =
                '_blackprint_variant_code:  ' .
                $createdVariation->get_meta(
                    '_blackprint_variant_code'
                );

            $output[] =
                '_blackprint_simple_code:   ' .
                $createdVariation->get_meta(
                    '_blackprint_simple_code'
                );

            $output[] = '';

            $output[] =
                'ATTRIBUTE VERIFICATION';

            $output[] =
                str_repeat(
                    '-',
                    64
                );

            if (
                $expectedAttributes === []
            ) {

                $output[] =
                    'Projected attributes: NONE';

                $output[] =
                    'Attributes verified:   YES';

            } else {

                foreach (
                    $expectedAttributes
                    as $attributeName => $expectedAttributeValue
                ) {

                    $attributeVerified =
                        array_key_exists(
                            $attributeName,
                            $actualAttributes
                        )
                        && $actualAttributes[$attributeName]
                            === $expectedAttributeValue;

                    $output[] =
                        $attributeName .
                        ': ' .
                        (
                            $attributeVerified
                                ? 'YES'
                                : 'NO'
                        );
                }
            }

            $output[] = '';

            $output[] =
                'VERIFICATION';

            $output[] =
                str_repeat(
                    '-',
                    64
                );

            $output[] =
                'Variation type verified:       ' .
                (
                    $createdVariation->get_type()
                    === 'variation'
                        ? 'YES'
                        : 'NO'
                );

            $output[] =
                'Parent relationship verified:  ' .
                (
                    $createdVariation->get_parent_id()
                    === $parentId
                        ? 'YES'
                        : 'NO'
                );

            $output[] =
                'Variation child registered:    ' .
                (
                    in_array(
                        $createdVariationId,
                        $afterVariationIds,
                        true
                    )
                        ? 'YES'
                        : 'NO'
                );

            $output[] =
                'Exactly one new variation:     ' .
                (
                    count($newVariationIds) === 1
                        ? 'YES'
                        : 'NO'
                );

            $output[] =
                'SKU verified:                   ' .
                (
                    $createdVariation->get_sku()
                    === $expectedSku
                        ? 'YES'
                        : 'NO'
                );

            $output[] =
                'Full code verified:             ' .
                (
                    $createdVariation->get_meta(
                        '_blackprint_variant_code'
                    )
                    === $expectedFullCode
                        ? 'YES'
                        : 'NO'
                );

            $output[] =
                'Simple code verified:           ' .
                (
                    $createdVariation->get_meta(
                        '_blackprint_simple_code'
                    )
                    === $expectedSimpleCode
                        ? 'YES'
                        : 'NO'
                );

            $output[] =
                'Ownership verified:             ' .
                (
                    $createdVariation->get_meta(
                        '_blackprint_managed'
                    )
                    === 'yes'
                        ? 'YES'
                        : 'NO'
                );

            $output[] = '';

            if (
                $failures === []
            ) {

                $output[] =
                    'FINAL STATUS: PASS';

            } else {

                $output[] =
                    'FAILURES';

                $output[] =
                    str_repeat(
                        '-',
                        64
                    );

                foreach (
                    $failures
                    as $failure
                ) {

                    $output[] =
                        '• ' .
                        $failure;
                }

                $output[] = '';

                $output[] =
                    'FINAL STATUS: FAILED';
            }

            wp_die(
                '<pre>' .
                esc_html(
                    implode(
                        "\n",
                        $output
                    )
                ) .
                '</pre>'
            );

        } catch (
            \Throwable $exception
        ) {

            wp_die(
                '<pre>' .
                esc_html(
                    implode(
                        "\n",
                        [
                            '12.1.2 CONTROLLED VARIANT CREATION',
                            str_repeat(
                                '=',
                                64
                            ),
                            '',
                            'UNEXPECTED TEST ERROR',
                            '',
                            'Message: ' .
                                $exception->getMessage(),
                            '',
                            'FINAL STATUS: FAILED',
                        ]
                    )
                ) .
                '</pre>'
            );
        }
    }

}
