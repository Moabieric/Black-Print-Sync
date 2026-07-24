<?php

namespace BlackPrint\Commerce\Suppliers\Amrod;

defined('ABSPATH') || exit;

/**
 * Amrod Vendor API Client.
 *
 * Provides the HTTP communication layer between BlackPrint OS
 * and the Amrod Vendor API.
 *
 * Responsibilities:
 * - Authentication headers.
 * - GET and POST requests.
 * - Bearer token handling.
 * - Token refresh after HTTP 401.
 * - HTTP error handling.
 * - Amrod API maintenance window handling.
 * - JSON response decoding.
 *
 * This class does not contain product, stock, pricing,
 * category, or branding business logic.
 *
 * @package BlackPrint\Commerce
 */
class Amrod_Api_Client
{
    /**
     * Amrod connector configuration.
     *
     * @var Amrod_Config
     */
    private Amrod_Config $config;


    /**
     * Authentication service.
     *
     * @var Amrod_Auth
     */
    private Amrod_Auth $auth;


    /**
     * Default HTTP timeout in seconds.
     *
     * @var int
     */
    private const DEFAULT_TIMEOUT = 60;


    /**
     * Maximum number of authentication retries.
     *
     * A 401 response triggers one token refresh and retry.
     *
     * @var int
     */
    private const MAX_AUTH_RETRIES = 1;


    /**
     * Constructor.
     *
     * @param Amrod_Auth   $auth   Authentication service.
     * @param Amrod_Config $config Amrod connector configuration.
     */
    public function __construct(
        Amrod_Auth $auth,
        Amrod_Config $config
    ) {
        $this->auth   = $auth;
        $this->config = $config;
    }


    /**
     * Perform a GET request.
     *
     * @param string $endpoint API endpoint path.
     * @param array  $query    Optional query parameters.
     *
     * @return array
     *
     * @throws \RuntimeException On API or HTTP failure.
     */
    public function get(
        string $endpoint,
        array $query = []
    ): array {
        return $this->request(
            'GET',
            $endpoint,
            $query
        );
    }


    /**
     * Perform a POST request.
     *
     * @param string $endpoint API endpoint path.
     * @param array  $body     Request body.
     *
     * @return array
     *
     * @throws \RuntimeException On API or HTTP failure.
     */
    public function post(
        string $endpoint,
        array $body = []
    ): array {
        return $this->request(
            'POST',
            $endpoint,
            [],
            $body
        );
    }


