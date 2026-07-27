<?php

namespace BlackPrint\Commerce\Suppliers\Amrod;

defined('ABSPATH') || exit;

/**
 * Amrod Price Service.
 *
 * Provides read-only access to price data
 * from the Amrod Vendor API.
 *
 * Responsibilities:
 * - Retrieve the full Amrod price catalogue.
 * - Retrieve updated Amrod prices.
 * - Provide controlled access to supplier price data.
 *
 * This service does not:
 * - Create WooCommerce products.
 * - Update WooCommerce prices.
 * - Apply BlackPrint margins.
 * - Apply BlackPrint markup.
 * - Apply VAT rules.
 * - Calculate customer selling prices.
 * - Modify WooCommerce data.
 * - Normalise supplier data.
 *
 * @package BlackPrint\Commerce
 */
class Amrod_Price_Service
{
    /**
     * Full prices endpoint.
     *
     * @var string
     */
    private const PRICES_ENDPOINT =
        '/api/v1/Prices/';


    /**
     * Updated prices endpoint.
     *
     * @var string
     */
    private const UPDATED_PRICES_ENDPOINT =
        '/api/v1/Prices/GetUpdated';


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
     * Get all Amrod price data.
     *
     * This is a read-only operation.
     *
     * The returned data represents raw supplier pricing.
     *
     * No BlackPrint pricing rules are applied.
     *
     * @param array $query Optional query parameters.
     *
     * @return array
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function get_prices(
        array $query = []
    ): array {
        return $this->api_client->get(
            self::PRICES_ENDPOINT,
            $query
        );
    }


    /**
     * Get updated Amrod price data.
     *
     * This endpoint is intended for incremental
     * price synchronisation.
     *
     * This is a read-only operation.
     *
     * The returned data represents raw supplier pricing.
     *
     * No BlackPrint pricing rules are applied.
     *
     * @param array $query Optional query parameters.
     *
     * @return array
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function get_updated_prices(
        array $query = []
    ): array {
        return $this->api_client->get(
            self::UPDATED_PRICES_ENDPOINT,
            $query
        );
    }


    /**
     * Get the full prices endpoint path.
     *
     * @return string
     */
    public function get_prices_endpoint(): string
    {
        return self::PRICES_ENDPOINT;
    }


    /**
     * Get the updated prices endpoint path.
     *
     * @return string
     */
    public function get_updated_prices_endpoint(): string
    {
        return self::UPDATED_PRICES_ENDPOINT;
    }
}