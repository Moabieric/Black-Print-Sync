<?php

namespace BlackPrint\Commerce\Suppliers\Amrod;

defined('ABSPATH') || exit;

/**
 * Amrod Connector Health Check.
 *
 * Performs a safe, read-only end-to-end health check of the
 * Amrod supplier connector.
 *
 * Checks:
 * - Connector availability.
 * - Authentication.
 * - Bearer token availability.
 * - Vendor API connectivity.
 * - Valid response from the Brands endpoint.
 *
 * This class does not:
 * - Create products.
 * - Update products.
 * - Modify WooCommerce data.
 * - Modify supplier data.
 * - Run product synchronisation.
 *
 * @package BlackPrint\Commerce
 */
class Amrod_Health_Check
{
    /**
     * Read-only Amrod API endpoint used for connectivity testing.
     *
     * @var string
     */
    private const HEALTH_ENDPOINT = '/api/v1/Brands/';


    /**
     * Amrod connector.
     *
     * @var Amrod_Connector
     */
    private Amrod_Connector $connector;


    /**
     * Constructor.
     *
     * @param Amrod_Connector $connector Amrod connector instance.
     */
    public function __construct(
        Amrod_Connector $connector
    ) {
        $this->connector = $connector;
    }


    /**
     * Run the complete Amrod health check.
     *
     * The test performs:
     *
     * 1. Connector check.
     * 2. Authentication check.
     * 3. Read-only Brands API request.
     *
     * @return array{
     *     supplier: string,
     *     supplier_id: string,
     *     endpoint: string,
     *     status: string,
     *     checks: array{
     *         connector: array{
     *             status: string,
     *             message: string
     *         },
     *         authentication: array{
     *             status: string,
     *             message: string,
     *             cached?: bool
     *         },
     *         api: array{
     *             status: string,
     *             message: string,
     *             records?: int
     *         }
     *     }
     * }
     */
    public function run(): array
    {
        $result = [
            'supplier' => $this->connector->get_name(),

            'supplier_id' => $this->connector->get_id(),

            'endpoint' => self::HEALTH_ENDPOINT,

            'status' => 'unhealthy',

            'checks' => [
                'connector' => [
                    'status' => 'pass',
                    'message' =>
                        'Amrod Connector loaded successfully.',
                ],

                'authentication' => [
                    'status' => 'pending',
                    'message' => '',
                ],

                'api' => [
                    'status' => 'pending',
                    'message' => '',
                ],
            ],
        ];


        /*
        |--------------------------------------------------------------------------
        | Authentication Check
        |--------------------------------------------------------------------------
        */

        $authentication = $this->connector
            ->test_authentication();


        if (
            ! $authentication['success']
        ) {
            $result['checks']['authentication'] = [
                'status' => 'fail',

                'message' =>
                    $authentication['message'],

                'cached' =>
                    $authentication['cached'],
            ];


            $result['checks']['api'] = [
                'status' => 'skipped',

                'message' =>
                    'API connectivity test skipped because Amrod authentication failed.',
            ];


            return $result;
        }


        $result['checks']['authentication'] = [
            'status' => 'pass',

            'message' =>
                $authentication['message'],

            'cached' =>
                $authentication['cached'],
        ];


        /*
        |--------------------------------------------------------------------------
        | Vendor API Connectivity Check
        |--------------------------------------------------------------------------
        |
        | The Brands endpoint is read-only and is used only to verify
        | that the authenticated connector can communicate with Amrod.
        |
        */

        $api_result = $this->connector
            ->test_api_connection(
                self::HEALTH_ENDPOINT
            );


        if (
            ! $api_result['success']
        ) {
            $result['checks']['api'] = [
                'status' => 'fail',

                'message' =>
                    $api_result['message'],

                'records' => 0,
            ];


            return $result;
        }


        /*
        |--------------------------------------------------------------------------
        | Count Returned Records
        |--------------------------------------------------------------------------
        */

        $records = $this->count_records(
            $api_result['data']
        );


        $result['checks']['api'] = [
            'status' => 'pass',

            'message' =>
                sprintf(
                    'Amrod Vendor API responded successfully. Brands endpoint returned %d record(s).',
                    $records
                ),

            'records' => $records,
        ];


        /*
        |--------------------------------------------------------------------------
        | Overall Health
        |--------------------------------------------------------------------------
        */

        $result['status'] = 'healthy';


        return $result;
    }


    /**
     * Get the health check endpoint.
     *
     * @return string
     */
    public function get_endpoint(): string
    {
        return self::HEALTH_ENDPOINT;
    }


    /**
     * Count records in an Amrod API response.
     *
     * Amrod responses may return:
     *
     * - A direct indexed array.
     * - An array containing a data collection.
     * - An array containing a result collection.
     *
     * This method attempts to provide a useful record count
     * without making assumptions about the complete API schema.
     *
     * @param array $data Decoded API response.
     *
     * @return int
     */
    private function count_records(
        array $data
    ): int {
        /*
        |--------------------------------------------------------------------------
        | Direct Indexed Response
        |--------------------------------------------------------------------------
        */

        if (
            $this->is_indexed_array($data)
        ) {
            return count($data);
        }


        /*
        |--------------------------------------------------------------------------
        | Common Collection Containers
        |--------------------------------------------------------------------------
        */

        foreach (
            [
                'data',
                'Data',
                'result',
                'Result',
                'items',
                'Items',
                'brands',
                'Brands',
            ] as $key
        ) {
            if (
                isset($data[$key])
                && is_array($data[$key])
            ) {
                return count(
                    $data[$key]
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Unknown Valid Response
        |--------------------------------------------------------------------------
        |
        | The API returned valid JSON but its structure does not
        | expose a recognisable collection.
        |
        */

        return 0;
    }


    /**
     * Determine whether an array is indexed.
     *
     * @param array $array Array to inspect.
     *
     * @return bool
     */
    private function is_indexed_array(
        array $array
    ): bool {
        if (
            $array === []
        ) {
            return true;
        }


        return array_keys($array) === range(
            0,
            count($array) - 1
        );
    }
}