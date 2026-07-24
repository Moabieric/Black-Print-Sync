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
}