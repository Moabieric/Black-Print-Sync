<?php

namespace BlackPrint\Commerce\Suppliers\Amrod;

defined('ABSPATH') || exit;

/**
 * Amrod Category Service.
 *
 * Provides a controlled, read-only service for retrieving
 * category data from the Amrod Vendor API.
 *
 * Responsibilities:
 * - Retrieve Amrod categories.
 * - Cache category data.
 * - Refresh category data on demand.
 * - Clear cached category data.
 *
 * This service does not:
 * - Create WooCommerce categories.
 * - Update WooCommerce categories.
 * - Assign products to categories.
 * - Modify supplier data.
 * - Apply BlackPrint taxonomy rules.
 * - Synchronise category data to WooCommerce.
 *
 * @package BlackPrint\Commerce
 */
class Amrod_Category_Service
{
    /**
     * WordPress transient used to cache categories.
     *
     * @var string
     */
    private const CACHE_KEY =
        'bp_amrod_categories';


    /**
     * Category cache lifetime.
     *
     * @var int
     */
    private const CACHE_TTL =
        3600;


    /**
     * Amrod API client.
     *
     * @var Amrod_Api_Client
     */
    private Amrod_Api_Client $api_client;


    /**
     * Categories endpoint.
     *
     * @var string
     */
    private const CATEGORIES_ENDPOINT =
        '/api/v1/Categories/';


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
     * Get Amrod categories.
     *
     * Returns cached categories when available.
     *
     * If no cached data exists, a fresh read-only
     * API request is performed.
     *
     * @return array
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function get_categories(): array
    {
        $cached = get_transient(
            self::CACHE_KEY
        );


        if (
            is_array($cached)
        ) {
            return $cached;
        }


        return $this->refresh();
    }


    /**
     * Refresh Amrod categories.
     *
     * Performs a fresh read-only API request
     * and replaces the cached category data.
     *
     * @return array
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function refresh(): array
    {
        $categories = $this->api_client->get(
            self::CATEGORIES_ENDPOINT
        );


        if (
            ! is_array($categories)
        ) {
            throw new \RuntimeException(
                'Amrod Categories endpoint returned an invalid response.'
            );
        }


        set_transient(
            self::CACHE_KEY,
            $categories,
            self::CACHE_TTL
        );


        return $categories;
    }


    /**
     * Clear the cached Amrod categories.
     *
     * This does not make an API request.
     *
     * @return void
     */
    public function clear_cache(): void
    {
        delete_transient(
            self::CACHE_KEY
        );
    }


    /**
     * Get the categories endpoint.
     *
     * @return string
     */
    public function get_endpoint(): string
    {
        return self::CATEGORIES_ENDPOINT;
    }
}