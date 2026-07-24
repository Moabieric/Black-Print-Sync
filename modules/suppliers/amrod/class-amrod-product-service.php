<?php

namespace BlackPrint\Commerce\Suppliers\Amrod;

defined('ABSPATH') || exit;

/**
 * Amrod Product Service.
 *
 * Provides read-only access to product data
 * from the Amrod Vendor API.
 *
 * Responsibilities:
 * - Retrieve the full Amrod product catalogue.
 * - Retrieve updated Amrod products.
 * - Retrieve products including branding data.
 * - Retrieve updated products including branding data.
 * - Provide controlled access to supplier product data.
 *
 * This service does not:
 * - Create WooCommerce products.
 * - Update WooCommerce products.
 * - Modify WooCommerce data.
 * - Apply BlackPrint business rules.
 * - Normalise supplier data.
 * - Download product images.
 *
 * @package BlackPrint\Commerce
 */
class Amrod_Product_Service
{
    /**
     * Full products endpoint.
     *
     * @var string
     */
    private const PRODUCTS_ENDPOINT =
        '/api/v1/Products/';


    /**
     * Updated products endpoint.
     *
     * @var string
     */
    private const UPDATED_PRODUCTS_ENDPOINT =
        '/api/v1/Products/GetUpdated';


    /**
     * Products with branding endpoint.
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
     * Amrod API client.
     *
     * @var Amrod_Api_Client
     */
    private Amrod_Api_Client $api_client;


    /**
     * Constructor.
     *
     * @param Amrod_Api_Client $api_client Amrod API client.
     */
    public function __construct(
        Amrod_Api_Client $api_client
    ) {
        $this->api_client = $api_client;
    }


    /**
     * Get all Amrod products.
     *
     * This is a read-only operation.
     *
     * @param array $query Optional query parameters.
     *
     * @return array
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function get_products(
        array $query = []
    ): array {
        return $this->api_client->get(
            self::PRODUCTS_ENDPOINT,
            $query
        );
    }


    /**
     * Get updated Amrod products.
     *
     * This endpoint is intended for incremental
     * product synchronisation.
     *
     * This is a read-only operation.
     *
     * @param array $query Optional query parameters.
     *
     * @return array
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function get_updated_products(
        array $query = []
    ): array {
        return $this->api_client->get(
            self::UPDATED_PRODUCTS_ENDPOINT,
            $query
        );
    }


    /**
     * Get all Amrod products including branding data.
     *
     * This is a read-only operation.
     *
     * @param array $query Optional query parameters.
     *
     * @return array
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function get_products_with_branding(
        array $query = []
    ): array {
        return $this->api_client->get(
            self::PRODUCTS_WITH_BRANDING_ENDPOINT,
            $query
        );
    }


    /**
     * Get updated Amrod products including branding data.
     *
     * This endpoint is intended for incremental
     * product and branding synchronisation.
     *
     * This is a read-only operation.
     *
     * @param array $query Optional query parameters.
     *
     * @return array
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function get_updated_products_with_branding(
        array $query = []
    ): array {
        return $this->api_client->get(
            self::UPDATED_PRODUCTS_WITH_BRANDING_ENDPOINT,
            $query
        );
    }


    /**
     * Get the full products endpoint path.
     *
     * @return string
     */
    public function get_products_endpoint(): string
    {
        return self::PRODUCTS_ENDPOINT;
    }


    /**
     * Get the updated products endpoint path.
     *
     * @return string
     */
    public function get_updated_products_endpoint(): string
    {
        return self::UPDATED_PRODUCTS_ENDPOINT;
    }


    /**
     * Get the products with branding endpoint path.
     *
     * @return string
     */
    public function get_products_with_branding_endpoint(): string
    {
        return self::PRODUCTS_WITH_BRANDING_ENDPOINT;
    }


    /**
     * Get the updated products with branding endpoint path.
     *
     * @return string
     */
    public function get_updated_products_with_branding_endpoint(): string
    {
        return self::UPDATED_PRODUCTS_WITH_BRANDING_ENDPOINT;
    }
}