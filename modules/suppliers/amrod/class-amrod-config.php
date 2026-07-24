<?php

namespace BlackPrint\Commerce\Suppliers\Amrod;

defined('ABSPATH') || exit;

/**
 * Amrod Configuration.
 *
 * Central configuration service for the Amrod supplier connector.
 *
 * Responsibilities:
 * - Provide Amrod API endpoint configuration.
 * - Provide access to Amrod credentials.
 * - Keep supplier-specific configuration isolated
 *   from the rest of BlackPrint OS.
 *
 * Credentials are expected to be defined in wp-config.php.
 *
 * Required constants:
 *
 * BP_AMROD_USERNAME
 * BP_AMROD_PASSWORD
 * BP_AMROD_CUSTOMER_CODE
 *
 * @package BlackPrint\Commerce
 */
class Amrod_Config
{
    /**
     * Amrod authentication URL.
     *
     * @var string
     */
    private const AUTH_URL =
        'https://identity.amrod.co.za/VendorLogin';


    /**
     * Amrod Vendor API base URL.
     *
     * @var string
     */
    private const VENDOR_API_URL =
        'https://vendorapi.amrod.co.za';


    /**
     * Get the Amrod authentication URL.
     *
     * @return string
     */
    public function get_auth_url(): string
    {
        return self::AUTH_URL;
    }


    /**
     * Get the Amrod Vendor API base URL.
     *
     * @return string
     */
    public function get_vendor_api_url(): string
    {
        return self::VENDOR_API_URL;
    }


    /**
     * Get the Amrod API username.
     *
     * @return string
     *
     * @throws \RuntimeException When the username is not configured.
     */
    public function get_username(): string
    {
        if (
            ! defined('BP_AMROD_USERNAME')
            || trim((string) BP_AMROD_USERNAME) === ''
        ) {
            throw new \RuntimeException(
                'Amrod API username is not configured.'
            );
        }

        return trim(
            (string) BP_AMROD_USERNAME
        );
    }


    /**
     * Get the Amrod API password.
     *
     * @return string
     *
     * @throws \RuntimeException When the password is not configured.
     */
    public function get_password(): string
    {
        if (
            ! defined('BP_AMROD_PASSWORD')
            || (string) BP_AMROD_PASSWORD === ''
        ) {
            throw new \RuntimeException(
                'Amrod API password is not configured.'
            );
        }

        return (string) BP_AMROD_PASSWORD;
    }


    /**
     * Get the Amrod customer code.
     *
     * @return string
     *
     * @throws \RuntimeException When the customer code is not configured.
     */
    public function get_customer_code(): string
    {
        if (
            ! defined('BP_AMROD_CUSTOMER_CODE')
            || trim((string) BP_AMROD_CUSTOMER_CODE) === ''
        ) {
            throw new \RuntimeException(
                'Amrod API customer code is not configured.'
            );
        }

        return trim(
            (string) BP_AMROD_CUSTOMER_CODE
        );
    }


    /**
     * Determine whether all required Amrod credentials are configured.
     *
     * This method does not expose credential values.
     *
     * @return bool
     */
    public function has_credentials(): bool
    {
        return (
            defined('BP_AMROD_USERNAME')
            && trim((string) BP_AMROD_USERNAME) !== ''
            && defined('BP_AMROD_PASSWORD')
            && (string) BP_AMROD_PASSWORD !== ''
            && defined('BP_AMROD_CUSTOMER_CODE')
            && trim((string) BP_AMROD_CUSTOMER_CODE) !== ''
        );
    }
}