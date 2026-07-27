<?php

namespace BlackPrint\Commerce\Suppliers\Amrod;

defined('ABSPATH') || exit;

/**
 * Amrod Connector.
 *
 * Public entry point for the Amrod supplier integration.
 *
 * The connector acts as the boundary between BlackPrint OS
 * and the Amrod-specific implementation.
 *
 * Responsibilities:
 * - Provide connector identity.
 * - Provide connector version.
 * - Provide configuration.
 * - Provide authentication services.
 * - Provide the Amrod API client.
 * - Provide the Amrod product service.
 * - Provide connector-level status information.
 * - Provide authentication testing.
 * - Provide API connectivity testing.
 * - Provide health-check services.
 * - Provide read-only supplier data access.
 * - Provide a controlled endpoint registry.
 *
 * This class does not:
 * - Write to WooCommerce.
 * - Create or update products.
 * - Synchronise products.
 * - Apply BlackPrint business rules.
 * - Normalise supplier data.
 *
 * @package BlackPrint\Commerce
 */
class Amrod_Connector
{
    /**
     * Supplier identifier.
     *
     * This value must remain stable.
     *
     * @var string
     */
    private const SUPPLIER_ID = 'amrod';


    /**
     * Supplier display name.
     *
     * @var string
     */
    private const SUPPLIER_NAME = 'Amrod';


    /**
     * Connector version.
     *
     * @var string
     */
    private const CONNECTOR_VERSION = '1.0.0';


    /*
    |--------------------------------------------------------------------------
    | Core Read-Only Endpoints
    |--------------------------------------------------------------------------
    |
    | These paths represent the current supplier data domains exposed
    | through the connector.
    |
    | The connector remains read-only.
    |
    */


    /**
     * Brands endpoint.
     *
     * @var string
     */
    private const BRANDS_ENDPOINT = '/api/v1/Brands/';


    /**
     * Categories endpoint.
     *
     * @var string
     */
    private const CATEGORIES_ENDPOINT = '/api/v1/Categories/';


    /**
     * Full products endpoint.
     *
     * @var string
     */
    private const PRODUCTS_ENDPOINT = '/api/v1/Products/';


    /**
     * Updated products endpoint.
     *
     * @var string
     */
    private const UPDATED_PRODUCTS_ENDPOINT =
        '/api/v1/Products/GetUpdatedProducts';


    /**
     * Full products with branding endpoint.
     *
     * @var string
     */
    private const PRODUCTS_WITH_BRANDING_ENDPOINT =
        '/api/v1/Products/GetProductsAndBranding';


    /**
     * Updated products with branding endpoint.
     *
     * @var string
     */
    private const UPDATED_PRODUCTS_WITH_BRANDING_ENDPOINT =
        '/api/v1/Products/GetUpdatedProductsAndBranding';

            /**
     * Full stock endpoint.
     *
     * @var string
     */
    private const STOCK_ENDPOINT =
        '/api/v1/Stock/';


    /**
     * Updated stock endpoint.
     *
     * @var string
     */
    private const UPDATED_STOCK_ENDPOINT =
        '/api/v1/Stock/GetUpdated';

        /**
 * Full prices endpoint.
 *
 * @var string
 */
private const PRICES_ENDPOINT = '/api/v1/Prices/';


/**
 * Updated prices endpoint.
 *
 * @var string
 */
private const UPDATED_PRICES_ENDPOINT =
    '/api/v1/Prices/GetUpdated';


    /**
 * Full branding departments endpoint.
 *
 * @var string
 */
private const BRANDING_DEPARTMENTS_ENDPOINT =
    '/api/v1/BrandingDepartments/';


/**
 * Updated branding departments endpoint.
 *
 * @var string
 */
private const UPDATED_BRANDING_DEPARTMENTS_ENDPOINT =
    '/api/v1/BrandingDepartments/GetUpdated';


    /*
    |--------------------------------------------------------------------------
    | Amrod Services
    |--------------------------------------------------------------------------
    */


    /**
     * Amrod configuration service.
     *
     * @var Amrod_Config
     */
    private Amrod_Config $config;


    /**
     * Authentication service.
     *
     * @var Amrod_Auth
     */
    private Amrod_Auth $auth;


    /**
     * API client.
     *
     * @var Amrod_Api_Client
     */
    private Amrod_Api_Client $api_client;


    /**
     * Product service.
     *
     * @var Amrod_Product_Service
     */
    private Amrod_Product_Service $product_service;


       /**
     * Stock service.
     *
     * @var Amrod_Stock_Service
     */
    private Amrod_Stock_Service $stock_service;


    /**
 * Price service.
 *
 * @var Amrod_Price_Service
 */
private Amrod_Price_Service $price_service;

/**
 * Branding department service.
 *
 * @var Amrod_Branding_Department_Service
 */
private Amrod_Branding_Department_Service $branding_department_service;


