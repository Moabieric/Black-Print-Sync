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