<?php

namespace BlackPrint\Commerce;

defined('ABSPATH') || exit;

/**
 * BlackPrint Commerce Plugin Loader.
 *
 * Responsible for loading all plugin dependencies and
 * booting the plugin's runtime components.
 *
 * Dependencies are loaded in a deliberate order so that
 * classes are available before dependent services are
 * instantiated.
 *
 * @package BlackPrint\Commerce
 */
class Loader
{
    /**
     * Singleton instance.
     *
     * @var Loader|null
     */
    private static ?Loader $instance = null;


    /**
     * Get singleton instance.
     *
     * @return Loader
     */
    public static function instance(): Loader
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    /**
     * Constructor.
     *
     * Loads all plugin dependencies and boots
     * the required plugin components.
     */
    private function __construct()
    {
        $this->load_dependencies();

        $this->boot();
    }


    /**
     * Load all plugin dependencies.
     *
     * Dependencies must be loaded before any class
     * that relies on them is instantiated.
     *
     * @return void
     */
    private function load_dependencies(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Core Store
        |--------------------------------------------------------------------------
        */

        require_once BP_COMMERCE_PATH
            . 'includes/class-store.php';


        /*
        |--------------------------------------------------------------------------
        | Store Audit
        |--------------------------------------------------------------------------
        */

        require_once BP_COMMERCE_PATH
            . 'includes/class-audit.php';


        /*
        |--------------------------------------------------------------------------
        | Category Repository
        |--------------------------------------------------------------------------
        |
        | Central source for category data.
        |
        */

        require_once BP_COMMERCE_PATH
            . 'includes/class-category-repository.php';


        /*
        |--------------------------------------------------------------------------
        | Category Intelligence
        |--------------------------------------------------------------------------
        |
        | Builds the complete category intelligence index.
        |
        */

        require_once BP_COMMERCE_PATH
            . 'includes/class-category-intelligence.php';


        /*
        |--------------------------------------------------------------------------
        | Category Explorer
        |--------------------------------------------------------------------------
        |
        | Provides detailed inspection of individual categories.
        |
        */

        require_once BP_COMMERCE_PATH
            . 'includes/class-category-explorer.php';


        /*
        |--------------------------------------------------------------------------
        | Category Health
        |--------------------------------------------------------------------------
        |
        | Evaluates category health and identifies issues.
        |
        */

        require_once BP_COMMERCE_PATH
            . 'includes/class-category-health.php';


        /*
        |--------------------------------------------------------------------------
        | Category Tree
        |--------------------------------------------------------------------------
        |
        | Builds the hierarchical category structure.
        |
        */

        require_once BP_COMMERCE_PATH
            . 'includes/class-category-tree.php';


        /*
        |--------------------------------------------------------------------------
        | Category Recommendations
        |--------------------------------------------------------------------------
        |
        | Generates recommendations based on category condition.
        |
        */

        require_once BP_COMMERCE_PATH
            . 'includes/class-category-recommendations.php';


        /*
        |--------------------------------------------------------------------------
        | Recovery Action Engine
        |--------------------------------------------------------------------------
        |
        | Converts intelligence and recommendations into
        | safe, actionable recovery opportunities.
        |
        | IMPORTANT:
        | This class is currently READ-ONLY.
        |
        */

        require_once BP_COMMERCE_PATH
            . 'includes/class-recovery-action-engine.php';


        /*
        |--------------------------------------------------------------------------
        | Supplier Integrations
        |--------------------------------------------------------------------------
        |
        | Supplier connectors communicate with external
        | fulfilment providers.
        |
        | Current connector:
        | - Amrod
        |
        | IMPORTANT:
        | Supplier integrations are read-only at this stage.
        | No WooCommerce product writes are performed.
        |
        */


        /*
        |--------------------------------------------------------------------------
        | Amrod Configuration
        |--------------------------------------------------------------------------
        |
        | Provides Amrod API URLs and credentials.
        |
        | No external API request is made when this class
        | is loaded.
        |
        */

        require_once BP_COMMERCE_PATH
            . 'modules/suppliers/amrod/class-amrod-config.php';


        /*
        |--------------------------------------------------------------------------
        | Amrod Authentication
        |--------------------------------------------------------------------------
        |
        | Handles VendorLogin authentication and
        | Bearer token lifecycle.
        |
        | Depends on:
        | - Amrod_Config
        |
        */

        require_once BP_COMMERCE_PATH
            . 'modules/suppliers/amrod/class-amrod-auth.php';


        /*
        |--------------------------------------------------------------------------
        | Amrod API Client
        |--------------------------------------------------------------------------
        |
        | Provides authenticated HTTP communication
        | with the Amrod Vendor API.
        |
        | Depends on:
        | - Amrod_Config
        | - Amrod_Auth
        |
        */

        require_once BP_COMMERCE_PATH
            . 'modules/suppliers/amrod/class-amrod-api-client.php';


        /*
        |--------------------------------------------------------------------------
        | Amrod Category Service
        |--------------------------------------------------------------------------
        |
        | Provides read-only access to Amrod category data.
        |
        | Depends on:
        | - Amrod_Api_Client
        |
        */

        require_once BP_COMMERCE_PATH
            . 'modules/suppliers/amrod/class-amrod-category-service.php';


        /*
        |--------------------------------------------------------------------------
        | Amrod Brand Service
        |--------------------------------------------------------------------------
        |
        | Provides read-only access to Amrod brand data.
        |
        | Depends on:
        | - Amrod_Api_Client
        |
        */

        require_once BP_COMMERCE_PATH
            . 'modules/suppliers/amrod/class-amrod-brand-service.php';


        /*
        |--------------------------------------------------------------------------
        | Amrod Product Service
        |--------------------------------------------------------------------------
        |
        | Provides read-only access to Amrod product data.
        |
        | Supports:
        | - Full product catalogue.
        | - Updated products.
        | - Products with branding.
        | - Updated products with branding.
        |
        | Depends on:
        | - Amrod_Api_Client
        |
        */

        require_once BP_COMMERCE_PATH
            . 'modules/suppliers/amrod/class-amrod-product-service.php';


        /*
        |--------------------------------------------------------------------------
        | Amrod Stock Service
        |--------------------------------------------------------------------------
        |
        | Provides read-only access to Amrod stock data.
        |
        | Supports:
        | - Full stock catalogue.
        | - Updated stock.
        |
        | Depends on:
        | - Amrod_Api_Client
        |
        */

        require_once BP_COMMERCE_PATH
            . 'modules/suppliers/amrod/class-amrod-stock-service.php';


            /*
|--------------------------------------------------------------------------
| Amrod Price Service
|--------------------------------------------------------------------------
|
| Provides read-only access to Amrod price data.
|
| Supports:
| - Full price catalogue.
| - Updated prices.
|
| Depends on:
| - Amrod_Api_Client
|
*/

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-price-service.php';

    /*
|--------------------------------------------------------------------------
| Amrod Branding Department Service
|--------------------------------------------------------------------------
|
| Provides read-only access to Amrod branding department data.
|
| Supports:
| - Full branding department catalogue.
| - Updated branding departments.
|
| Depends on:
| - Amrod_Api_Client
|
*/

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-branding-department-service.php';

    /*
|--------------------------------------------------------------------------
| Amrod Inclusive Branding Service
|--------------------------------------------------------------------------
|
| Provides read-only access to Amrod inclusive branding data.
|
| Supports:
| - Full inclusive branding catalogue.
| - Updated inclusive branding data.
|
| Depends on:
| - Amrod_Api_Client
|
*/

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-inclusive-branding-service.php';


    /*
|--------------------------------------------------------------------------
| Amrod Colour Swatch Service
|--------------------------------------------------------------------------
|
| Provides read-only access to Amrod colour swatch data.
|
| Supports:
| - Full colour swatches.
| - Updated colour swatches.
|
| Depends on:
| - Amrod_Api_Client
|
*/

require_once BP_COMMERCE_PATH
    . 'modules/suppliers/amrod/class-amrod-colour-swatch-service.php';


        /*
        |--------------------------------------------------------------------------
        | Amrod Connector
        |--------------------------------------------------------------------------
        |
        | Public entry point for the Amrod supplier integration.
        |
        | Depends on:
        | - Amrod_Config
        | - Amrod_Auth
        | - Amrod_Api_Client
        | - Amrod_Category_Service
        | - Amrod_Brand_Service
        | - Amrod_Product_Service
        | - Amrod_Stock_Service
        |
        */
        

        require_once BP_COMMERCE_PATH
            . 'modules/suppliers/amrod/class-amrod-connector.php';


        /*
        |--------------------------------------------------------------------------
        | Amrod Health Check
        |--------------------------------------------------------------------------
        |
        | Verifies authentication and API connectivity.
        |
        | Depends on:
        | - Amrod_Connector
        |
        */

        require_once BP_COMMERCE_PATH
            . 'modules/suppliers/amrod/class-amrod-health-check.php';


        /*
        |--------------------------------------------------------------------------
        | Admin
        |--------------------------------------------------------------------------
        |
        | Loads the BlackPrint Commerce administration layer.
        |
        | Admin is loaded after all backend services so that
        | dashboard pages can safely access the available
        | supplier and commerce services.
        |
        */

        require_once BP_COMMERCE_PATH
            . 'admin/class-admin.php';
    }


    /**
     * Boot plugin components.
     *
     * @return void
     */
    private function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Admin
        |--------------------------------------------------------------------------
        */

        new Admin();
    }
}