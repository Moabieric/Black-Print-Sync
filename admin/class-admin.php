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
            'admin_post_bp_test_woocommerce_execution_decisions',
            [
                $this,
                'test_woocommerce_execution_decisions',
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
}

public function test_woocommerce_execution_decisions(): void
{
    if (
        ! current_user_can(
            'manage_woocommerce'
        )
    ) {
        wp_die(
            'You do not have permission to run this execution decision test.'
        );
    }

    check_admin_referer(
        'bp_test_woocommerce_execution_decisions'
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
        ) {

            wp_die(
                'Execution decision test aborted: no canonical product collection was returned.'
            );
        }

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
        | Statistics
        |--------------------------------------------------------------------------
        */

        $canonicalProducts = 0;
        $successfulDecisions = 0;

        $createDecisions = 0;
        $updateDecisions = 0;

        $failedDecisions = 0;
        $invalidDecisions = 0;

        $failures = [];

        /*
        |--------------------------------------------------------------------------
        | Process Every Canonical Product
        |--------------------------------------------------------------------------
        */

        foreach (
            $products->all() as $product
        ) {

            $canonicalProducts++;

            $canonical =
                $product->toArray();

            /*
            |--------------------------------------------------------------------------
            | Build Projection Plan
            |--------------------------------------------------------------------------
            */

            $projectionResult =
                $projector->project(
                    $canonical
                );

            if (
                ! $projectionResult->success()
            ) {

                $failedDecisions++;

                $failures[] = [
                    'stage' => 'projection',
                    'message' =>
                        $projectionResult->message(),
                    'identity' =>
                        $canonical['identity']
                        ?? [],
                ];

                continue;
            }

            $projection =
                $projectionResult->data();

            /*
            |--------------------------------------------------------------------------
            | Execute Decision-Only Lookup
            |--------------------------------------------------------------------------
            */

            $executionResult =
                $executor->execute(
                    $projection
                );

            if (
                ! $executionResult->success()
            ) {

                $failedDecisions++;

                $failures[] = [
                    'stage' => 'execution',
                    'message' =>
                        $executionResult->message(),
                    'identity' =>
                        $canonical['identity']
                        ?? [],
                ];

                continue;
            }

            $decision =
                $executionResult->data()['decision']
                ?? null;

            if (
                $decision === 'create'
            ) {

                $createDecisions++;
                $successfulDecisions++;

                continue;
            }

            if (
                $decision === 'update'
            ) {

                $updateDecisions++;
                $successfulDecisions++;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Unexpected Decision
            |--------------------------------------------------------------------------
            */

            $invalidDecisions++;

            $failures[] = [
                'stage' => 'decision',
                'message' =>
                    'Executor returned an unexpected execution decision.',
                'identity' =>
                    $canonical['identity']
                    ?? [],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Determine Verification Status
        |--------------------------------------------------------------------------
        */

        $status =
            (
                $canonicalProducts > 0
                && $failedDecisions === 0
                && $invalidDecisions === 0
                && $successfulDecisions === $canonicalProducts
                && (
                    $createDecisions
                    + $updateDecisions
                ) === $canonicalProducts
            )
                ? 'PASS'
                : 'FAILED';

        /*
        |--------------------------------------------------------------------------
        | Build Report
        |--------------------------------------------------------------------------
        */

        $output = [];

        $output[] =
            'TEST VERSION: WOOCOMMERCE EXECUTION DECISIONS v1';

        $output[] = '';

        $output[] =
            'BlackPrint OS — WooCommerce Execution Decision Verification';

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

        $output[] = 'EXECUTION DECISIONS';

        $output[] =
            str_repeat('-', 64);

        $output[] =
            'Canonical products:       ' .
            $canonicalProducts;

        $output[] =
            'Successful decisions:     ' .
            $successfulDecisions;

        $output[] =
            'Create decisions:         ' .
            $createDecisions;

        $output[] =
            'Update decisions:         ' .
            $updateDecisions;

        $output[] =
            'Failed decisions:         ' .
            $failedDecisions;

        $output[] =
            'Invalid decisions:        ' .
            $invalidDecisions;

        $output[] =
            'Status:                   ' .
            $status;

        $output[] = '';

        /*
        |--------------------------------------------------------------------------
        | Failure Details
        |--------------------------------------------------------------------------
        */

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
                    'stage=' .
                    (
                        $failure['stage']
                        ?? 'unknown'
                    ) .
                    ' message=' .
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
            'Execution decision test exception: ' .
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