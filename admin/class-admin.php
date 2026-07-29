<?php

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
class Admin
{
    /**
     * Constructor.
     *
     * Registers the WordPress admin menu.
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
    }


    /**
     * Register BlackPrint Commerce admin menus.
     *
     * @return void
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
        |
        | Provides access to the Amrod supplier connector,
        | configuration and diagnostics.
        |
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
        |
        | Provides read-only access to the brands returned
        | by the Amrod Vendor API.
        |
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
        |
        | Provides read-only access to category data returned
        | by the Amrod Vendor API.
        |
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
        | Provides read-only access to product data returned
        | by the Amrod Vendor API.
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
| Amrod Stock
|--------------------------------------------------------------------------
|
| Provides read-only access to stock data returned
| by the Amrod Vendor API.
|
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
|
| Provides read-only access to price data returned
| by the Amrod Vendor API.
|
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
|
| Provides read-only access to branding departments and
| inclusive branding data returned by the Amrod Vendor API.
|
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
     *
     * @return void
     */
    public function dashboard(): void
    {
        include BP_COMMERCE_PATH
            . 'admin/views/dashboard.php';
    }


    /**
     * Render the Amrod Connector administration page.
     *
     * @return void
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
     *
     * @return void
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

            } catch (
                \Throwable $exception
            ) {
                $error = $exception->getMessage();
            }

        } else {

            try {
                $brands = $brand_service->get_brands();

            } catch (
                \Throwable $exception
            ) {
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
     *
     * @return void
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

            } catch (
                \Throwable $exception
            ) {
                $error = $exception->getMessage();
            }

        } else {

            try {
                $categories = $category_service->get_categories();

            } catch (
                \Throwable $exception
            ) {
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
     *
     * @return void
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

                        $result = $product_service->get_updated_products_with_branding();

                        break;


                    default:

                        $error = 'Invalid Amrod product API action.';

                        break;
                }

            } catch (
                \Throwable $exception
            ) {
                $error = $exception->getMessage();
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Render View
        |--------------------------------------------------------------------------
        */

        include BP_COMMERCE_PATH
            . 'admin/views/amrod-products.php';
    }

    /**
 * Render the Amrod Stock Explorer page.
 *
 * This page is read-only.
 *
 * No WooCommerce products or stock values are
 * created or modified.
 *
 * @return void
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

                    $result = $stock_service->get_updated_stock();

                    break;


                default:

                    $error =
                        'Invalid Amrod stock API action.';

                    break;

            }

        } catch (
            \Throwable $exception
        ) {

            $error =
                $exception->getMessage();

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Render View
    |--------------------------------------------------------------------------
    */

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
 *
 * @return void
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

                    $result = $price_service->get_updated_prices();

                    break;


                default:

                    $error =
                        'Invalid Amrod price API action.';

                    break;

            }

        } catch (
            \Throwable $exception
        ) {

            $error =
                $exception->getMessage();

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Render View
    |--------------------------------------------------------------------------
    */

    include BP_COMMERCE_PATH
        . 'admin/views/amrod-prices.php';
}

/**
 * Render the Amrod Branding Explorer page.
 *
 * Read-only.
 *
 * @return void
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
            wp_unslash($_GET['bp_amrod_branding_action'])
        )
        : '';

    if (
        isset($_GET['bp_amrod_branding_test'])
        && check_admin_referer('bp_amrod_branding_test')
    ) {

        try {

            switch ($action) {

                case 'branding_departments':

                    $result = $branding_department_service
                        ->get_branding_departments();

                    break;

                case 'updated_branding_departments':

                    $result = $branding_department_service
                        ->get_updated_branding_departments();

                    break;

                case 'inclusive_branding':

                    $result = $inclusive_branding_service
                        ->get_inclusive_branding();

                    break;

                case 'updated_inclusive_branding':

                    $result = $inclusive_branding_service
                        ->get_updated_inclusive_branding();

                    break;

                default:

                    $error = 'Invalid branding action.';
            }

        } catch (\Throwable $exception) {

            $error = $exception->getMessage();

        }

    }

    include BP_COMMERCE_PATH
        . 'admin/views/amrod-branding.php';
}


}