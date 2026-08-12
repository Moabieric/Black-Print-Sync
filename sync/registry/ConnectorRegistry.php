<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Sync\Registry;

use RuntimeException;
use BlackPrint\Commerce\Sync\Contracts\SupplierConnector;

final class ConnectorRegistry
{
    /**
     * @var array<string, SupplierConnector>
     */
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

        if (! isset($this->connectors[$supplier])) {

            throw new RuntimeException(

                sprintf(
                    'Unknown supplier: %s',
                    $supplier
                )

            );
        }

        return $this->connectors[$supplier];
    }

    public function has(
        string $supplier
    ): bool {

        return isset(
            $this->connectors[$supplier]
        );
    }

    /**
     * @return array<string, SupplierConnector>
     */
    public function all(): array
    {
        return $this->connectors;
    }
}
