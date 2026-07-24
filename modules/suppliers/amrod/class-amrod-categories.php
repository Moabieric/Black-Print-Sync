<?php

namespace BlackPrint\Commerce\Suppliers\Amrod;

defined('ABSPATH') || exit;

/**
 * Amrod Categories Service.
 *
 * Provides read-only access to Amrod category data.
 *
 * Responsibilities:
 * - Request category data from the Amrod Vendor API.
 * - Return raw supplier category data.
 * - Keep Amrod category retrieval isolated from
 *   BlackPrint category business rules.
 *
 * This class does not:
 * - Create WooCommerce categories.
 * - Update WooCommerce categories.
 * - Delete categories.
 * - Assign products to categories.
 * - Modify supplier data.
 * - Perform synchronization.
 *
 * @package BlackPrint\Commerce
 */
class Amrod_Categories
{
    /**
     * Amrod API client.
     *
     * @var Amrod_Api_Client
     */
    private Amrod_Api_Client $api_client;


    /**
     * Categories API endpoint.
     *
     * @var string
     */
    private const ENDPOINT = '/api/v1/Categories/';


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
     * Retrieve categories from Amrod.
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
            self::ENDPOINT
        );
    }


    /**
     * Get the categories API endpoint.
     *
     * @return string
     */
    public function get_endpoint(): string
    {
        return self::ENDPOINT;
    }
}