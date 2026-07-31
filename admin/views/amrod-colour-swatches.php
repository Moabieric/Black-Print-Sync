<?php

namespace BlackPrint\Commerce\Suppliers\Amrod;

defined('ABSPATH') || exit;

/**
 * Amrod Colour Swatch Service.
 *
 * Responsible for retrieving colour swatches
 * from the Amrod API.
 */
class Amrod_Colour_Swatch_Service
{
    /**
     * API connector.
     *
     * @var Amrod_Connector
     */
    private Amrod_Connector $connector;

    /**
     * Constructor.
     *
     * @param Amrod_Connector $connector
     */
    public function __construct(
        Amrod_Connector $connector
    ) {
        $this->connector = $connector;
    }

    /**
     * Get all colour swatches.
     *
     * @return array
     */
    public function all(): array
    {
        $response = $this->connector->get(
            '/colourswatches'
        );

        if (is_wp_error($response)) {
            return [];
        }

        return is_array($response)
            ? $response
            : [];
    }

    /**
     * Find a swatch by code.
     *
     * @param string $code
     *
     * @return array|null
     */
    public function find(
        string $code
    ): ?array {

        foreach ($this->all() as $swatch) {

            if (
                isset($swatch['code']) &&
                $swatch['code'] === $code
            ) {
                return $swatch;
            }
        }

        return null;
    }

    /**
     * Check whether a swatch exists.
     *
     * @param string $code
     *
     * @return bool
     */
    public function exists(
        string $code
    ): bool {

        return $this->find($code) !== null;
    }

    /**
     * Get the total number of swatches.
     *
     * @return int
     */
    public function count(): int
    {
        return count(
            $this->all()
        );
    }
}