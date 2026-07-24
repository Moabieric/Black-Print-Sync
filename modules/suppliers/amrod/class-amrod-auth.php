<?php

namespace BlackPrint\Commerce\Suppliers\Amrod;

defined('ABSPATH') || exit;

/**
 * Amrod Authentication Service.
 *
 * Responsible for authenticating with the Amrod Vendor API
 * and managing the short-lived Bearer authentication token.
 *
 * Responsibilities:
 * - Authenticate against VendorLogin.
 * - Retrieve the Bearer token.
 * - Cache the token using a WordPress transient.
 * - Return cached tokens when available.
 * - Refresh expired or invalid tokens.
 *
 * The actual Bearer token is never stored in plugin source code.
 *
 * @package BlackPrint\Commerce
 */
class Amrod_Auth
{
    /**
     * Authentication service configuration.
     *
     * @var Amrod_Config
     */
    private Amrod_Config $config;


    /**
     * WordPress transient used to cache the token.
     *
     * @var string
     */
    private const TOKEN_TRANSIENT =
        'bp_amrod_api_token';


    /**
     * Token cache lifetime in seconds.
     *
     * Amrod tokens have an approximately one-hour lifetime.
     * We cache for slightly less than one hour.
     *
     * @var int
     */
    private const TOKEN_TTL = 3300;


    /**
     * Constructor.
     *
     * @param Amrod_Config $config Amrod configuration service.
     */
    public function __construct(
        Amrod_Config $config
    ) {
        $this->config = $config;
    }


    /**
     * Get a valid Amrod Bearer token.
     *
     * Returns the cached token when available.
     * Otherwise performs fresh authentication.
     *
     * @return string
     *
     * @throws \RuntimeException When authentication fails.
     */
    public function get_token(): string
    {
        $cached_token = get_transient(
            self::TOKEN_TRANSIENT
        );

        if (
            is_string($cached_token)
            && trim($cached_token) !== ''
        ) {
            return $cached_token;
        }

        return $this->authenticate();
    }


    /**
     * Authenticate with Amrod VendorLogin.
     *
     * @return string
     *
     * @throws \RuntimeException When authentication fails.
     */
    public function authenticate(): string
    {
        $response = wp_remote_post(
            $this->config->get_auth_url(),
            [
                'timeout' => 30,

                'headers' => [
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ],

                'body' => wp_json_encode(
                    [
                        'UserName' => $this->config->get_username(),

                        'Password' => $this->config->get_password(),

                        'CustomerCode' =>
                            $this->config->get_customer_code(),
                    ]
                ),
            ]
        );


        /**
         * WordPress HTTP transport error.
         */
        if (
            is_wp_error($response)
        ) {
            throw new \RuntimeException(
                sprintf(
                    'Amrod authentication request failed: %s',
                    $response->get_error_message()
                )
            );
        }


        $status_code = wp_remote_retrieve_response_code(
            $response
        );

        $response_body = wp_remote_retrieve_body(
            $response
        );


        /**
         * Handle non-successful authentication responses.
         */
        if (
            $status_code < 200
            || $status_code >= 300
        ) {
            throw new \RuntimeException(
                $this->format_authentication_error(
                    $status_code,
                    $response_body
                )
            );
        }


        /**
         * Decode authentication response.
         */
        $data = json_decode(
            $response_body,
            true
        );


        if (
            json_last_error()
            !== JSON_ERROR_NONE
        ) {
            throw new \RuntimeException(
                sprintf(
                    'Amrod authentication returned invalid JSON. JSON error: %s',
                    json_last_error_msg()
                )
            );
        }


        /**
         * Validate response structure.
         */
        if (
            ! is_array($data)
            || ! isset($data['token'])
            || ! is_string($data['token'])
            || trim($data['token']) === ''
        ) {
            throw new \RuntimeException(
                'Amrod authentication succeeded but the response did not contain a valid token.'
            );
        }


        $token = trim(
            $data['token']
        );


        /**
         * Cache the token.
         */
        set_transient(
            self::TOKEN_TRANSIENT,
            $token,
            self::TOKEN_TTL
        );


        return $token;
    }


    /**
     * Clear the cached authentication token.
     *
     * Should be called when the Vendor API returns HTTP 401.
     *
     * @return void
     */
    public function clear_token(): void
    {
        delete_transient(
            self::TOKEN_TRANSIENT
        );
    }


    /**
     * Force a fresh authentication.
     *
     * @return string
     *
     * @throws \RuntimeException When authentication fails.
     */
    public function refresh_token(): string
    {
        $this->clear_token();

        return $this->authenticate();
    }


    /**
     * Determine whether a cached token currently exists.
     *
     * This does not validate the token against Amrod.
     *
     * @return bool
     */
    public function has_cached_token(): bool
    {
        $token = get_transient(
            self::TOKEN_TRANSIENT
        );

        return (
            is_string($token)
            && trim($token) !== ''
        );
    }


    /**
     * Format an authentication error.
     *
     * Never exposes the password.
     *
     * @param int    $status_code HTTP status code.
     * @param string $body        Response body.
     *
     * @return string
     */
    private function format_authentication_error(
        int $status_code,
        string $body
    ): string {
        $message = sprintf(
            'Amrod authentication failed with HTTP status %d.',
            $status_code
        );


        if (
            trim($body) === ''
        ) {
            return $message;
        }


        $decoded = json_decode(
            $body,
            true
        );


        if (
            is_array($decoded)
        ) {
            foreach (
                [
                    'message',
                    'Message',
                    'error',
                    'Error',
                    'error_description',
                    'ErrorDescription',
                ] as $key
            ) {
                if (
                    isset($decoded[$key])
                    && is_string($decoded[$key])
                    && trim($decoded[$key]) !== ''
                ) {
                    return $message
                        . ' '
                        . sanitize_text_field(
                            $decoded[$key]
                        );
                }
            }
        }


        $plain_message = trim(
            wp_strip_all_tags(
                $body
            )
        );


        if (
            $plain_message !== ''
        ) {
            return $message
                . ' '
                . sanitize_text_field(
                    $plain_message
                );
        }


        return $message;
    }
}