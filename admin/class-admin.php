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

        add_action(
            'admin_post_bp_commit_woocommerce_ownership',
            [
                $this,
                'commit_woocommerce_ownership',
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
        | WooCommerce Adoption
        |--------------------------------------------------------------------------
        |
        | Controlled Step 5B hand-off for committing verified BlackPrint
        | ownership metadata to existing WooCommerce products and variations.
        |
        */

        add_submenu_page(
            'blackprint-commerce',
            'WooCommerce Adoption',
            'WooCommerce Adoption',
            'manage_woocommerce',
            'blackprint-woocommerce-adoption',
            [
                $this,
                'woocommerce_adoption',
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
 * Render the controlled WooCommerce adoption page.
 *
 * Step 5B is the controlled ownership hand-off from the verified
 * adoption mapping artifact into existing WooCommerce products
 * and variations.
 *
 * Rendering this page performs no WooCommerce writes.
 * The actual write occurs only through the dedicated admin-post
 * commit handler after explicit user submission.
 */
public function woocommerce_adoption(): void
{
    $artifactId = '';

    $artifact = null;

    $artifactError = '';

    try {

        $store =
            new \BlackPrint\Commerce\Projection\Adoption\VerifiedAdoptionMappingStore();

        $artifact =
            $store->loadLatestVerified();

        if (
            ! is_array($artifact)
            || empty($artifact['artifact_id'])
        ) {
            throw new \RuntimeException(
                'No valid Step 5B verified adoption artifact is available.'
            );
        }

        $artifactId =
            (string) $artifact['artifact_id'];

    } catch (\Throwable $exception) {

        $artifact = null;

        $artifactId = '';

        $artifactError =
            $exception->getMessage();
    }

    include BP_COMMERCE_PATH
        . 'admin/views/woocommerce-adoption.php';
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
| Adoption Candidate Analysis
|--------------------------------------------------------------------------
|
| Read-only decision analysis built from the legacy reconciliation
| and family reconciliation results.
|
| This determines whether an existing legacy WooCommerce product
| has sufficient deterministic evidence to be adopted by BlackPrint.
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

/*
|--------------------------------------------------------------------------
| Candidate Collections
|--------------------------------------------------------------------------
*/

$adoptionCandidates = [];

$adoptionStats = [
    'total_published_products'            => count(
        $legacyWooCommerceProducts
    ),

    'direct_identity_adopt'               => 0,

    'direct_variant_simple_adopt'         => 0,

    'direct_variant_variable_review'      => 0,

    'family_deterministic_adopt'          => 0,

    'family_deterministic_review'         => 0,

    'family_multiple_candidates_review'   => 0,

    'missing_sku_review'                  => 0,

    'unmatched_do_not_adopt'              => 0,

    'adopt'                               => 0,

    'review'                              => 0,

    'do_not_adopt'                        => 0,
];


/*
|--------------------------------------------------------------------------
| Direct deterministic matches
|--------------------------------------------------------------------------
|
| These were already established by the legacy product reconciliation.
|
*/

foreach (
    $legacyDeterministicMatches
    as $wooProductId => $match
) {

    $methods =
        $match['methods']
        ?? [];

    $productType =
        $match['product_type']
        ?? '';

    $sku =
        $match['sku']
        ?? '';

    /*
     * Missing SKU should never become an automatic adoption candidate.
     */
    if ($sku === '') {

        $classification =
            'REVIEW_MISSING_SKU';

        $decision =
            'REVIEW';

        $adoptionStats[
            'missing_sku_review'
        ]++;

        $adoptionStats[
            'review'
        ]++;

    /*
     * Strongest evidence:
     *
     * supplier_product_id and/or supplier_product_code.
     */
    } elseif (
        in_array(
            'supplier_product_id',
            $methods,
            true
        )
        ||
        in_array(
            'supplier_product_code',
            $methods,
            true
        )
    ) {

        $classification =
            'DIRECT_IDENTITY_MATCH';

        $decision =
            'ADOPT';

        $adoptionStats[
            'direct_identity_adopt'
        ]++;

        $adoptionStats[
            'adopt'
        ]++;

    /*
     * Variant fullCode is sufficient for a simple product because
     * the WooCommerce product itself represents the sellable entity.
     */
    } elseif (
        in_array(
            'variant_full_code',
            $methods,
            true
        )
        && $productType === 'simple'
    ) {

        $classification =
            'DIRECT_VARIANT_MATCH_SIMPLE';

        $decision =
            'ADOPT';

        $adoptionStats[
            'direct_variant_simple_adopt'
        ]++;

        $adoptionStats[
            'adopt'
        ]++;

    /*
     * A variable parent matching only a canonical fullCode is not
     * automatically safe. The parent may represent a broader legacy
     * family than the canonical product.
     */
    } elseif (
        in_array(
            'variant_full_code',
            $methods,
            true
        )
        && $productType === 'variable'
    ) {

        $classification =
            'REVIEW_VARIANT_ONLY_PARENT';

        $decision =
            'REVIEW';

        $adoptionStats[
            'direct_variant_variable_review'
        ]++;

        $adoptionStats[
            'review'
        ]++;

    } else {

        /*
         * Defensive fallback.
         */
        $classification =
            'REVIEW_UNCLASSIFIED';

        $decision =
            'REVIEW';

        $adoptionStats[
            'review'
        ]++;
    }

    $adoptionCandidates[$wooProductId] = [
        ...$match,

        'classification' =>
            $classification,

        'decision' =>
            $decision,

        'adoption_reason' =>
            $classification,
    ];
}


/*
|--------------------------------------------------------------------------
| Family deterministic matches
|--------------------------------------------------------------------------
|
| A family match is only automatically adoptable when ALL published
| WooCommerce child variations with usable SKUs are represented by
| the same canonical family AND the canonical family has the same
| number of variants.
|
| This prevents a partial child-variant match from becoming ownership.
|
*/

foreach (
    $legacyFamilyDeterministicMatches
    as $wooProductId => $familyMatch
) {

    $variationCount =
        (int) (
            $familyMatch['variation_count']
            ?? 0
        );

    $matchedVariationCount =
        (int) (
            $familyMatch['matched_variation_count']
            ?? 0
        );

    $canonicalVariantCount =
        (int) (
            $familyMatch['canonical'][
                'variant_count'
            ]
            ?? 0
        );

    $hasCompleteWooCommerceFamily =
        $variationCount > 0
        && $matchedVariationCount === $variationCount;

    $hasCompleteCanonicalFamily =
        $canonicalVariantCount > 0
        && $matchedVariationCount === $canonicalVariantCount;

    if (
        $hasCompleteWooCommerceFamily
        && $hasCompleteCanonicalFamily
    ) {

        $classification =
            'FAMILY_DETERMINISTIC';

        $decision =
            'ADOPT';

        $adoptionStats[
            'family_deterministic_adopt'
        ]++;

        $adoptionStats[
            'adopt'
        ]++;

        $reason =
            'All WooCommerce child variations reconcile to one complete canonical family.';

    } else {

        $classification =
            'FAMILY_DETERMINISTIC_INCOMPLETE';

        $decision =
            'REVIEW';

        $adoptionStats[
            'family_deterministic_review'
        ]++;

        $adoptionStats[
            'review'
        ]++;

        $reason =
            'One canonical family was identified, but the WooCommerce and canonical variant sets are not completely reconciled.';
    }

    $adoptionCandidates[$wooProductId] = [
        ...$familyMatch,

        'classification' =>
            $classification,

        'decision' =>
            $decision,

        'adoption_reason' =>
            $reason,
    ];
}


/*
|--------------------------------------------------------------------------
| Multiple canonical family candidates
|--------------------------------------------------------------------------
|
| These are structurally ambiguous and must remain review-only.
|
*/

foreach (
    $legacyFamilyCandidateMatches
    as $wooProductId => $familyMatch
) {

    $adoptionCandidates[$wooProductId] = [
        ...$familyMatch,

        'classification' =>
            'FAMILY_MULTIPLE_CANDIDATES',

        'decision' =>
            'REVIEW',

        'adoption_reason' =>
            'Child variation SKUs map to multiple canonical product families.',
    ];

    $adoptionStats[
        'family_multiple_candidates_review'
    ]++;

    $adoptionStats[
        'review'
    ]++;
}


/*
|--------------------------------------------------------------------------
| Missing SKU / unmatched products
|--------------------------------------------------------------------------
|
| Anything remaining in the unmatched collection has no deterministic
| direct product match.
|
*/

foreach (
    $legacyUnmatchedProducts
    as $wooProductId => $wooProduct
) {

    /*
     * Family reconciliation may already have classified this product.
     */
    if (
        isset(
            $adoptionCandidates[$wooProductId]
        )
    ) {
        continue;
    }

    if (
        ($wooProduct['sku'] ?? '') === ''
    ) {

        $classification =
            'REVIEW_MISSING_SKU';

        $decision =
            'REVIEW';

        $reason =
            'WooCommerce product has no SKU, so deterministic canonical ownership cannot be established.';

        $adoptionStats[
            'missing_sku_review'
        ]++;

        $adoptionStats[
            'review'
        ]++;

    } else {

        $classification =
            'UNMATCHED';

        $decision =
            'DO NOT ADOPT';

        $reason =
            'No deterministic canonical product or variant evidence was found.';

        $adoptionStats[
            'unmatched_do_not_adopt'
        ]++;

        $adoptionStats[
            'do_not_adopt'
        ]++;
    }

    $adoptionCandidates[$wooProductId] = [
        ...$wooProduct,

        'classification' =>
            $classification,

        'decision' =>
            $decision,

        'adoption_reason' =>
            $reason,

        'canonical' =>
            null,
    ];
}


/*
|--------------------------------------------------------------------------
| Adoption Candidate Samples
|--------------------------------------------------------------------------
*/

$adoptionDirectSample = [];

$adoptionFamilySample = [];

$adoptionReviewSample = [];

$adoptionDoNotAdoptSample = [];

foreach (
    $adoptionCandidates
    as $candidate
) {

    $decision =
        $candidate['decision']
        ?? '';

    $classification =
        $candidate['classification']
        ?? '';

    if (
        $decision === 'ADOPT'
        && str_starts_with(
            $classification,
            'DIRECT_'
        )
        && count($adoptionDirectSample) < 20
    ) {

        $adoptionDirectSample[] =
            $candidate;

    } elseif (
        $classification === 'FAMILY_DETERMINISTIC'
        && count($adoptionFamilySample) < 20
    ) {

        $adoptionFamilySample[] =
            $candidate;

    } elseif (
        $decision === 'REVIEW'
        && count($adoptionReviewSample) < 20
    ) {

        $adoptionReviewSample[] =
            $candidate;

    } elseif (
        $decision === 'DO NOT ADOPT'
        && count($adoptionDoNotAdoptSample) < 20
    ) {

        $adoptionDoNotAdoptSample[] =
            $candidate;
    }
}


/*
|--------------------------------------------------------------------------
| Adoption Candidate Analysis Report
|--------------------------------------------------------------------------
*/

echo '<h3>Adoption Candidate Analysis</h3>';

echo '<pre>';

echo "ADOPTION CANDIDATE ANALYSIS\n";
echo "----------------------------------------------------------\n";

echo "Total published WooCommerce products: "
    . $adoptionStats[
        'total_published_products'
    ]
    . "\n";

echo "\n";

echo "SAFE TO ADOPT\n";
echo "----------------------------------------------------------\n";

echo "Direct identity matches: "
    . $adoptionStats[
        'direct_identity_adopt'
    ]
    . "\n";

echo "Direct variant matches — simple products: "
    . $adoptionStats[
        'direct_variant_simple_adopt'
    ]
    . "\n";

echo "Deterministic complete family matches: "
    . $adoptionStats[
        'family_deterministic_adopt'
    ]
    . "\n";

echo "TOTAL ADOPT: "
    . $adoptionStats['adopt']
    . "\n";

echo "\n";

echo "REVIEW REQUIRED\n";
echo "----------------------------------------------------------\n";

echo "Variant-only variable parents: "
    . $adoptionStats[
        'direct_variant_variable_review'
    ]
    . "\n";

echo "Incomplete deterministic family matches: "
    . $adoptionStats[
        'family_deterministic_review'
    ]
    . "\n";

echo "Multiple canonical family candidates: "
    . $adoptionStats[
        'family_multiple_candidates_review'
    ]
    . "\n";

echo "Missing SKU: "
    . $adoptionStats[
        'missing_sku_review'
    ]
    . "\n";

echo "TOTAL REVIEW: "
    . $adoptionStats['review']
    . "\n";

echo "\n";

echo "DO NOT ADOPT\n";
echo "----------------------------------------------------------\n";

echo "No deterministic canonical evidence: "
    . $adoptionStats[
        'unmatched_do_not_adopt'
    ]
    . "\n";

echo "TOTAL DO NOT ADOPT: "
    . $adoptionStats['do_not_adopt']
    . "\n";

echo "\n";


/*
|--------------------------------------------------------------------------
| Direct Adoption Sample
|--------------------------------------------------------------------------
*/

echo "DIRECT ADOPTION SAMPLE\n";
echo "----------------------------------------------------------\n";

foreach (
    $adoptionDirectSample
    as $candidate
) {

    echo "WooCommerce Product ID: "
        . $candidate['product_id']
        . "\n";

    echo "Type: "
        . $candidate['product_type']
        . "\n";

    echo "SKU: "
        . (
            $candidate['sku']
            !== ''
                ? $candidate['sku']
                : '[NO SKU]'
        )
        . "\n";

    echo "Title: "
        . $candidate['title']
        . "\n";

    echo "Classification: "
        . $candidate['classification']
        . "\n";

    echo "Decision: "
        . $candidate['decision']
        . "\n";

    echo "Match method(s): "
        . implode(
            ', ',
            $candidate['methods']
            ?? []
        )
        . "\n";

    echo "Canonical supplier_product_id: "
        . (
            $candidate['canonical'][
                'supplier_product_id'
            ]
            ?? 'N/A'
        )
        . "\n";

    echo "Canonical supplier_product_code: "
        . (
            $candidate['canonical'][
                'supplier_product_code'
            ]
            ?? 'N/A'
        )
        . "\n";

    echo "\n";
}


/*
|--------------------------------------------------------------------------
| Family Adoption Sample
|--------------------------------------------------------------------------
*/

echo "DETERMINISTIC FAMILY ADOPTION SAMPLE\n";
echo "----------------------------------------------------------\n";

foreach (
    $adoptionFamilySample
    as $candidate
) {

    echo "WooCommerce Product ID: "
        . $candidate['product_id']
        . "\n";

    echo "Type: "
        . $candidate['product_type']
        . "\n";

    echo "SKU: "
        . (
            $candidate['sku']
            !== ''
                ? $candidate['sku']
                : '[NO SKU]'
        )
        . "\n";

    echo "Title: "
        . $candidate['title']
        . "\n";

    echo "Classification: "
        . $candidate['classification']
        . "\n";

    echo "Decision: "
        . $candidate['decision']
        . "\n";

    echo "WooCommerce variations: "
        . (
            $candidate['variation_count']
            ?? 0
        )
        . "\n";

    echo "Matched variations: "
        . (
            $candidate['matched_variation_count']
            ?? 0
        )
        . "\n";

    echo "Canonical variants: "
        . (
            $candidate['canonical'][
                'variant_count'
            ]
            ?? 0
        )
        . "\n";

    echo "Canonical supplier_product_id: "
        . (
            $candidate['canonical'][
                'supplier_product_id'
            ]
            ?? 'N/A'
        )
        . "\n";

    echo "Canonical supplier_product_code: "
        . (
            $candidate['canonical'][
                'supplier_product_code'
            ]
            ?? 'N/A'
        )
        . "\n";

    echo "Reason: "
        . $candidate['adoption_reason']
        . "\n";

    echo "\n";
}


/*
|--------------------------------------------------------------------------
| Review Sample
|--------------------------------------------------------------------------
*/

echo "REVIEW SAMPLE\n";
echo "----------------------------------------------------------\n";

foreach (
    $adoptionReviewSample
    as $candidate
) {

    echo "WooCommerce Product ID: "
        . $candidate['product_id']
        . "\n";

    echo "Type: "
        . $candidate['product_type']
        . "\n";

    echo "SKU: "
        . (
            $candidate['sku']
            !== ''
                ? $candidate['sku']
                : '[NO SKU]'
        )
        . "\n";

    echo "Title: "
        . $candidate['title']
        . "\n";

    echo "Classification: "
        . $candidate['classification']
        . "\n";

    echo "Decision: "
        . $candidate['decision']
        . "\n";

    echo "Reason: "
        . $candidate['adoption_reason']
        . "\n";

    echo "\n";
}


/*
|--------------------------------------------------------------------------
| Do Not Adopt Sample
|--------------------------------------------------------------------------
*/

echo "DO NOT ADOPT SAMPLE\n";
echo "----------------------------------------------------------\n";

foreach (
    $adoptionDoNotAdoptSample
    as $candidate
) {

    echo "WooCommerce Product ID: "
        . $candidate['product_id']
        . "\n";

    echo "Type: "
        . $candidate['product_type']
        . "\n";

    echo "SKU: "
        . (
            $candidate['sku']
            !== ''
                ? $candidate['sku']
                : '[NO SKU]'
        )
        . "\n";

    echo "Title: "
        . $candidate['title']
        . "\n";

    echo "Classification: "
        . $candidate['classification']
        . "\n";

    echo "Decision: "
        . $candidate['decision']
        . "\n";

    echo "Reason: "
        . $candidate['adoption_reason']
        . "\n";

    echo "\n";
}

echo '</pre>';


/*
|--------------------------------------------------------------------------
| Step 3 — Adoption Mapping Analysis
|--------------------------------------------------------------------------
|
| Read-only.
|
| Converts the ADOPT decisions produced by Step 2 into explicit
| WooCommerce -> Canonical mappings.
|
| IMPORTANT:
| Step 3 does not perform new reconciliation logic.
| It uses the deterministic decisions and canonical lookup structures
| already established above.
|
| No WooCommerce writes are performed here.
|
*/

echo '<h2>ADOPTION MAPPING ANALYSIS</h2>';

$adoptionMappings = [];

$adoptionMappingErrors   = [];
$adoptionMappingWarnings = [];

$mappedCanonicalProducts = [];
$mappedCanonicalVariants = [];

/*
|--------------------------------------------------------------------------
| Counters
|--------------------------------------------------------------------------
*/

$approvedAdoptionCount = 0;

$directIdentityMappingCount = 0;
$directVariantMappingCount  = 0;
$familyMappingCount         = 0;

$variableParentMappingCount = 0;
$simpleProductMappingCount  = 0;

$fullyMappedVariableCount = 0;
$incompleteVariableCount  = 0;

/*
|--------------------------------------------------------------------------
| Iterate only over candidates whose decision is ADOPT.
|--------------------------------------------------------------------------
*/

foreach ($adoptionCandidates as $productId => $candidate) {

    if (
        !isset($candidate['decision']) ||
        $candidate['decision'] !== 'ADOPT'
    ) {
        continue;
    }

    $approvedAdoptionCount++;

    $product = wc_get_product($productId);

    if (!$product) {

        $adoptionMappingErrors[$productId] = [
            'product_id' => $productId,
            'reason'     => 'WooCommerce product could not be loaded.',
        ];

        continue;
    }

    $classification = $candidate['classification'] ?? '';

    /*
     * ------------------------------------------------------------------
     * Canonical parent identity
     * ------------------------------------------------------------------
     */

    $canonical = $candidate['canonical'] ?? null;

    if (
        !is_array($canonical) ||
        empty($canonical['supplier_product_id'])
    ) {

        $adoptionMappingErrors[$productId] = [
            'product_id'     => $productId,
            'sku'            => $product->get_sku(),
            'classification' => $classification,
            'reason'         => 'ADOPT candidate has no canonical supplier_product_id.',
        ];

        continue;
    }

    $canonicalProductId =
        (string) $canonical['supplier_product_id'];

    $canonicalProductCode =
        isset($canonical['supplier_product_code'])
            ? (string) $canonical['supplier_product_code']
            : '';

    /*
     * ------------------------------------------------------------------
     * Detect duplicate canonical product targets.
     *
     * We do NOT silently overwrite an existing mapping.
     * ------------------------------------------------------------------
     */

    if (isset($mappedCanonicalProducts[$canonicalProductId])) {

        $existingProductId =
            $mappedCanonicalProducts[$canonicalProductId];

        $adoptionMappingErrors[$productId] = [
            'product_id'           => $productId,
            'sku'                  => $product->get_sku(),
            'canonical_product_id' => $canonicalProductId,
            'canonical_product_code' => $canonicalProductCode,
            'reason'               => sprintf(
                'Canonical product is already mapped to WooCommerce product %d.',
                $existingProductId
            ),
        ];

        continue;
    }

    $mappedCanonicalProducts[$canonicalProductId] = $productId;

    /*
     * ------------------------------------------------------------------
     * Base mapping.
     * ------------------------------------------------------------------
     */

    $mapping = [
        'woocommerce_product_id'    => $productId,
        'woocommerce_type'          => $product->get_type(),
        'woocommerce_sku'           => (string) $product->get_sku(),
        'classification'            => $classification,
        'decision'                  => 'ADOPT',
        'canonical_product_id'      => $canonicalProductId,
        'canonical_product_code'    => $canonicalProductCode,
        'canonical_variant_count'   => (int) (
            $canonical['variant_count'] ?? 0
        ),
        'woocommerce_variant_count' => 0,
        'matched_variant_count'     => 0,
        'variants'                  => [],
    ];

    /*
     * ------------------------------------------------------------------
     * SIMPLE PRODUCT
     * ------------------------------------------------------------------
     */

    if ($product->is_type('simple')) {

        $simpleProductMappingCount++;

        /*
         * Direct variant match:
         *
         * The WooCommerce SKU itself is the canonical fullCode.
         */
        if (
            $classification === 'DIRECT_VARIANT_MATCH_SIMPLE'
        ) {

            $sku = trim((string) $product->get_sku());

            if ($sku === '') {

                $adoptionMappingErrors[$productId] = [
                    'product_id'    => $productId,
                    'classification'=> $classification,
                    'reason'        => 'Direct simple variant adoption has no SKU.',
                ];

                unset($mappedCanonicalProducts[$canonicalProductId]);

                continue;
            }

            if (
                !isset($legacyCanonicalVariantLookup[$sku]) ||
                !is_array($legacyCanonicalVariantLookup[$sku])
            ) {

                $adoptionMappingErrors[$productId] = [
                    'product_id'           => $productId,
                    'sku'                  => $sku,
                    'canonical_product_id' => $canonicalProductId,
                    'reason'               => 'SKU is not present in the canonical variant lookup.',
                ];

                unset($mappedCanonicalProducts[$canonicalProductId]);

                continue;
            }

            /*
             * The variant lookup is:
             *
             * fullCode => [
             *     supplier_product_id => identity
             * ]
             *
             * Therefore resolve the canonical identity from the
             * nested supplier product ID.
             */

            $variantMatches =
                $legacyCanonicalVariantLookup[$sku];

            if (count($variantMatches) !== 1) {

                $adoptionMappingErrors[$productId] = [
                    'product_id' => $productId,
                    'sku'        => $sku,
                    'reason'     => sprintf(
                        'Canonical variant SKU resolves to %d canonical products instead of exactly one.',
                        count($variantMatches)
                    ),
                ];

                unset($mappedCanonicalProducts[$canonicalProductId]);

                continue;
            }

            $variantIdentity =
                reset($variantMatches);

            $resolvedCanonicalProductId =
                (string) (
                    $variantIdentity['supplier_product_id'] ?? ''
                );

            if (
                $resolvedCanonicalProductId !==
                $canonicalProductId
            ) {

                $adoptionMappingErrors[$productId] = [
                    'product_id'           => $productId,
                    'sku'                  => $sku,
                    'canonical_product_id' => $canonicalProductId,
                    'resolved_product_id'  => $resolvedCanonicalProductId,
                    'reason'               => 'Variant fullCode resolves to a different canonical product.',
                ];

                unset($mappedCanonicalProducts[$canonicalProductId]);

                continue;
            }

            $mapping['variants'][] = [
                'woocommerce_variation_id' => null,
                'woocommerce_sku'          => $sku,
                'canonical_variant_code'   => $sku,
            ];

            $mapping['woocommerce_variant_count'] = 1;
            $mapping['matched_variant_count']     = 1;

            $mappedCanonicalVariants[$sku] = true;
        }

        /*
         * Direct identity simple products are mapped at the
         * canonical product level.
         *
         * We deliberately do NOT assume that their SKU is a
         * canonical fullCode.
         */

        $adoptionMappings[$productId] = $mapping;

        if ($classification === 'DIRECT_VARIANT_MATCH_SIMPLE') {
            $directVariantMappingCount++;
        } else {
            $directIdentityMappingCount++;
        }

        continue;
    }

    /*
     * ------------------------------------------------------------------
     * VARIABLE PRODUCT
     * ------------------------------------------------------------------
     */

    if ($product->is_type('variable')) {

        $variableParentMappingCount++;

        $variationIds = $product->get_children();

        $mapping['woocommerce_variant_count'] =
            count($variationIds);

        /*
         * Child-level mapping.
         *
         * Every child SKU is resolved through the canonical
         * fullCode lookup.
         */

        foreach ($variationIds as $variationId) {

            $variation = wc_get_product($variationId);

            if (!$variation) {

                $adoptionMappingWarnings[$productId][] = [
                    'variation_id' => $variationId,
                    'reason'       => 'WooCommerce variation could not be loaded.',
                ];

                continue;
            }

            $variationSku =
                trim((string) $variation->get_sku());

            if ($variationSku === '') {

                $adoptionMappingWarnings[$productId][] = [
                    'variation_id' => $variationId,
                    'reason'       => 'WooCommerce variation has no SKU.',
                ];

                continue;
            }

            /*
             * Resolve exact canonical fullCode.
             */

            if (
                !isset($legacyCanonicalVariantLookup[$variationSku]) ||
                !is_array($legacyCanonicalVariantLookup[$variationSku])
            ) {

                $adoptionMappingWarnings[$productId][] = [
                    'variation_id' => $variationId,
                    'sku'          => $variationSku,
                    'reason'       => 'WooCommerce variation SKU has no canonical fullCode match.',
                ];

                continue;
            }

            $variantMatches =
                $legacyCanonicalVariantLookup[$variationSku];

            /*
             * A variant fullCode must resolve to exactly one
             * canonical product.
             */

            if (count($variantMatches) !== 1) {

                $adoptionMappingErrors[$productId][] = [
                    'variation_id' => $variationId,
                    'sku'          => $variationSku,
                    'reason'       => sprintf(
                        'Canonical variant fullCode resolves to %d canonical products.',
                        count($variantMatches)
                    ),
                ];

                continue;
            }

            $variantIdentity =
                reset($variantMatches);

            $resolvedCanonicalProductId =
                (string) (
                    $variantIdentity['supplier_product_id'] ?? ''
                );

            /*
             * The child variant must belong to the same canonical
             * product family represented by the deterministic
             * adoption mapping.
             */

            if (
                $resolvedCanonicalProductId !==
                $canonicalProductId
            ) {

                /*
                 * This is a warning rather than a fatal mapping error
                 * for direct identity products.
                 *
                 * The parent identity was already deterministically
                 * established in Step 2.
                 *
                 * We therefore record the discrepancy for review
                 * without destroying the parent mapping.
                 */

                $adoptionMappingWarnings[$productId][] = [
                    'variation_id' => $variationId,
                    'sku'          => $variationSku,
                    'reason'       => 'WooCommerce variation resolves to a canonical product different from the deterministic parent identity.',
                    'resolved_product_id' => $resolvedCanonicalProductId,
                ];

                continue;
            }

                $mapping['variants'][] = [
                        'woocommerce_variation_id' => $variationId,
                        'woocommerce_sku'          => $variationSku,
                        'canonical_variant_code'   => (string) (
                    $variantIdentity['full_code'] ?? $variationSku
                ),            ];

            $mapping['matched_variant_count']++;

            $mappedCanonicalVariants[$variationSku] = true;
        }

        /*
         * Family deterministic mappings already passed the complete
         * family test in Step 2.
         *
         * We nevertheless verify the resulting child mapping here.
         */

        if (
            $classification === 'FAMILY_DETERMINISTIC'
        ) {

            $familyMappingCount++;

            if (
                $mapping['matched_variant_count'] !==
                $mapping['woocommerce_variant_count']
            ) {

                $incompleteVariableCount++;

                $adoptionMappingWarnings[$productId][] = [
                    'reason' => sprintf(
                        'Deterministic family child mapping is incomplete: %d of %d WooCommerce variations mapped.',
                        $mapping['matched_variant_count'],
                        $mapping['woocommerce_variant_count']
                    ),
                ];
            } else {

                $fullyMappedVariableCount++;
            }

        } else {

            /*
             * Direct identity variable products.
             */

            if (
                $mapping['matched_variant_count'] ===
                $mapping['woocommerce_variant_count']
                &&
                $mapping['woocommerce_variant_count'] > 0
            ) {
                $fullyMappedVariableCount++;
            } else {

                $incompleteVariableCount++;

                if (
                    $mapping['woocommerce_variant_count'] > 0
                ) {

                    $adoptionMappingWarnings[$productId][] = [
                        'reason' => sprintf(
                            'Variable product child mapping is incomplete: %d of %d WooCommerce variations mapped.',
                            $mapping['matched_variant_count'],
                            $mapping['woocommerce_variant_count']
                        ),
                    ];
                }
            }
        }

        $adoptionMappings[$productId] = $mapping;

        $directIdentityMappingCount += (
            $classification === 'DIRECT_IDENTITY_MATCH'
                ? 1
                : 0
        );

        continue;
    }

    /*
     * ------------------------------------------------------------------
     * Unsupported WooCommerce product type.
     * ------------------------------------------------------------------
     */

    $adoptionMappingErrors[$productId] = [
        'product_id'    => $productId,
        'classification'=> $classification,
        'reason'        => 'Unsupported WooCommerce product type.',
    ];

    unset($mappedCanonicalProducts[$canonicalProductId]);
}

/*
|--------------------------------------------------------------------------
| Recalculate mapped variant count.
|--------------------------------------------------------------------------
*/

$mappedCanonicalVariantCount =
    count($mappedCanonicalVariants);

/*
|--------------------------------------------------------------------------
| Report
|--------------------------------------------------------------------------
*/

echo '<pre>';

echo "ADOPTION MAPPING ANALYSIS\n";
echo str_repeat('-', 58) . "\n";

echo "Approved adoption candidates: "
    . $approvedAdoptionCount
    . "\n";

echo "Explicit WooCommerce → canonical mappings: "
    . count($adoptionMappings)
    . "\n";

echo "\n";

echo "DIRECT IDENTITY MAPPINGS\n";
echo str_repeat('-', 58) . "\n";
echo "Mappings: "
    . $directIdentityMappingCount
    . "\n";

echo "\n";

echo "DIRECT SIMPLE VARIANT MAPPINGS\n";
echo str_repeat('-', 58) . "\n";
echo "Mappings: "
    . $directVariantMappingCount
    . "\n";

echo "\n";

echo "DETERMINISTIC FAMILY MAPPINGS\n";
echo str_repeat('-', 58) . "\n";
echo "Mappings: "
    . $familyMappingCount
    . "\n";

echo "\n";

echo "VARIABLE PARENT MAPPINGS\n";
echo str_repeat('-', 58) . "\n";
echo "Variable parents: "
    . $variableParentMappingCount
    . "\n";

echo "Fully mapped variable parents: "
    . $fullyMappedVariableCount
    . "\n";

echo "Incomplete variable mappings: "
    . $incompleteVariableCount
    . "\n";

echo "\n";

echo "SIMPLE PRODUCT MAPPINGS\n";
echo str_repeat('-', 58) . "\n";
echo "Simple products: "
    . $simpleProductMappingCount
    . "\n";

echo "\n";

echo "MAPPED CANONICAL PRODUCTS\n";
echo str_repeat('-', 58) . "\n";
echo count($mappedCanonicalProducts) . "\n";

echo "\n";

echo "MAPPED CANONICAL VARIANTS\n";
echo str_repeat('-', 58) . "\n";
echo $mappedCanonicalVariantCount . "\n";

echo "\n";

echo "MAPPING ERRORS\n";
echo str_repeat('-', 58) . "\n";
echo count($adoptionMappingErrors) . "\n";

echo "\n";

echo "MAPPING WARNINGS\n";
echo str_repeat('-', 58) . "\n";
echo count($adoptionMappingWarnings) . "\n";

/*
|--------------------------------------------------------------------------
| Compact error summary.
|--------------------------------------------------------------------------
|
| Do not dump thousands of child-level errors into the report.
|--------------------------------------------------------------------------
*/

if (!empty($adoptionMappingErrors)) {

    echo "\n";
    echo "MAPPING ERROR SAMPLE\n";
    echo str_repeat('-', 58) . "\n";

    $errorSampleCount = 0;

    foreach ($adoptionMappingErrors as $productId => $error) {

        if ($errorSampleCount >= 10) {
            break;
        }

        echo "WC Product ID: "
            . $productId
            . "\n";

        if (
            is_array($error) &&
            isset($error['reason'])
        ) {

            echo "Reason: "
                . $error['reason']
                . "\n";

        } elseif (is_array($error)) {

            $childErrorCount = count($error);

            echo "Child-level mapping errors: "
                . $childErrorCount
                . "\n";

            $childSample = 0;

            foreach ($error as $childError) {

                if (
                    $childSample >= 3 ||
                    !is_array($childError)
                ) {
                    break;
                }

                echo "  Variation ID: "
                    . ($childError['variation_id'] ?? 'n/a')
                    . "\n";

                echo "  SKU: "
                    . ($childError['sku'] ?? 'n/a')
                    . "\n";

                echo "  Reason: "
                    . ($childError['reason'] ?? 'n/a')
                    . "\n";

                $childSample++;
            }
        }

        echo "\n";

        $errorSampleCount++;
    }
}

/*
|--------------------------------------------------------------------------
| Compact warning summary.
|--------------------------------------------------------------------------
*/

if (!empty($adoptionMappingWarnings)) {

    echo "\n";
    echo "MAPPING WARNING SAMPLE\n";
    echo str_repeat('-', 58) . "\n";

    $warningSampleCount = 0;

    foreach ($adoptionMappingWarnings as $productId => $warnings) {

        if ($warningSampleCount >= 10) {
            break;
        }

        echo "WC Product ID: "
            . $productId
            . "\n";

        if (is_array($warnings)) {

            $childWarningCount = count($warnings);

            echo "Warnings: "
                . $childWarningCount
                . "\n";

            $childSample = 0;

            foreach ($warnings as $warning) {

                if (
                    $childSample >= 3 ||
                    !is_array($warning)
                ) {
                    break;
                }

                echo "  Variation ID: "
                    . ($warning['variation_id'] ?? 'n/a')
                    . "\n";

                echo "  SKU: "
                    . ($warning['sku'] ?? 'n/a')
                    . "\n";

                echo "  Reason: "
                    . ($warning['reason'] ?? 'n/a')
                    . "\n";

                if (
                    isset($warning['resolved_product_id'])
                ) {

                    echo "  Resolved Canonical Product ID: "
                        . $warning['resolved_product_id']
                        . "\n";
                }

                $childSample++;
            }
        }

        echo "\n";

        $warningSampleCount++;
    }
}

/*
|--------------------------------------------------------------------------
| Compact mapping samples.
|--------------------------------------------------------------------------
*/

echo "\n";
echo "MAPPING SAMPLE\n";
echo str_repeat('-', 58) . "\n";

$mappingSampleCount = 0;

foreach ($adoptionMappings as $mapping) {

    if ($mappingSampleCount >= 8) {
        break;
    }

    echo "\n";

    echo "WC Product ID: "
        . $mapping['woocommerce_product_id']
        . "\n";

    echo "Type: "
        . $mapping['woocommerce_type']
        . "\n";

    echo "SKU: "
        . (
            $mapping['woocommerce_sku'] !== ''
                ? $mapping['woocommerce_sku']
                : 'NONE'
        )
        . "\n";

    echo "Classification: "
        . $mapping['classification']
        . "\n";

    echo "Canonical Product ID: "
        . $mapping['canonical_product_id']
        . "\n";

    echo "Canonical Product Code: "
        . (
            $mapping['canonical_product_code'] !== ''
                ? $mapping['canonical_product_code']
                : 'NONE'
        )
        . "\n";

    echo "Canonical Variant Count: "
        . $mapping['canonical_variant_count']
        . "\n";

    echo "WooCommerce Variant Count: "
        . $mapping['woocommerce_variant_count']
        . "\n";

    echo "Matched Variant Count: "
        . $mapping['matched_variant_count']
        . "\n";

    if (!empty($mapping['variants'])) {

        echo "Variant Mapping Sample:\n";

        foreach (
            array_slice($mapping['variants'], 0, 5)
            as $variant
        ) {

            echo "  ";

            if (
                $variant['woocommerce_variation_id'] !== null
            ) {

                echo "WC Variation "
                    . $variant['woocommerce_variation_id']
                    . " → ";
            }

            echo $variant['woocommerce_sku']
                . " → Canonical "
                . $variant['canonical_variant_code']
                . "\n";
        }
    }

    $mappingSampleCount++;
}

echo '</pre>';


/* -------------------------------------------------------------------------
 * STEP 4 — ADOPTION MAPPING VERIFICATION
 * ---------------------------------------------------------------------- */

echo '<h2>ADOPTION MAPPING VERIFICATION</h2>';

/*
|--------------------------------------------------------------------------
| Verification containers
|--------------------------------------------------------------------------
*/

$verificationErrors   = [];
$verificationWarnings = [];

$verifiedWooCommerceProducts = [];
$verifiedCanonicalProducts   = [];
$verifiedCanonicalVariants   = [];

/*
|--------------------------------------------------------------------------
| Verification counters
|--------------------------------------------------------------------------
*/

$verificationApprovedCount       = 0;
$verificationMappingCount        = 0;
$verificationProductTargetCount  = 0;
$verificationVariantCount        = 0;

$missingProductTargets           = 0;
$multipleProductTargets          = 0;
$duplicateCanonicalClaims        = 0;

$missingCanonicalVariants         = 0;
$ambiguousCanonicalVariants       = 0;
$duplicateCanonicalVariants       = 0;

$crossFamilyVariants             = 0;
$incompleteFamilies              = 0;

$fullyVerifiedProducts            = 0;
$fullyVerifiedVariables           = 0;

/*
|--------------------------------------------------------------------------
| 4.1 Determine exactly how many approved candidates exist.
|--------------------------------------------------------------------------
|
| This must equal Step 3:
|
|     Approved adoption candidates: 3710
|
|--------------------------------------------------------------------------
*/

foreach ($adoptionCandidates as $productId => $candidate) {

    if (
        isset($candidate['decision']) &&
        $candidate['decision'] === 'ADOPT'
    ) {
        $verificationApprovedCount++;
    }
}

/*
|--------------------------------------------------------------------------
| 4.2 Verify every approved candidate has exactly one mapping.
|--------------------------------------------------------------------------
*/

foreach ($adoptionCandidates as $productId => $candidate) {

    if (
        !isset($candidate['decision']) ||
        $candidate['decision'] !== 'ADOPT'
    ) {
        continue;
    }

    /*
     * Every approved product must exist in $adoptionMappings.
     */
    if (!isset($adoptionMappings[$productId])) {

        $missingProductTargets++;

        $verificationErrors[] = [
            'product_id' => $productId,
            'reason'     => 'APPROVED_PRODUCT_HAS_NO_ADOPTION_MAPPING',
        ];

        continue;
    }

    $mapping = $adoptionMappings[$productId];

    $verificationMappingCount++;

    /*
     * ------------------------------------------------------------------
     * Verify WooCommerce product identity.
     * ------------------------------------------------------------------
     */

    $mappedWooCommerceProductId =
        isset($mapping['woocommerce_product_id'])
            ? (int) $mapping['woocommerce_product_id']
            : 0;

    if ($mappedWooCommerceProductId !== (int) $productId) {

        $multipleProductTargets++;

        $verificationErrors[] = [
            'product_id' => $productId,
            'mapped_product_id' => $mappedWooCommerceProductId,
            'reason' => 'MAPPING_REFERENCES_DIFFERENT_WOOCOMMERCE_PRODUCT',
        ];

        continue;
    }

    /*
     * ------------------------------------------------------------------
     * Verify canonical product target.
     * ------------------------------------------------------------------
     */

    $canonicalProductId =
        isset($mapping['canonical_product_id'])
            ? trim((string) $mapping['canonical_product_id'])
            : '';

    $canonicalProductCode =
        isset($mapping['canonical_product_code'])
            ? trim((string) $mapping['canonical_product_code'])
            : '';

    if ($canonicalProductId === '') {

        $missingProductTargets++;

        $verificationErrors[] = [
            'product_id' => $productId,
            'reason'     => 'MAPPING_HAS_NO_CANONICAL_PRODUCT_ID',
        ];

        continue;
    }

    /*
     * A canonical product must not be claimed by multiple
     * WooCommerce products.
     */
    if (
        isset($verifiedCanonicalProducts[$canonicalProductId]) &&
        (int) $verifiedCanonicalProducts[$canonicalProductId] !==
            (int) $productId
    ) {

        $duplicateCanonicalClaims++;

        $verificationErrors[] = [
            'product_id' => $productId,
            'canonical_product_id' => $canonicalProductId,
            'existing_product_id' =>
                $verifiedCanonicalProducts[$canonicalProductId],
            'reason' => 'DUPLICATE_CANONICAL_PRODUCT_CLAIM',
        ];

        continue;
    }

    $verifiedCanonicalProducts[$canonicalProductId] =
        (int) $productId;

    $verificationProductTargetCount++;

    $verifiedWooCommerceProducts[(int) $productId] =
        $canonicalProductId;

    /*
     * Canonical product code is useful for diagnostics, but the
     * supplier product ID is the authoritative identity.
     */
    if ($canonicalProductCode === '') {

        $verificationWarnings[] = [
            'product_id' => $productId,
            'canonical_product_id' => $canonicalProductId,
            'reason' => 'CANONICAL_PRODUCT_CODE_MISSING',
        ];
    }

    /*
     * ------------------------------------------------------------------
     * 4.3 Verify mapping type.
     * ------------------------------------------------------------------
     */

    $woocommerceType =
        isset($mapping['woocommerce_type'])
            ? (string) $mapping['woocommerce_type']
            : '';

    /*
     * ------------------------------------------------------------------
     * SIMPLE PRODUCTS
     * ------------------------------------------------------------------
     */

    if ($woocommerceType === 'simple') {

        /*
         * A simple direct-identity product does not necessarily have
         * a canonical fullCode in its WooCommerce SKU.
         *
         * Only verify a variant when Step 3 actually created one.
         */
        if (!empty($mapping['variants'])) {

            foreach ($mapping['variants'] as $variant) {

                $canonicalVariantCode =
                    isset($variant['canonical_variant_code'])
                        ? trim((string) $variant['canonical_variant_code'])
                        : '';

                $woocommerceSku =
                    isset($variant['woocommerce_sku'])
                        ? trim((string) $variant['woocommerce_sku'])
                        : '';

                if ($canonicalVariantCode === '') {

                    $missingCanonicalVariants++;

                    $verificationErrors[] = [
                        'product_id' => $productId,
                        'sku'        => $woocommerceSku,
                        'reason'     => 'MAPPED_SIMPLE_VARIANT_HAS_NO_CANONICAL_FULL_CODE',
                    ];

                    continue;
                }

                /*
                 * Resolve the canonical variant through the actual
                 * nested lookup structure:
                 *
                 * fullCode => [
                 *     supplier_product_id => identity
                 * ]
                 */
                if (
                    !isset(
                        $legacyCanonicalVariantLookup[
                            $canonicalVariantCode
                        ]
                    )
                ) {

                    $missingCanonicalVariants++;

                    $verificationErrors[] = [
                        'product_id' => $productId,
                        'sku'        => $woocommerceSku,
                        'canonical_variant_code' =>
                            $canonicalVariantCode,
                        'reason' =>
                            'CANONICAL_FULL_CODE_NOT_FOUND',
                    ];

                    continue;
                }

                $variantMatches =
                    $legacyCanonicalVariantLookup[
                        $canonicalVariantCode
                    ];

                if (
                    !is_array($variantMatches) ||
                    count($variantMatches) !== 1
                ) {

                    $ambiguousCanonicalVariants++;

                    $verificationErrors[] = [
                        'product_id' => $productId,
                        'sku'        => $woocommerceSku,
                        'canonical_variant_code' =>
                            $canonicalVariantCode,
                        'candidate_count' =>
                            is_array($variantMatches)
                                ? count($variantMatches)
                                : 0,
                        'reason' =>
                            'CANONICAL_FULL_CODE_IS_AMBIGUOUS',
                    ];

                    continue;
                }

                $variantIdentity =
                    reset($variantMatches);

                $resolvedProductId =
                    isset($variantIdentity['supplier_product_id'])
                        ? trim(
                            (string)
                            $variantIdentity['supplier_product_id']
                        )
                        : '';

                if ($resolvedProductId !== $canonicalProductId) {

                    $verificationErrors[] = [
                        'product_id' =>
                            $productId,
                        'sku' =>
                            $woocommerceSku,
                        'canonical_variant_code' =>
                            $canonicalVariantCode,
                        'mapped_product_id' =>
                            $canonicalProductId,
                        'resolved_product_id' =>
                            $resolvedProductId,
                        'reason' =>
                            'CANONICAL_VARIANT_RESOLVES_TO_DIFFERENT_PRODUCT',
                    ];

                    continue;
                }

                /*
                 * Verify the same canonical fullCode is not being
                 * represented by multiple different WooCommerce
                 * products.
                 */
                if (
                    isset(
                        $verifiedCanonicalVariants[
                            $canonicalVariantCode
                        ]
                    )
                ) {

                    $existingVariant =
                        $verifiedCanonicalVariants[
                            $canonicalVariantCode
                        ];

                    if (
                        $existingVariant['woocommerce_product_id']
                        !== (int) $productId
                    ) {

                        $duplicateCanonicalVariants++;

                        $verificationErrors[] = [
                            'product_id' =>
                                $productId,
                            'sku' =>
                                $woocommerceSku,
                            'canonical_variant_code' =>
                                $canonicalVariantCode,
                            'existing_product_id' =>
                                $existingVariant[
                                    'woocommerce_product_id'
                                ],
                            'reason' =>
                                'DUPLICATE_CANONICAL_VARIANT_CLAIM',
                        ];

                        continue;
                    }
                }

                $verifiedCanonicalVariants[
                    $canonicalVariantCode
                ] = [
                    'woocommerce_product_id' =>
                        (int) $productId,
                    'woocommerce_variation_id' =>
                        null,
                ];

                $verificationVariantCount++;
            }
        }

        continue;
    }

    /*
     * ------------------------------------------------------------------
     * VARIABLE PRODUCTS
     * ------------------------------------------------------------------
     */

    if ($woocommerceType === 'variable') {

        $woocommerceVariantCount =
            isset($mapping['woocommerce_variant_count'])
                ? (int) $mapping['woocommerce_variant_count']
                : 0;

        $mappedVariantCount =
            isset($mapping['matched_variant_count'])
                ? (int) $mapping['matched_variant_count']
                : 0;

        /*
         * Verify the mapping count itself.
         */
        $actualMappingArrayCount =
            isset($mapping['variants']) &&
            is_array($mapping['variants'])
                ? count($mapping['variants'])
                : 0;

        if ($actualMappingArrayCount !== $mappedVariantCount) {

            $verificationErrors[] = [
                'product_id' => $productId,
                'expected_mapped_variants' =>
                    $mappedVariantCount,
                'actual_mapped_variants' =>
                    $actualMappingArrayCount,
                'reason' =>
                    'MAPPED_VARIANT_COUNT_DOES_NOT_MATCH_VARIANT_ARRAY',
            ];
        }

        /*
         * Verify every mapped child.
         */
        if (
            isset($mapping['variants']) &&
            is_array($mapping['variants'])
        ) {

            foreach ($mapping['variants'] as $variant) {

                $variationId =
                    isset($variant['woocommerce_variation_id'])
                        ? (int) $variant[
                            'woocommerce_variation_id'
                        ]
                        : 0;

                $woocommerceSku =
                    isset($variant['woocommerce_sku'])
                        ? trim(
                            (string)
                            $variant['woocommerce_sku']
                        )
                        : '';

                $canonicalVariantCode =
                    isset($variant['canonical_variant_code'])
                        ? trim(
                            (string)
                            $variant['canonical_variant_code']
                        )
                        : '';

                if ($variationId <= 0) {

                    $verificationErrors[] = [
                        'product_id' => $productId,
                        'sku' => $woocommerceSku,
                        'reason' =>
                            'MAPPED_VARIABLE_CHILD_HAS_INVALID_VARIATION_ID',
                    ];

                    continue;
                }

                if ($canonicalVariantCode === '') {

                    $missingCanonicalVariants++;

                    $verificationErrors[] = [
                        'product_id' => $productId,
                        'variation_id' => $variationId,
                        'sku' => $woocommerceSku,
                        'reason' =>
                            'MAPPED_VARIABLE_CHILD_HAS_NO_CANONICAL_FULL_CODE',
                    ];

                    continue;
                }

                /*
                 * Exact canonical fullCode lookup.
                 */
                if (
                    !isset(
                        $legacyCanonicalVariantLookup[
                            $canonicalVariantCode
                        ]
                    )
                ) {

                    $missingCanonicalVariants++;

                    $verificationErrors[] = [
                        'product_id' => $productId,
                        'variation_id' => $variationId,
                        'sku' => $woocommerceSku,
                        'canonical_variant_code' =>
                            $canonicalVariantCode,
                        'reason' =>
                            'CANONICAL_FULL_CODE_NOT_FOUND',
                    ];

                    continue;
                }

                $variantMatches =
                    $legacyCanonicalVariantLookup[
                        $canonicalVariantCode
                    ];

                if (
                    !is_array($variantMatches) ||
                    count($variantMatches) !== 1
                ) {

                    $ambiguousCanonicalVariants++;

                    $verificationErrors[] = [
                        'product_id' => $productId,
                        'variation_id' => $variationId,
                        'sku' => $woocommerceSku,
                        'canonical_variant_code' =>
                            $canonicalVariantCode,
                        'candidate_count' =>
                            is_array($variantMatches)
                                ? count($variantMatches)
                                : 0,
                        'reason' =>
                            'CANONICAL_FULL_CODE_IS_AMBIGUOUS',
                    ];

                    continue;
                }

                $variantIdentity =
                    reset($variantMatches);

                $resolvedProductId =
                    isset($variantIdentity['supplier_product_id'])
                        ? trim(
                            (string)
                            $variantIdentity[
                                'supplier_product_id'
                            ]
                        )
                        : '';

                /*
                 * A child resolving to another canonical family is
                 * deliberately a warning, not an error.
                 *
                 * This preserves the legacy MiniOrange structure
                 * without forcing the child into the wrong family.
                 */
                if ($resolvedProductId !== $canonicalProductId) {

                    $crossFamilyVariants++;

                    $verificationWarnings[] = [
                        'product_id' =>
                            $productId,
                        'variation_id' =>
                            $variationId,
                        'sku' =>
                            $woocommerceSku,
                        'canonical_variant_code' =>
                            $canonicalVariantCode,
                        'parent_canonical_product_id' =>
                            $canonicalProductId,
                        'resolved_canonical_product_id' =>
                            $resolvedProductId,
                        'reason' =>
                            'CROSS_FAMILY_VARIANT',
                    ];

                    /*
                     * It is still a valid canonical variant mapping.
                     * We therefore continue verifying the fullCode
                     * itself.
                     */
                }

                /*
                 * A canonical fullCode must not be claimed by two
                 * different WooCommerce products.
                 */
                if (
                    isset(
                        $verifiedCanonicalVariants[
                            $canonicalVariantCode
                        ]
                    )
                ) {

                    $existingVariant =
                        $verifiedCanonicalVariants[
                            $canonicalVariantCode
                        ];

                    if (
                        $existingVariant['woocommerce_product_id']
                        !== (int) $productId
                    ) {

                        $duplicateCanonicalVariants++;

                        $verificationErrors[] = [
                            'product_id' =>
                                $productId,
                            'variation_id' =>
                                $variationId,
                            'sku' =>
                                $woocommerceSku,
                            'canonical_variant_code' =>
                                $canonicalVariantCode,
                            'existing_product_id' =>
                                $existingVariant[
                                    'woocommerce_product_id'
                                ],
                            'reason' =>
                                'DUPLICATE_CANONICAL_VARIANT_CLAIM',
                        ];

                        continue;
                    }
                }

                $verifiedCanonicalVariants[
                    $canonicalVariantCode
                ] = [
                    'woocommerce_product_id' =>
                        (int) $productId,
                    'woocommerce_variation_id' =>
                        $variationId,
                ];

                $verificationVariantCount++;
            }
        }

        /*
         * ------------------------------------------------------------------
         * Family completeness verification.
         * ------------------------------------------------------------------
         */

        if ($woocommerceVariantCount > 0) {

            if (
                $mappedVariantCount ===
                $woocommerceVariantCount
            ) {

                $fullyVerifiedVariables++;

            } else {

                $incompleteFamilies++;

                $verificationWarnings[] = [
                    'product_id' =>
                        $productId,
                    'canonical_product_id' =>
                        $canonicalProductId,
                    'woocommerce_variation_count' =>
                        $woocommerceVariantCount,
                    'mapped_variant_count' =>
                        $mappedVariantCount,
                    'reason' =>
                        'INCOMPLETE_FAMILY',
                ];
            }
        }

        continue;
    }

    /*
     * ------------------------------------------------------------------
     * Unknown product type.
     * ------------------------------------------------------------------
     */

    $verificationErrors[] = [
        'product_id' => $productId,
        'woocommerce_type' => $woocommerceType,
        'reason' => 'UNKNOWN_WOOCOMMERCE_PRODUCT_TYPE',
    ];
}

/*
|--------------------------------------------------------------------------
| 4.4 Verify Step 3 mapping count.
|--------------------------------------------------------------------------
*/

if (
    $verificationApprovedCount !==
    $verificationMappingCount
) {

    $verificationErrors[] = [
        'reason' =>
            'APPROVED_CANDIDATE_COUNT_DOES_NOT_MATCH_MAPPING_COUNT',
        'approved_count' =>
            $verificationApprovedCount,
        'mapping_count' =>
            $verificationMappingCount,
    ];
}

/*
|--------------------------------------------------------------------------
| 4.5 Verification status.
|--------------------------------------------------------------------------
*/

$verificationErrorCount =
    count($verificationErrors);

$verificationWarningCount =
    count($verificationWarnings);

$verificationPass =
    $verificationApprovedCount === 3710
    &&
    $verificationMappingCount === 3710
    &&
    $missingProductTargets === 0
    &&
    $multipleProductTargets === 0
    &&
    $duplicateCanonicalClaims === 0
    &&
    $missingCanonicalVariants === 0
    &&
    $ambiguousCanonicalVariants === 0
    &&
    $duplicateCanonicalVariants === 0
    &&
    $verificationErrorCount === 0;

/*
|--------------------------------------------------------------------------
| REPORT
|--------------------------------------------------------------------------
*/

echo '<pre>';

echo "ADOPTION MAPPING VERIFICATION\n";
echo str_repeat('-', 58) . "\n";

echo "Approved adoption candidates: "
    . $verificationApprovedCount
    . "\n";

echo "Adoption mappings verified: "
    . $verificationMappingCount
    . "\n";

echo "Canonical product targets verified: "
    . $verificationProductTargetCount
    . "\n";

echo "\n";

echo "PRODUCT TARGET INTEGRITY\n";
echo str_repeat('-', 58) . "\n";

echo "Missing product targets: "
    . $missingProductTargets
    . "\n";

echo "Multiple WooCommerce targets: "
    . $multipleProductTargets
    . "\n";

echo "Duplicate canonical product claims: "
    . $duplicateCanonicalClaims
    . "\n";

echo "\n";

echo "VARIANT MAPPING INTEGRITY\n";
echo str_repeat('-', 58) . "\n";

echo "Canonical variants verified: "
    . $verificationVariantCount
    . "\n";

echo "Missing canonical variants: "
    . $missingCanonicalVariants
    . "\n";

echo "Ambiguous canonical variants: "
    . $ambiguousCanonicalVariants
    . "\n";

echo "Duplicate canonical variant claims: "
    . $duplicateCanonicalVariants
    . "\n";

echo "\n";

echo "FAMILY VERIFICATION\n";
echo str_repeat('-', 58) . "\n";

echo "Fully verified variable families: "
    . $fullyVerifiedVariables
    . "\n";

echo "Incomplete families: "
    . $incompleteFamilies
    . "\n";

echo "Cross-family variants: "
    . $crossFamilyVariants
    . "\n";

echo "\n";

echo "VERIFICATION ERRORS\n";
echo str_repeat('-', 58) . "\n";

echo "Errors: "
    . $verificationErrorCount
    . "\n";

echo "\n";

echo "VERIFICATION WARNINGS\n";
echo str_repeat('-', 58) . "\n";

echo "Warnings: "
    . $verificationWarningCount
    . "\n";

echo "\n";

echo "STATUS: "
    . ($verificationPass ? 'PASS' : 'FAIL')
    . "\n";

/*
|--------------------------------------------------------------------------
| Error samples
|--------------------------------------------------------------------------
*/

if (!empty($verificationErrors)) {

    echo "\n";
    echo "VERIFICATION ERROR SAMPLE\n";
    echo str_repeat('-', 58) . "\n";

    foreach (
        array_slice($verificationErrors, 0, 10)
        as $error
    ) {

        echo "WC Product ID: "
            . ($error['product_id'] ?? 'n/a')
            . "\n";

        if (isset($error['variation_id'])) {
            echo "Variation ID: "
                . $error['variation_id']
                . "\n";
        }

        if (isset($error['sku'])) {
            echo "SKU: "
                . $error['sku']
                . "\n";
        }

        echo "Reason: "
            . ($error['reason'] ?? 'n/a')
            . "\n";

        echo "\n";
    }
}

/*
|--------------------------------------------------------------------------
| Warning samples
|--------------------------------------------------------------------------
*/

if (!empty($verificationWarnings)) {

    echo "\n";
    echo "VERIFICATION WARNING SAMPLE\n";
    echo str_repeat('-', 58) . "\n";

    foreach (
        array_slice($verificationWarnings, 0, 10)
        as $warning
    ) {

        echo "WC Product ID: "
            . ($warning['product_id'] ?? 'n/a')
            . "\n";

        if (isset($warning['variation_id'])) {
            echo "Variation ID: "
                . $warning['variation_id']
                . "\n";
        }

        if (isset($warning['sku'])) {
            echo "SKU: "
                . $warning['sku']
                . "\n";
        }

        echo "Reason: "
            . ($warning['reason'] ?? 'n/a')
            . "\n";

        if (isset($warning['parent_canonical_product_id'])) {
            echo "Parent Canonical Product: "
                . $warning['parent_canonical_product_id']
                . "\n";
        }

        if (isset($warning['resolved_canonical_product_id'])) {
            echo "Resolved Canonical Product: "
                . $warning['resolved_canonical_product_id']
                . "\n";
        }

        if (isset($warning['woocommerce_variation_count'])) {
            echo "WooCommerce Variations: "
                . $warning['woocommerce_variation_count']
                . "\n";
        }

        if (isset($warning['mapped_variant_count'])) {
            echo "Mapped Variants: "
                . $warning['mapped_variant_count']
                . "\n";
        }

        echo "\n";
    }
}

echo '</pre>';

/*
|--------------------------------------------------------------------------
| Step 5A — Controlled Adoption / Ownership Write — DRY RUN
|--------------------------------------------------------------------------
|
| Read-only.
|
| Consumes ONLY the verified $adoptionMappings produced by:
|
|   Step 3 — Adoption Mapping
|   Step 4 — Adoption Mapping Verification
|
| No new reconciliation is performed here.
| No WooCommerce writes are performed here.
|
| This step determines exactly which ownership metadata would be written
| during the controlled adoption commit.
|
| Parent ownership:
|   _blackprint_managed
|   _blackprint_supplier
|   _blackprint_product_id
|   _blackprint_product_code
|
| Variant ownership:
|   _blackprint_managed
|   _blackprint_supplier
|   _blackprint_variant_code
|
| Existing ownership is NEVER overwritten during the dry run.
|
*/

echo '<h2>CONTROLLED ADOPTION / OWNERSHIP WRITE — DRY RUN</h2>';
echo '<pre>';

$ownershipDryRunErrors = [];
$ownershipDryRunWarnings = [];

$ownershipWouldAdoptParents = [];
$ownershipAlreadyManagedParents = [];
$ownershipParentConflicts = [];

$ownershipWouldAdoptVariants = [];
$ownershipAlreadyManagedVariants = [];
$ownershipVariantConflicts = [];

$ownershipExcludedProducts = [];

$ownershipDryRunApprovedCount = 0;
$ownershipDryRunParentCount = 0;
$ownershipDryRunVariantCount = 0;


/*
|--------------------------------------------------------------------------
| Safety Guard
|--------------------------------------------------------------------------
|
| Step 5 must consume the verified adoption mappings.
|
*/

if (!isset($adoptionMappings) || !is_array($adoptionMappings)) {

    $ownershipDryRunErrors[] = [
        'reason' => 'ADOPTION_MAPPINGS_NOT_AVAILABLE',
    ];

} else {

    /*
    |--------------------------------------------------------------------------
    | Process verified adoption mappings only
    |--------------------------------------------------------------------------
    */

    foreach ($adoptionMappings as $productId => $mapping) {

        $productId = (int) $productId;

        if ($productId <= 0) {
            $ownershipDryRunErrors[] = [
                'product_id' => $productId,
                'reason'     => 'INVALID_WOOCOMMERCE_PRODUCT_ID',
            ];

            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | Mapping must explicitly be ADOPT
        |--------------------------------------------------------------------------
        */

        if (
            !isset($mapping['decision'])
            || $mapping['decision'] !== 'ADOPT'
        ) {
            $ownershipDryRunErrors[] = [
                'product_id' => $productId,
                'reason'     => 'MAPPING_IS_NOT_ADOPT',
            ];

            continue;
        }

        $ownershipDryRunApprovedCount++;

        /*
        |--------------------------------------------------------------------------
        | Canonical product identity
        |--------------------------------------------------------------------------
        */

        $canonicalProductId = isset($mapping['canonical_product_id'])
            ? (string) $mapping['canonical_product_id']
            : '';

        $canonicalProductCode = isset($mapping['canonical_product_code'])
            ? (string) $mapping['canonical_product_code']
            : '';

        if ($canonicalProductId === '') {

            $ownershipDryRunErrors[] = [
                'product_id' => $productId,
                'reason'     => 'MISSING_CANONICAL_PRODUCT_ID',
            ];

            continue;
        }

        if ($canonicalProductCode === '') {

            /*
            |--------------------------------------------------------------------------
            | Product code is important, but the canonical product ID is the
            | authoritative identity. Treat missing code as an error for the
            | controlled ownership write because we do not want to establish
            | incomplete ownership metadata.
            |--------------------------------------------------------------------------
            */

            $ownershipDryRunErrors[] = [
                'product_id'          => $productId,
                'canonical_product_id'=> $canonicalProductId,
                'reason'              => 'MISSING_CANONICAL_PRODUCT_CODE',
            ];

            continue;
        }

        $ownershipDryRunParentCount++;

        /*
        |--------------------------------------------------------------------------
        | Expected parent ownership
        |--------------------------------------------------------------------------
        */

        $expectedParentOwnership = [
            '_blackprint_managed'      => 'yes',
            '_blackprint_supplier'     => 'amrod',
            '_blackprint_product_id'   => $canonicalProductId,
            '_blackprint_product_code' => $canonicalProductCode,
        ];

        /*
        |--------------------------------------------------------------------------
        | Inspect existing parent ownership
        |--------------------------------------------------------------------------
        */

        $existingManaged = get_post_meta(
            $productId,
            '_blackprint_managed',
            true
        );

        $existingSupplier = get_post_meta(
            $productId,
            '_blackprint_supplier',
            true
        );

        $existingProductId = get_post_meta(
            $productId,
            '_blackprint_product_id',
            true
        );

        $existingProductCode = get_post_meta(
            $productId,
            '_blackprint_product_code',
            true
        );

        $hasExistingOwnership =
            $existingManaged !== ''
            || $existingSupplier !== ''
            || $existingProductId !== ''
            || $existingProductCode !== '';

        /*
        |--------------------------------------------------------------------------
        | Existing ownership classification
        |--------------------------------------------------------------------------
        */

        if (!$hasExistingOwnership) {

            $ownershipWouldAdoptParents[$productId] = [
                'woocommerce_product_id' => $productId,
                'canonical_product_id'   => $canonicalProductId,
                'canonical_product_code' => $canonicalProductCode,
            ];

        } elseif (
            (string) $existingManaged === 'yes'
            && (string) $existingSupplier === 'amrod'
            && (string) $existingProductId === $canonicalProductId
            && (string) $existingProductCode === $canonicalProductCode
        ) {

            $ownershipAlreadyManagedParents[$productId] = [
                'woocommerce_product_id' => $productId,
                'canonical_product_id'   => $canonicalProductId,
                'canonical_product_code' => $canonicalProductCode,
            ];

        } else {

            /*
            |--------------------------------------------------------------------------
            | Existing ownership conflicts with the verified adoption target.
            |
            | NEVER overwrite this automatically.
            |--------------------------------------------------------------------------
            */

            $ownershipParentConflicts[$productId] = [
                'woocommerce_product_id' => $productId,
                'expected'               => $expectedParentOwnership,
                'existing'               => [
                    '_blackprint_managed'      => (string) $existingManaged,
                    '_blackprint_supplier'     => (string) $existingSupplier,
                    '_blackprint_product_id'   => (string) $existingProductId,
                    '_blackprint_product_code' => (string) $existingProductCode,
                ],
            ];

            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | Variant ownership
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | We process ONLY variants explicitly present in the verified
        | adoption mapping.
        |
        | Unmapped legacy children are NOT adopted.
        |
        */

        $mappingVariants = isset($mapping['variants'])
            && is_array($mapping['variants'])
            ? $mapping['variants']
            : [];

        foreach ($mappingVariants as $variantMapping) {

            $variationId = isset($variantMapping['woocommerce_variation_id'])
                ? (int) $variantMapping['woocommerce_variation_id']
                : 0;

            $woocommerceSku = isset($variantMapping['woocommerce_sku'])
                ? (string) $variantMapping['woocommerce_sku']
                : '';

            $canonicalVariantCode = isset(
                $variantMapping['canonical_variant_code']
            )
                ? (string) $variantMapping['canonical_variant_code']
                : '';

            /*
            |--------------------------------------------------------------------------
            | Simple products have no variation ID.
            |
            | For those, the product itself represents the sellable variant,
            | but parent ownership is still handled above.
            |--------------------------------------------------------------------------
            */

            if ($variationId <= 0) {
                continue;
            }

            if ($canonicalVariantCode === '') {

                $ownershipDryRunErrors[] = [
                    'product_id'   => $productId,
                    'variation_id' => $variationId,
                    'reason'       => 'MISSING_CANONICAL_VARIANT_CODE',
                ];

                continue;
            }

            $ownershipDryRunVariantCount++;

            /*
            |--------------------------------------------------------------------------
            | Expected variant ownership
            |--------------------------------------------------------------------------
            */

            $expectedVariantOwnership = [
                '_blackprint_managed'      => 'yes',
                '_blackprint_supplier'     => 'amrod',
                '_blackprint_variant_code' => $canonicalVariantCode,
            ];

            /*
            |--------------------------------------------------------------------------
            | Inspect existing variant ownership
            |--------------------------------------------------------------------------
            */

            $existingVariantManaged = get_post_meta(
                $variationId,
                '_blackprint_managed',
                true
            );

            $existingVariantSupplier = get_post_meta(
                $variationId,
                '_blackprint_supplier',
                true
            );

            $existingVariantCode = get_post_meta(
                $variationId,
                '_blackprint_variant_code',
                true
            );

            $hasExistingVariantOwnership =
                $existingVariantManaged !== ''
                || $existingVariantSupplier !== ''
                || $existingVariantCode !== '';

            /*
            |--------------------------------------------------------------------------
            | Variant ownership classification
            |--------------------------------------------------------------------------
            */

            if (!$hasExistingVariantOwnership) {

                $ownershipWouldAdoptVariants[$variationId] = [
                    'woocommerce_product_id'   => $productId,
                    'woocommerce_variation_id' => $variationId,
                    'woocommerce_sku'          => $woocommerceSku,
                    'canonical_variant_code'   => $canonicalVariantCode,
                ];

            } elseif (
                (string) $existingVariantManaged === 'yes'
                && (string) $existingVariantSupplier === 'amrod'
                && (string) $existingVariantCode === $canonicalVariantCode
            ) {

                $ownershipAlreadyManagedVariants[$variationId] = [
                    'woocommerce_product_id'   => $productId,
                    'woocommerce_variation_id' => $variationId,
                    'woocommerce_sku'          => $woocommerceSku,
                    'canonical_variant_code'   => $canonicalVariantCode,
                ];

            } else {

                /*
                |--------------------------------------------------------------------------
                | Existing conflicting variant ownership.
                |
                | NEVER overwrite automatically.
                |--------------------------------------------------------------------------
                */

                $ownershipVariantConflicts[$variationId] = [
                    'woocommerce_product_id'   => $productId,
                    'woocommerce_variation_id' => $variationId,
                    'woocommerce_sku'          => $woocommerceSku,
                    'expected'                 => $expectedVariantOwnership,
                    'existing'                 => [
                        '_blackprint_managed'      => (string) $existingVariantManaged,
                        '_blackprint_supplier'     => (string) $existingVariantSupplier,
                        '_blackprint_variant_code' => (string) $existingVariantCode,
                    ],
                ];
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Explicit exclusion accounting
|--------------------------------------------------------------------------
|
| These are NOT written by Step 5.
|
*/

if (isset($adoptionCandidates) && is_array($adoptionCandidates)) {

    foreach ($adoptionCandidates as $productId => $candidate) {

        $decision = isset($candidate['decision'])
            ? (string) $candidate['decision']
            : '';

        if ($decision === 'ADOPT') {
            continue;
        }

        $ownershipExcludedProducts[$productId] = [
            'product_id' => (int) $productId,
            'decision'   => $decision,
        ];
    }
}


/*
|--------------------------------------------------------------------------
| Count exclusions by decision
|--------------------------------------------------------------------------
*/

$ownershipReviewCount = 0;
$ownershipDoNotAdoptCount = 0;

foreach ($ownershipExcludedProducts as $excluded) {

    if ($excluded['decision'] === 'REVIEW') {
        $ownershipReviewCount++;
        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | Any non-ADOPT, non-REVIEW candidate is treated as DO NOT ADOPT
    | for the ownership-write exclusion report.
    |
    | This keeps the write layer independent from the presentation label
    | used by Adoption Candidate Analysis.
    |--------------------------------------------------------------------------
    */

    $ownershipDoNotAdoptCount++;
}


/*
|--------------------------------------------------------------------------
| Final dry-run status
|--------------------------------------------------------------------------
|
| Any conflict or error prevents the dry run from being considered safe.
|
*/

$ownershipDryRunErrorCount =
    count($ownershipDryRunErrors);

$ownershipParentConflictCount =
    count($ownershipParentConflicts);

$ownershipVariantConflictCount =
    count($ownershipVariantConflicts);

$ownershipDryRunStatus =
    $ownershipDryRunErrorCount === 0
    && $ownershipParentConflictCount === 0
    && $ownershipVariantConflictCount === 0;


/*
|--------------------------------------------------------------------------
| REPORT
|--------------------------------------------------------------------------
*/

echo "CONTROLLED ADOPTION / OWNERSHIP WRITE — DRY RUN\n";
echo str_repeat('=', 58) . "\n\n";

echo "SOURCE\n";
echo str_repeat('-', 58) . "\n";
echo "Verified adoption mappings:       "
    . count($adoptionMappings ?? [])
    . "\n";

echo "Approved mappings processed:      "
    . $ownershipDryRunApprovedCount
    . "\n";

echo "Parent mappings inspected:        "
    . $ownershipDryRunParentCount
    . "\n";

echo "Variant mappings inspected:       "
    . $ownershipDryRunVariantCount
    . "\n\n";


echo "PARENT OWNERSHIP\n";
echo str_repeat('-', 58) . "\n";

echo "Would adopt:                       "
    . count($ownershipWouldAdoptParents)
    . "\n";

echo "Already correctly managed:        "
    . count($ownershipAlreadyManagedParents)
    . "\n";

echo "Conflicts:                         "
    . $ownershipParentConflictCount
    . "\n\n";


echo "VARIANT OWNERSHIP\n";
echo str_repeat('-', 58) . "\n";

echo "Would adopt:                       "
    . count($ownershipWouldAdoptVariants)
    . "\n";

echo "Already correctly managed:        "
    . count($ownershipAlreadyManagedVariants)
    . "\n";

echo "Conflicts:                         "
    . $ownershipVariantConflictCount
    . "\n\n";


echo "EXCLUSIONS\n";
echo str_repeat('-', 58) . "\n";

echo "Review products excluded:         "
    . $ownershipReviewCount
    . "\n";

echo "Do-not-adopt products excluded:   "
    . $ownershipDoNotAdoptCount
    . "\n";

echo "Total excluded products:          "
    . count($ownershipExcludedProducts)
    . "\n\n";


echo "PLANNED METADATA WRITES\n";
echo str_repeat('-', 58) . "\n";

echo "Parent metadata writes:            "
    . (count($ownershipWouldAdoptParents) * 4)
    . "\n";

echo "Variant metadata writes:           "
    . (count($ownershipWouldAdoptVariants) * 3)
    . "\n";

echo "Total metadata writes:             "
    . (
        (count($ownershipWouldAdoptParents) * 4)
        + (count($ownershipWouldAdoptVariants) * 3)
    )
    . "\n\n";


echo "SAFETY CHECKS\n";
echo str_repeat('-', 58) . "\n";

echo "No WooCommerce writes performed:   YES\n";
echo "Only verified mappings consumed:   "
    . (
        $ownershipDryRunApprovedCount === count($adoptionMappings ?? [])
            ? 'YES'
            : 'NO'
    )
    . "\n";

echo "Conflicting ownership preserved:  YES\n";
echo "Unmapped variants excluded:        YES\n\n";


echo "ERRORS\n";
echo str_repeat('-', 58) . "\n";

echo "Errors:                            "
    . $ownershipDryRunErrorCount
    . "\n";

echo "Parent ownership conflicts:        "
    . $ownershipParentConflictCount
    . "\n";

echo "Variant ownership conflicts:       "
    . $ownershipVariantConflictCount
    . "\n\n";


echo "STATUS: "
    . ($ownershipDryRunStatus ? 'PASS' : 'FAIL')
    . "\n";


/*
|--------------------------------------------------------------------------
| ERROR SAMPLES
|--------------------------------------------------------------------------
*/

if (!empty($ownershipDryRunErrors)) {

    echo "\n";
    echo "ERROR SAMPLES\n";
    echo str_repeat('-', 58) . "\n";

    $sampleErrors = array_slice(
        $ownershipDryRunErrors,
        0,
        10
    );

    foreach ($sampleErrors as $error) {

        echo "WC Product ID: "
            . ($error['product_id'] ?? 'N/A')
            . "\n";

        if (isset($error['variation_id'])) {
            echo "Variation ID:   "
                . $error['variation_id']
                . "\n";
        }

        echo "Reason: "
            . ($error['reason'] ?? 'UNKNOWN')
            . "\n\n";
    }
}


/*
|--------------------------------------------------------------------------
| PARENT CONFLICT SAMPLES
|--------------------------------------------------------------------------
*/

if (!empty($ownershipParentConflicts)) {

    echo "\n";
    echo "PARENT OWNERSHIP CONFLICT SAMPLES\n";
    echo str_repeat('-', 58) . "\n";

    $sampleConflicts = array_slice(
        $ownershipParentConflicts,
        0,
        10
    );

    foreach ($sampleConflicts as $conflict) {

        echo "WC Product ID: "
            . $conflict['woocommerce_product_id']
            . "\n";

        echo "Expected canonical product ID: "
            . $conflict['expected']['_blackprint_product_id']
            . "\n";

        echo "Existing canonical product ID: "
            . $conflict['existing']['_blackprint_product_id']
            . "\n";

        echo "Expected supplier: "
            . $conflict['expected']['_blackprint_supplier']
            . "\n";

        echo "Existing supplier: "
            . $conflict['existing']['_blackprint_supplier']
            . "\n\n";
    }
}


/*
|--------------------------------------------------------------------------
| VARIANT CONFLICT SAMPLES
|--------------------------------------------------------------------------
*/

if (!empty($ownershipVariantConflicts)) {

    echo "\n";
    echo "VARIANT OWNERSHIP CONFLICT SAMPLES\n";
    echo str_repeat('-', 58) . "\n";

    $sampleVariantConflicts = array_slice(
        $ownershipVariantConflicts,
        0,
        10
    );

    foreach ($sampleVariantConflicts as $conflict) {

        echo "WC Product ID: "
            . $conflict['woocommerce_product_id']
            . "\n";

        echo "Variation ID: "
            . $conflict['woocommerce_variation_id']
            . "\n";

        echo "WooCommerce SKU: "
            . $conflict['woocommerce_sku']
            . "\n";

        echo "Expected canonical variant: "
            . $conflict['expected']['_blackprint_variant_code']
            . "\n";

        echo "Existing canonical variant: "
            . $conflict['existing']['_blackprint_variant_code']
            . "\n\n";
    }
}


/*
|--------------------------------------------------------------------------
| ADOPTION SAMPLES
|--------------------------------------------------------------------------
*/

if (!empty($ownershipWouldAdoptParents)) {

    echo "\n";
    echo "PARENT ADOPTION SAMPLES\n";
    echo str_repeat('-', 58) . "\n";

    $sampleParents = array_slice(
        $ownershipWouldAdoptParents,
        0,
        10,
        true
    );

    foreach ($sampleParents as $parent) {

        echo "WC Product ID: "
            . $parent['woocommerce_product_id']
            . "\n";

        echo "Canonical Product ID: "
            . $parent['canonical_product_id']
            . "\n";

        echo "Canonical Product Code: "
            . $parent['canonical_product_code']
            . "\n\n";
    }
}


if (!empty($ownershipWouldAdoptVariants)) {

    echo "\n";
    echo "VARIANT ADOPTION SAMPLES\n";
    echo str_repeat('-', 58) . "\n";

    $sampleVariants = array_slice(
        $ownershipWouldAdoptVariants,
        0,
        10,
        true
    );

    foreach ($sampleVariants as $variant) {

        echo "WC Product ID: "
            . $variant['woocommerce_product_id']
            . "\n";

        echo "Variation ID: "
            . $variant['woocommerce_variation_id']
            . "\n";

        echo "WooCommerce SKU: "
            . $variant['woocommerce_sku']
            . "\n";

        echo "Canonical Variant: "
            . $variant['canonical_variant_code']
            . "\n\n";
    }
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
| Step 5B — Create Verified Adoption Hand-off
|--------------------------------------------------------------------------
|
| This does not write to WooCommerce.
|
| The hand-off is created only when Step 4 verification and Step 5A
| ownership dry-run both pass. The exact verified adoption mappings
| are stored server-side and later consumed by the dedicated
| WooCommerce ownership committer.
|
*/

$verifiedAdoptionHandoff = null;

$explicitVariantOwnershipCount = 0;

if (
    $verificationPass === true
    && $ownershipDryRunStatus === true
    && count($adoptionMappings) === 3710
) {

    $verifiedAdoptionMappingStore =
        new \BlackPrint\Commerce\Projection\Adoption\VerifiedAdoptionMappingStore();

    $explicitVariantOwnershipCount =
        $verifiedAdoptionMappingStore->countExplicitVariantOwnership(
            $adoptionMappings
        );

    if ($explicitVariantOwnershipCount === 20265) {

        $verifiedAdoptionHandoff =
            $verifiedAdoptionMappingStore->create(
                $adoptionMappings,
                $snapshotUuid,
                [
                    'pass' => $verificationPass,
                    'approved_mapping_count' => count($adoptionMappings),
                ],
                [
                    'pass' => $ownershipDryRunStatus,
                    'parent_conflict_count' =>
                        $ownershipParentConflictCount,
                    'variant_conflict_count' =>
                        $ownershipVariantConflictCount,
                    'error_count' =>
                        $ownershipDryRunErrorCount,
                    'approved_mapping_count' =>
                        $ownershipDryRunApprovedCount,
                ]
            );
    } else {

        $verifiedAdoptionHandoff = [
            'success' => false,
            'message' =>
                'Verified adoption hand-off not created: expected 20,265 explicit variant ownership records, received ' .
                $explicitVariantOwnershipCount .
                '.',
        ];
    }

} else {

    $verifiedAdoptionHandoff = [
        'success' => false,
        'message' =>
            'Verified adoption hand-off not created because Step 4 verification, Step 5A dry-run, or the approved mapping count did not pass.',
    ];
}


/*
|--------------------------------------------------------------------------
| Step 5B — Hand-off Report
|--------------------------------------------------------------------------
*/

$output[] = '';

$output[] =
    'STEP 5B — VERIFIED ADOPTION HAND-OFF';

$output[] =
    str_repeat(
        '-',
        58
    );

if (
    is_array($verifiedAdoptionHandoff)
    && !empty($verifiedAdoptionHandoff['success'])
) {

    $output[] =
        'Status:                          READY';

    $output[] =
        'Artifact ID:                     ' .
        ($verifiedAdoptionHandoff['artifact_id'] ?? 'N/A');

    $output[] =
        'Snapshot UUID:                   ' .
        ($verifiedAdoptionHandoff['snapshot_uuid'] ?? $snapshotUuid);

    $output[] =
        'Mapping hash:                    ' .
        ($verifiedAdoptionHandoff['mapping_hash'] ?? 'N/A');

    $output[] =
        'Approved mappings:               ' .
        ($verifiedAdoptionHandoff['approved_mapping_count'] ?? 0);

    $output[] =
        'Explicit variant ownership:      ' .
        ($verifiedAdoptionHandoff['explicit_variant_ownership_count'] ?? 0);

    $output[] =
        'Expires at:                      ' .
        (
            isset($verifiedAdoptionHandoff['expires_at'])
                ? wp_date(
                    'Y-m-d H:i:s',
                    (int) $verifiedAdoptionHandoff['expires_at']
                )
                : 'N/A'
        );

    $output[] =
        'WooCommerce writes performed:    NO';

} else {

    $output[] =
        'Status:                          NOT READY';

    $output[] =
        'Reason:                          ' .
        (
            is_array($verifiedAdoptionHandoff)
            && isset($verifiedAdoptionHandoff['message'])
                ? $verifiedAdoptionHandoff['message']
                : 'Unknown hand-off creation failure.'
        );
}

$output[] = '';

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
     * Commit verified WooCommerce ownership adoption.
     *
     * Step 5B.
     *
     * This action consumes only a server-side verified adoption hand-off
     * created by the Step 3/4/5A verification pipeline.
     *
     * It does not accept adoption mappings from the browser.
     *
     * It:
     *
     * - Requires manage_woocommerce capability.
     * - Verifies the admin nonce.
     * - Validates the supplied artifact ID.
     * - Loads the server-side verified adoption hand-off.
     * - Requires the hand-off to belong to the current administrator.
     * - Commits ownership metadata only.
     * - Creates no products.
     * - Creates no variations.
     * - Does not modify SKUs.
     * - Performs post-write ownership verification.
     * - Deletes the hand-off only after a successful commit and verification.
     *
     * A failed commit leaves the hand-off intact for investigation.
     */
public function commit_woocommerce_ownership(): void
{
    if (
        ! current_user_can(
            'manage_woocommerce'
        )
    ) {
        wp_die(
            'You do not have permission to commit WooCommerce ownership.'
        );
    }

    check_admin_referer(
        'bp_commit_woocommerce_ownership'
    );

    $artifactId = isset(
        $_POST['artifact_id']
    )
        ? sanitize_text_field(
            wp_unslash(
                $_POST['artifact_id']
            )
        )
        : '';

    if (
        ! preg_match(
            '/^[a-f0-9]{64}$/',
            $artifactId
        )
    ) {
        wp_die(
            'Ownership commit aborted: invalid adoption hand-off artifact ID.'
        );
    }

    try {

        /*
        |--------------------------------------------------------------------------
        | Load Verified Adoption Hand-off
        |--------------------------------------------------------------------------
        */

        $mappingStore =
            new \BlackPrint\Commerce\Projection\Adoption\VerifiedAdoptionMappingStore();

        $handoff =
            $mappingStore->load(
                $artifactId
            );

        if (
            ! is_array($handoff)
        ) {
            wp_die(
                'Ownership commit aborted: verified adoption hand-off could not be loaded, has expired, or failed integrity validation.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Verify Hand-off Ownership
        |--------------------------------------------------------------------------
        |
        | A verified artifact is intentionally bound to the administrator
        | who created it. Another administrator must not be able to submit
        | an existing artifact.
        |
        */

        $createdBy =
            (int) (
                $handoff['created_by']
                ?? 0
            );

        if (
            $createdBy !== get_current_user_id()
        ) {
            wp_die(
                'Ownership commit aborted: this adoption hand-off belongs to a different administrator.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Extract Verified Mappings
        |--------------------------------------------------------------------------
        */

        $adoptionMappings =
            $handoff['adoption_mappings']
            ?? null;

        if (
            ! is_array($adoptionMappings)
        ) {
            wp_die(
                'Ownership commit aborted: verified adoption mappings are invalid.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Final Hand-off Counts
        |--------------------------------------------------------------------------
        */

        $approvedMappingCount =
            count(
                $adoptionMappings
            );

        $explicitVariantOwnershipCount =
            $mappingStore->countExplicitVariantOwnership(
                $adoptionMappings
            );

        if (
            $approvedMappingCount !== 3710
        ) {
            wp_die(
                esc_html(
                    'Ownership commit aborted: expected 3,710 approved mappings, received ' .
                    $approvedMappingCount .
                    '.'
                )
            );
        }

        if (
            $explicitVariantOwnershipCount !== 20265
        ) {
            wp_die(
                esc_html(
                    'Ownership commit aborted: expected 20,265 explicit variant ownership records, received ' .
                    $explicitVariantOwnershipCount .
                    '.'
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Commit Ownership
        |--------------------------------------------------------------------------
        */

        $committer =
            new \BlackPrint\Commerce\Projection\WooCommerce\WooCommerceOwnershipCommitter();

        $result =
            $committer->commit(
                $adoptionMappings
            );

        if (
            ! is_array($result)
        ) {
            wp_die(
                'Ownership commit failed: committer returned an invalid result.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Commit Result
        |--------------------------------------------------------------------------
        */

        $success =
            ! empty(
                $result['success']
            );

        if (
            ! $success
        ) {

            $output = [];

            $output[] =
                'WOOCOMMERCE OWNERSHIP COMMIT — FAILED';

            $output[] =
                str_repeat(
                    '=',
                    58
                );


            $output[] =
                'Artifact ID:                     ' .
                $artifactId;

            $output[] =
                'Snapshot UUID:                   ' .
                (
                    $handoff['snapshot_uuid']
                    ?? 'N/A'
                );

            $output[] =
                'Approved mappings:               ' .
                $approvedMappingCount;

            $output[] =
                'Explicit variant ownership:      ' .
                $explicitVariantOwnershipCount;


            $output[] =
                'Message:                         ' .
                (
                    $result['message']
                    ?? 'Ownership commit failed.'
                );


            $output[] =
                'WooCommerce writes may have occurred: '
                . (
                    ! empty(
                        $result['write_errors']
                    )
                        ? 'YES — WRITE ERRORS REPORTED'
                        : 'UNKNOWN — POST-WRITE VERIFICATION REQUIRED'
                );

            $output[] =
                'Hand-off preserved:              YES';


            $output[] =
                'Write errors:';

            if (
                empty(
                    $result['write_errors']
                )
            ) {

                $output[] =
                    'None reported by committer.';

            } else {

                foreach (
                    $result['write_errors']
                    as $writeError
                ) {

                    $output[] =
                        '- ' .
                        (
                            is_scalar($writeError)
                                ? (string) $writeError
                                : wp_json_encode($writeError)
                        );
                }
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
        }

        /*
        |--------------------------------------------------------------------------
        | Successful Commit
        |--------------------------------------------------------------------------
        |
        | The committer performs its own post-write verification.
        | Only after success do we destroy the one-time hand-off.
        |
        */

        $deleted =
            $mappingStore->delete(
                $artifactId
            );

        if (
            ! $deleted
        ) {
            wp_die(
                '<pre>' .
                esc_html(
                    'WooCommerce ownership commit succeeded, but the verified adoption hand-off could not be deleted. Artifact preserved for investigation.' .
                    "\n\n" .
                    'Artifact ID: ' .
                    $artifactId
                ) .
                '</pre>'
            );
        }

        $output[] =
            'WOOCOMMERCE OWNERSHIP COMMIT — SUCCESS';

        $output[] =
            str_repeat(
                '=',
                58
            );

        $output[] =
            'Artifact ID:                     ' .
            $artifactId;

        $output[] =
            'Snapshot UUID:                   ' .
            (
                $handoff['snapshot_uuid']
                ?? 'N/A'
            );

        $output[] =
            'Approved mappings committed:     ' .
            $approvedMappingCount;

        $output[] =
            'Explicit variant ownership:      ' .
            $explicitVariantOwnershipCount;


        $output[] =
            'Parent ownership written:        ' .
            (
                $result['audit']['parents_written']
                ?? 0
            );

        $output[] =
            'Variant ownership written:       ' .
            (
                $result['audit']['variants_written']
                ?? 0
            );

        $output[] =
            'Parents already managed:         ' .
            (
                $result['audit']['parents_already_managed']
                ?? 0
            );

        $output[] =
            'Variants already managed:        ' .
            (
                $result['audit']['variants_already_managed']
                ?? 0
            );

        $output[] =
            'Post-write verification:          PASS';

        $output[] =
            'Ownership conflicts:              0';

        $output[] =
            'Missing ownership records:         0';

        $output[] =
            'Incorrect canonical identities:    0';


        $output[] =
            'Hand-off deleted:                 YES';

        $output[] =
            'WooCommerce products created:      NO';

        $output[] =
            'WooCommerce variations created:   NO';

        $output[] =
            'SKUs modified:                    NO';

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
                'WooCommerce ownership commit failed: ' .
                $exception->getMessage()
            ) .
            '</pre>'
        );
    }
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
