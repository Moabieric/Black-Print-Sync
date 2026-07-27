<?php

namespace BlackPrint\Commerce\Suppliers\Amrod;

defined('ABSPATH') || exit;

/**
 * Amrod Stock Service.
 *
 * Provides read-only access to stock data
 * from the Amrod Vendor API.
 *
 * Responsibilities:
 * - Retrieve the full Amrod stock catalogue.
 * - Retrieve updated Amrod stock.
 * - Provide controlled access to supplier stock data.
 *
 * This service does not:
 * - Create WooCommerce products.
 * - Update WooCommerce stock.
 * - Modify WooCommerce data.
 * - Apply BlackPrint business rules.
 * - Normalise supplier data.
 *
 * @package BlackPrint\Commerce
 */
class Amrod_Stock_Service
{
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
     * Get all Amrod stock data.
     *
     * This is a read-only operation.
     *
     * @param array $query Optional query parameters.
     *
     * @return array
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function get_stock(
        array $query = []
    ): array {
        return $this->api_client->get(
            self::STOCK_ENDPOINT,
            $query
        );
    }


    /**
     * Get updated Amrod stock data.
     *
     * This endpoint is intended for incremental
     * stock synchronisation.
     *
     * This is a read-only operation.
     *
     * @param array $query Optional query parameters.
     *
     * @return array
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function get_updated_stock(
        array $query = []
    ): array {
        return $this->api_client->get(
            self::UPDATED_STOCK_ENDPOINT,
            $query
        );
    }


    /**
     * Get the full stock endpoint path.
     *
     * @return string
     */
    public function get_stock_endpoint(): string
    {
        return self::STOCK_ENDPOINT;
    }


    /**
     * Get the updated stock endpoint path.
     *
     * @return string
     */
    public function get_updated_stock_endpoint(): string
    {
        return self::UPDATED_STOCK_ENDPOINT;
    }
}