<?php

namespace BlackPrint\Commerce\Suppliers\Amrod;

defined('ABSPATH') || exit;

/**
 * Amrod Brand Service.
 *
 * Provides read-only access to the Brands endpoint
 * of the Amrod Vendor API.
 *
 * Responsibilities:
 * - Retrieve Amrod brands.
 * - Validate the returned brand structure.
 * - Cache the read-only API response.
 * - Provide a manual cache refresh mechanism.
 *
 * This service does not:
 * - Create WooCommerce brands.
 * - Create WooCommerce products.
 * - Create media attachments.
 * - Modify WooCommerce data.
 * - Modify supplier data.
 *
 * @package BlackPrint\Commerce
 */
class Amrod_Brand_Service
{
    /**
     * Cache key for Amrod brands.
     *
     * @var string
     */
    private const CACHE_KEY = 'bp_amrod_brands';


    /**
     * Cache lifetime.
     *
     * @var int
     */
    private const CACHE_TTL = 900;


    /**
     * Amrod API client.
     *
     * @var Amrod_Api_Client
     */
    private Amrod_Api_Client $api_client;


    /**
     * Brands endpoint.
     *
     * @var string
     */
    private const ENDPOINT = '/api/v1/Brands/';


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
     * Get Amrod brands.
     *
     * Returns cached data when available.
     *
     * @return array
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function get_brands(): array
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
     * Refresh Amrod brands.
     *
     * Forces a fresh read-only request to the
     * Amrod Brands endpoint.
     *
     * @return array
     *
     * @throws \RuntimeException When the API request fails.
     */
    public function refresh(): array
    {
        $data = $this->api_client->get(
            self::ENDPOINT
        );


        $brands = $this->normalize_brands(
            $data
        );


        set_transient(
            self::CACHE_KEY,
            $brands,
            self::CACHE_TTL
        );


        return $brands;
    }


    /**
     * Clear cached Amrod brands.
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
     * Get the Brands API endpoint.
     *
     * @return string
     */
    public function get_endpoint(): string
    {
        return self::ENDPOINT;
    }


    /**
     * Normalize the Amrod Brands response.
     *
     * The service keeps the supplier response isolated
     * from the rest of BlackPrint OS.
     *
     * @param array $data Raw API response.
     *
     * @return array
     */
    private function normalize_brands(
        array $data
    ): array {
        $brands = [];


        foreach (
            $data as $brand
        ) {
            if (
                ! is_array($brand)
            ) {
                continue;
            }


            $name = isset($brand['name'])
                ? sanitize_text_field(
                    (string) $brand['name']
                )
                : '';


            $code = isset($brand['code'])
                ? sanitize_text_field(
                    (string) $brand['code']
                )
                : '';


            $order = isset($brand['order'])
                ? (int) $brand['order']
                : 99999;


            $image = isset($brand['image'])
                ? esc_url_raw(
                    (string) $brand['image']
                )
                : '';


            if (
                $name === ''
                && $code === ''
            ) {
                continue;
            }


            $brands[] = [
                'name'  => $name,
                'code'  => $code,
                'order' => $order,
                'image' => $image,
            ];
        }


        return $brands;
    }
}