    /**
     * Constructor.
     *
     * Builds the Amrod connector dependency chain.
     */
    public function __construct()
    {
        /*
        |--------------------------------------------------------------------------
        | Configuration
        |--------------------------------------------------------------------------
        */

        $this->config = new Amrod_Config();


        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        $this->auth = new Amrod_Auth(
            $this->config
        );


        /*
        |--------------------------------------------------------------------------
        | API Client
        |--------------------------------------------------------------------------
        */

        $this->api_client = new Amrod_Api_Client(
            $this->auth,
            $this->config
        );


              /*
        |--------------------------------------------------------------------------
        | Product Service
        |--------------------------------------------------------------------------
        */

        $this->product_service = new Amrod_Product_Service(
            $this->api_client
        );


        /*
        |--------------------------------------------------------------------------
        | Stock Service
        |--------------------------------------------------------------------------
        */

        $this->stock_service = new Amrod_Stock_Service(
            $this->api_client
        );

        /*
        |--------------------------------------------------------------------------
        | Price Service
        |--------------------------------------------------------------------------
        */

        $this->price_service = new Amrod_Price_Service(
            $this->api_client
        );

        /*
|--------------------------------------------------------------------------
| Branding Department Service
|--------------------------------------------------------------------------
*/

$this->branding_department_service =
    new Amrod_Branding_Department_Service(
        $this->api_client
    );

        }


    /**
     * Get the supplier identifier.
     *
     * @return string
     */
    public function get_id(): string
    {
        return self::SUPPLIER_ID;
    }


    /**
     * Get the supplier display name.
     *
     * @return string
     */
    public function get_name(): string
    {
        return self::SUPPLIER_NAME;
    }


    /**
     * Get the connector version.
     *
     * @return string
     */
    public function get_version(): string
    {
        return self::CONNECTOR_VERSION;
    }


    /**
     * Get the Amrod configuration service.
     *
     * @return Amrod_Config
     */
    public function get_config(): Amrod_Config
    {
        return $this->config;
    }


    /**
     * Get the authentication service.
     *
     * @return Amrod_Auth
     */
    public function get_auth(): Amrod_Auth
    {
        return $this->auth;
    }


    /**
     * Get the Amrod API client.
     *
     * @return Amrod_Api_Client
     */
    public function get_api_client(): Amrod_Api_Client
    {
        return $this->api_client;
    }


    /**
     * Get the Amrod product service.
     *
     * Provides controlled read-only access to Amrod
     * product and product-with-branding endpoints.
     *
     * @return Amrod_Product_Service
     */
    public function get_product_service(): Amrod_Product_Service
    {
        return $this->product_service;
    }

        /**
     * Get the Amrod stock service.
     *
     * Provides controlled read-only access to Amrod
     * stock and updated stock endpoints.
     *
     * @return Amrod_Stock_Service
     */
    public function get_stock_service(): Amrod_Stock_Service
    {
        return $this->stock_service;
    }

    /**
 * Get the Amrod price service.
 *
 * Provides controlled read-only access to Amrod
 * price and updated price endpoints.
 *
 * @return Amrod_Price_Service
 */
public function get_price_service(): Amrod_Price_Service
{
    return $this->price_service;
}

/**
 * Get the Amrod branding department service.
 *
 * Provides controlled read-only access to Amrod
 * branding department and updated branding department
 * endpoints.
 *
 * @return Amrod_Branding_Department_Service
 */
public function get_branding_department_service():
    Amrod_Branding_Department_Service
{
    return $this->branding_department_service;
}


