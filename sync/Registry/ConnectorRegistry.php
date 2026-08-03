<?php

declare(strict_types=1);

namespace BlackPrint\Sync\Registry;

use RuntimeException;
use BlackPrint\Sync\Contracts\SupplierConnector;

final class ConnectorRegistry
{
    private array $connectors = [];

    public function register(
        SupplierConnector $connector
    ): void {

        $this->connectors[
            $connector->supplier()
        ] = $connector;
    }

    public function get(
        string $supplier
    ): SupplierConnector {

        if (! isset(
            $this->connectors[$supplier]
        )) {

            throw new RuntimeException(

                sprintf(
                    'Unknown supplier: %s',
                    $supplier
                )

            );
        }

        return $this->connectors[$supplier];
    }
}