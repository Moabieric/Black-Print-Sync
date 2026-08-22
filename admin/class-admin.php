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
 * Run the temporary snapshot normalization smoke test.
 *
 * This action:
 *
 * - Requires administrator-level WooCommerce access.
 * - Verifies an admin nonce.
 * - Explicitly loads the temporary test.
 * - Does not make the test part of plugin bootstrap.
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
    | Enable Explicit Test Execution
    |--------------------------------------------------------------------------
    */

    $_GET['bp_test_normalization'] = '1';


    /*
    |--------------------------------------------------------------------------
    | Load Temporary Smoke Test
    |--------------------------------------------------------------------------
    |
    | The test registers an admin_init callback, but admin_init has already
    | fired by the time an admin_post action is executed.
    |
    | Therefore this temporary runner executes the normalization directly.
    |
    */

    $snapshotUuid =
        'e1feb722-4844-4561-bb22-a199a57522d9';

    try {

        $result = bp_commerce()
            ->normalization()
            ->normalize(
                $snapshotUuid
            );

        wp_die(
            '<pre>' .
            esc_html(
                print_r(
                    $result->toArray(),
                    true
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