    /**
     * Create a health-check service for this connector.
     *
     * A new health-check instance is created on demand.
     *
     * @return Amrod_Health_Check
     */
    public function get_health_check(): Amrod_Health_Check
    {
        return new Amrod_Health_Check(
            $this
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */


    /**
     * Test Amrod authentication.
     *
     * This verifies that the connector can obtain a token.
     *
     * If a cached token exists, it may be returned without
     * contacting VendorLogin.
     *
     * @return array{
     *     success: bool,
     *     supplier: string,
     *     message: string,
     *     cached: bool
     * }
     */
    public function test_authentication(): array
    {
        try {

            $was_cached = $this->auth->has_cached_token();

            $token = $this->auth->get_token();


            if (
                ! is_string($token)
                || trim($token) === ''
            ) {
                return [
                    'success'  => false,
                    'supplier' => self::SUPPLIER_ID,
                    'message'  =>
                        'Amrod authentication returned an empty token.',
                    'cached'   => $was_cached,
                ];
            }


            return [
                'success'  => true,
                'supplier' => self::SUPPLIER_ID,
                'message'  =>
                    $was_cached
                        ? 'Amrod authentication token retrieved from cache.'
                        : 'Amrod authentication successful. New Bearer token obtained and cached.',
                'cached'   => $was_cached,
            ];

        } catch (
            \Throwable $exception
        ) {

            return [
                'success'  => false,
                'supplier' => self::SUPPLIER_ID,
                'message'  => $exception->getMessage(),
                'cached'   => false,
            ];
        }
    }


    /**
     * Get a valid Amrod authentication token.
     *
     * @return string
     *
     * @throws \RuntimeException When authentication fails.
     */
    public function get_token(): string
    {
        return $this->auth->get_token();
    }


    /*
    |--------------------------------------------------------------------------
    | API Connectivity
    |--------------------------------------------------------------------------
    */


    /**
     * Perform a read-only Amrod API connectivity test.
     *
     * The supplied endpoint must be a safe, non-mutating
     * Amrod Vendor API endpoint.
     *
     * No WooCommerce data is modified.
     *
     * @param string $endpoint API endpoint path.
     *
     * @return array{
     *     success: bool,
     *     supplier: string,
     *     endpoint: string,
     *     message: string,
     *     data: array
     * }
     */
    public function test_api_connection(
        string $endpoint
    ): array {
        try {

            $data = $this->api_client->get(
                $endpoint
            );


            return [
                'success'  => true,
                'supplier' => self::SUPPLIER_ID,
                'endpoint' => $endpoint,
                'message'  =>
                    'Amrod Vendor API responded successfully.',
                'data'     => $data,
            ];

        } catch (
            \Throwable $exception
        ) {

            return [
                'success'  => false,
                'supplier' => self::SUPPLIER_ID,
                'endpoint' => $endpoint,
                'message'  => $exception->getMessage(),
                'data'     => [],
            ];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Supplier Data Access
    |--------------------------------------------------------------------------
    */


    /**
     * Get all Amrod brands.
     *
     * This is a read-only operation.
     *
     * @return array
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function get_brands(): array
    {
        return $this->api_client->get(
            self::BRANDS_ENDPOINT
        );
    }


    /**
     * Get all Amrod categories.
     *
     * This is a read-only operation.
     *
     * @return array
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function get_categories(): array
    {
        return $this->api_client->get(
            self::CATEGORIES_ENDPOINT
        );
    }


    /**
     * Get data from a read-only Amrod API endpoint.
     *
     * This is the generic supplier data access boundary.
     *
     * It must only be used with safe, non-mutating
     * GET endpoints.
     *
     * No WooCommerce data is modified.
     *
     * @param string $endpoint API endpoint path.
     * @param array  $query    Optional query parameters.
     *
     * @return array
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function get_read_only_endpoint(
        string $endpoint,
        array $query = []
    ): array {
        return $this->api_client->get(
            $endpoint,
            $query
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Endpoint Registry
    |--------------------------------------------------------------------------
    */


    /**
     * Get the registered Amrod read-only endpoints.
     *
     * This provides a central catalogue of supplier endpoints
     * that BlackPrint OS currently recognises.
     *
     * The registry is informational and does not perform
     * any API requests.
     *
     * @return array<string, string>
     */
    public function get_endpoint_registry(): array
    {
                return [
    'brands'                         => self::BRANDS_ENDPOINT,
    'categories'                     => self::CATEGORIES_ENDPOINT,
    'products'                       => self::PRODUCTS_ENDPOINT,
    'updated_products'               => self::UPDATED_PRODUCTS_ENDPOINT,
    'products_with_branding'         => self::PRODUCTS_WITH_BRANDING_ENDPOINT,
    'updated_products_with_branding' => self::UPDATED_PRODUCTS_WITH_BRANDING_ENDPOINT,
    'prices'                         => self::PRICES_ENDPOINT,
    'updated_prices'                 => self::UPDATED_PRICES_ENDPOINT,
    'branding_departments'           => self::BRANDING_DEPARTMENTS_ENDPOINT,
    'updated_branding_departments'   => self::UPDATED_BRANDING_DEPARTMENTS_ENDPOINT,
];
    }


    /**
     * Get a registered endpoint by key.
     *
     * This prevents callers from having to duplicate
     * supplier endpoint paths throughout the application.
     *
     * @param string $key Endpoint registry key.
     *
     * @return string|null
     */
    public function get_endpoint(
        string $key
    ): ?string {
        $endpoints = $this->get_endpoint_registry();

        return $endpoints[$key] ?? null;
    }


    /**
     * Determine whether an endpoint is registered.
     *
     * @param string $key Endpoint registry key.
     *
     * @return bool
     */
    public function has_endpoint(
        string $key
    ): bool {
        return $this->get_endpoint($key) !== null;
    }


    /*
    |--------------------------------------------------------------------------
    | Connector Status
    |--------------------------------------------------------------------------
    */


    /**
     * Get connector status.
     *
     * This does not make an external API request.
     *
     * @return array{
     *     supplier_id: string,
     *     supplier_name: string,
     *     connector: string,
     *     version: string,
     *     status: string
     * }
     */
    public function get_status(): array
    {
        return [
            'supplier_id'   => self::SUPPLIER_ID,
            'supplier_name' => self::SUPPLIER_NAME,
            'connector'     => 'Amrod Connector',
            'version'       => self::CONNECTOR_VERSION,
            'status'        => 'loaded',
        ];
    }
}