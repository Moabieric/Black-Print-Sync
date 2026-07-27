<?php

namespace BlackPrint\Commerce\Suppliers\Amrod;

defined('ABSPATH') || exit;

/**
 * Amrod Branding Department Service.
 *
 * Provides read-only access to branding department data
 * from the Amrod Vendor API.
 *
 * Responsibilities:
 * - Retrieve the full Amrod branding department catalogue.
 * - Retrieve updated Amrod branding departments.
 * - Provide controlled access to supplier branding department data.
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
class Amrod_Branding_Department_Service
{
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
     * Get all Amrod branding department data.
     *
     * This is a read-only operation.
     *
     * @param array $query Optional query parameters.
     *
     * @return array
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function get_branding_departments(
        array $query = []
    ): array {
        return $this->api_client->get(
            self::BRANDING_DEPARTMENTS_ENDPOINT,
            $query
        );
    }


    /**
     * Get updated Amrod branding department data.
     *
     * This endpoint is intended for incremental
     * branding department synchronisation.
     *
     * This is a read-only operation.
     *
     * @param array $query Optional query parameters.
     *
     * @return array
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function get_updated_branding_departments(
        array $query = []
    ): array {
        return $this->api_client->get(
            self::UPDATED_BRANDING_DEPARTMENTS_ENDPOINT,
            $query
        );
    }


    /**
     * Get the full branding departments endpoint path.
     *
     * @return string
     */
    public function get_branding_departments_endpoint(): string
    {
        return self::BRANDING_DEPARTMENTS_ENDPOINT;
    }


    /**
     * Get the updated branding departments endpoint path.
     *
     * @return string
     */
    public function get_updated_branding_departments_endpoint(): string
    {
        return self::UPDATED_BRANDING_DEPARTMENTS_ENDPOINT;
    }
}