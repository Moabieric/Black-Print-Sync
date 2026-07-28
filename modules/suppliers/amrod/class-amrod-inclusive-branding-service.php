<?php

namespace BlackPrint\Commerce\Suppliers\Amrod;

defined('ABSPATH') || exit;

/**
 * Amrod Inclusive Branding Service.
 *
 * Provides read-only access to inclusive branding data
 * from the Amrod Vendor API.
 *
 * Responsibilities:
 * - Retrieve the full inclusive branding catalogue.
 * - Retrieve updated inclusive branding data.
 * - Provide controlled access to supplier inclusive
 *   branding information.
 *
 * This service does not:
 * - Create WooCommerce products.
 * - Update WooCommerce products.
 * - Modify WooCommerce data.
 * - Apply BlackPrint business rules.
 * - Apply branding pricing rules.
 * - Normalise supplier data.
 *
 * @package BlackPrint\Commerce
 */
class Amrod_Inclusive_Branding_Service
{
    /**
     * Full inclusive branding endpoint.
     *
     * @var string
     */
    private const INCLUSIVE_BRANDING_ENDPOINT =
        '/api/v1/InclusiveBrandings/';


    /**
     * Updated inclusive branding endpoint.
     *
     * @var string
     */
    private const UPDATED_INCLUSIVE_BRANDING_ENDPOINT =
        '/api/v1/InclusiveBrandings/GetUpdated';


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
     * Get all Amrod inclusive branding data.
     *
     * This is a read-only operation.
     *
     * @param array $query Optional query parameters.
     *
     * @return array
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function get_inclusive_branding(
        array $query = []
    ): array {
        return $this->api_client->get(
            self::INCLUSIVE_BRANDING_ENDPOINT,
            $query
        );
    }


    /**
     * Get updated Amrod inclusive branding data.
     *
     * This endpoint is intended for incremental
     * inclusive branding synchronisation.
     *
     * This is a read-only operation.
     *
     * @param array $query Optional query parameters.
     *
     * @return array
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function get_updated_inclusive_branding(
        array $query = []
    ): array {
        return $this->api_client->get(
            self::UPDATED_INCLUSIVE_BRANDING_ENDPOINT,
            $query
        );
    }


    /**
     * Get the full inclusive branding endpoint path.
     *
     * @return string
     */
    public function get_inclusive_branding_endpoint(): string
    {
        return self::INCLUSIVE_BRANDING_ENDPOINT;
    }


    /**
     * Get the updated inclusive branding endpoint path.
     *
     * @return string
     */
    public function get_updated_inclusive_branding_endpoint(): string
    {
        return self::UPDATED_INCLUSIVE_BRANDING_ENDPOINT;
    }
}