    /**
     * Execute an authenticated API request.
     *
     * @param string $method       HTTP method.
     * @param string $endpoint     API endpoint path.
     * @param array  $query        Query parameters.
     * @param array  $body         Request body.
     * @param int    $auth_retries Number of authentication retries.
     *
     * @return array
     *
     * @throws \RuntimeException On API or HTTP failure.
     */
    private function request(
        string $method,
        string $endpoint,
        array $query = [],
        array $body = [],
        int $auth_retries = 0
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        $token = $this->auth->get_token();


        /*
        |--------------------------------------------------------------------------
        | Build Request URL
        |--------------------------------------------------------------------------
        */

        $url = $this->build_url(
            $endpoint,
            $query
        );


        /*
        |--------------------------------------------------------------------------
        | Build HTTP Request
        |--------------------------------------------------------------------------
        */

        $method = strtoupper(
            $method
        );

        $args = [
            'method'  => $method,
            'timeout' => self::DEFAULT_TIMEOUT,

            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
        ];


        /*
        |--------------------------------------------------------------------------
        | Request Body
        |--------------------------------------------------------------------------
        |
        | Add JSON request body for methods that support
        | a request payload.
        |
        */

        if (
            in_array(
                $method,
                [
                    'POST',
                    'PUT',
                    'PATCH',
                ],
                true
            )
        ) {
            $args['body'] = wp_json_encode(
                $body
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Execute Request
        |--------------------------------------------------------------------------
        */

        $response = wp_remote_request(
            $url,
            $args
        );


        /*
        |--------------------------------------------------------------------------
        | WordPress HTTP Transport Error
        |--------------------------------------------------------------------------
        */

        if (
            is_wp_error($response)
        ) {
            throw new \RuntimeException(
                sprintf(
                    'Amrod API request failed: %s',
                    $response->get_error_message()
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Read Response
        |--------------------------------------------------------------------------
        */

        $status_code = wp_remote_retrieve_response_code(
            $response
        );

        $response_body = wp_remote_retrieve_body(
            $response
        );


        /*
        |--------------------------------------------------------------------------
        | Amrod Maintenance Window
        |--------------------------------------------------------------------------
        |
        | According to the Amrod API documentation, the API
        | may return HTTP 204 during its scheduled maintenance
        | period.
        |
        | This must NOT be interpreted as an empty successful
        | dataset.
        |
        | No existing data may be overwritten based on this
        | response.
        |
        */

        if (
            $status_code === 204
        ) {
            throw new \RuntimeException(
                'Amrod API is temporarily unavailable or returned no content (HTTP 204). The API may be within its scheduled maintenance window.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Authentication Expired
        |--------------------------------------------------------------------------
        |
        | Clear the old token, authenticate again and retry
        | the original request once.
        |
        */

        if (
            $status_code === 401
        ) {
            if (
                $auth_retries
                < self::MAX_AUTH_RETRIES
            ) {
                $this->auth->refresh_token();

                return $this->request(
                    $method,
                    $endpoint,
                    $query,
                    $body,
                    $auth_retries + 1
                );
            }


            throw new \RuntimeException(
                'Amrod API authentication failed after refreshing the authentication token.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | HTTP Error Response
        |--------------------------------------------------------------------------
        */

        if (
            $status_code < 200
            || $status_code >= 300
        ) {
            throw new \RuntimeException(
                $this->format_api_error(
                    $status_code,
                    $response_body,
                    $endpoint
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Empty Successful Response
        |--------------------------------------------------------------------------
        */

        if (
            trim(
                $response_body
            ) === ''
        ) {
            return [];
        }


        /*
        |--------------------------------------------------------------------------
        | Decode JSON Response
        |--------------------------------------------------------------------------
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
                    'Amrod API returned invalid JSON for endpoint "%s". JSON error: %s',
                    $endpoint,
                    json_last_error_msg()
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Response Structure
        |--------------------------------------------------------------------------
        */

        if (
            ! is_array($data)
        ) {
            throw new \RuntimeException(
                sprintf(
                    'Amrod API returned an unexpected response format for endpoint "%s".',
                    $endpoint
                )
            );
        }


        return $data;
    }


    /**
     * Build the complete Amrod Vendor API URL.
     *
     * @param string $endpoint API endpoint path.
     * @param array  $query    Query parameters.
     *
     * @return string
     */
    private function build_url(
        string $endpoint,
        array $query = []
    ): string {
        $url = rtrim(
            $this->config->get_vendor_api_url(),
            '/'
        )
        . '/'
        . ltrim(
            $endpoint,
            '/'
        );


        if (
            ! empty($query)
        ) {
            $url = add_query_arg(
                $query,
                $url
            );
        }


        return $url;
    }


    /**
     * Format a failed API response.
     *
     * @param int    $status_code HTTP status code.
     * @param string $body        Response body.
     * @param string $endpoint    API endpoint.
     *
     * @return string
     */
    private function format_api_error(
        int $status_code,
        string $body,
        string $endpoint
    ): string {
        $message = sprintf(
            'Amrod API request to "%s" failed with HTTP status %d.',
            $endpoint,
            $status_code
        );


        /*
        |--------------------------------------------------------------------------
        | Empty Error Response
        |--------------------------------------------------------------------------
        */

        if (
            trim(
                $body
            ) === ''
        ) {
            return $message;
        }


        /*
        |--------------------------------------------------------------------------
        | JSON Error Response
        |--------------------------------------------------------------------------
        */

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
                    && is_string(
                        $decoded[$key]
                    )
                    && $decoded[$key] !== ''
                ) {
                    return $message
                        . ' '
                        . sanitize_text_field(
                            $decoded[$key]
                        );
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Plain Text Error Response
        |--------------------------------------------------------------------------
        */

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