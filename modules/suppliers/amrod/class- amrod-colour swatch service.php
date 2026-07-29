<?php

namespace BlackPrint\Commerce\Suppliers\Amrod;

defined('ABSPATH') || exit;

/**
 * Amrod Colour Swatch Service.
 *
 * Provides read-only access to colour swatch data
 * from the Amrod Vendor API.
 *
 * Responsibilities:
 * - Retrieve the full colour swatch catalogue.
 * - Retrieve updated colour swatches.
 * - Provide controlled access to supplier colour
 *   swatch information.
 *
 * This service does not:
 * - Create WooCommerce products.
 * - Update WooCommerce products.
 * - Modify WooCommerce data.
 * - Apply BlackPrint business rules.
 * - Apply branding rules.
 * - Normalise supplier data.
 *
 * @package BlackPrint\Commerce
 */
class Amrod_Colour_Swatch_Service
{
    /**
     * Full colour swatches endpoint.
     *
     * @var string
     */
    private const COLOUR_SWATCHES_ENDPOINT =
        '/api/v1/ColourSwatches/';


    /**
     * Updated colour swatches endpoint.
     *
     * @var string
     */
    private const UPDATED_COLOUR_SWATCHES_ENDPOINT =
        '/api/v1/ColourSwatches/GetUpdated';


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
     * Get all Amrod colour swatches.
     *
     * This is a read-only operation.
     *
     * @param array $query Optional query parameters.
     *
     * @return array
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function get_colour_swatches(
        array $query = []
    ): array {
        return $this->api_client->get(
            self::COLOUR_SWATCHES_ENDPOINT,
            $query
        );
    }


    /**
     * Get updated Amrod colour swatches.
     *
     * This endpoint is intended for incremental
     * colour swatch synchronisation.
     *
     * This is a read-only operation.
     *
     * @param array $query Optional query parameters.
     *
     * @return array
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function get_updated_colour_swatches(
        array $query = []
    ): array {
        return $this->api_client->get(
            self::UPDATED_COLOUR_SWATCHES_ENDPOINT,
            $query
        );
    }


    /**
     * Get the full colour swatches endpoint path.
     *
     * @return string
     */
    public function get_colour_swatches_endpoint(): string
    {
        return self::COLOUR_SWATCHES_ENDPOINT;
    }


    /**
     * Get the updated colour swatches endpoint path.
     *
     * @return string
     */
    public function get_updated_colour_swatches_endpoint(): string
    {
        return self::UPDATED_COLOUR_SWATCHES_ENDPOINT;
    }
}