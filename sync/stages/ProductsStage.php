<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Sync\Stages;

use RuntimeException;
use BlackPrint\Commerce\Sync\Kernel\JobContext;
use BlackPrint\Commerce\Sync\Contracts\IngestionStage;
use BlackPrint\Commerce\Sync\Contracts\SupportsProducts;
use BlackPrint\Commerce\Sync\Contracts\SupplierConnector;
use BlackPrint\Commerce\Sync\DTO\SupplierResponse;

final class ProductsStage implements IngestionStage
{
    public function resource(): string
    {
        return 'products';
    }

    public function fetch(
        SupplierConnector $connector,
        JobContext $context
    ): SupplierResponse {

        if (! $connector instanceof SupportsProducts) {

            throw new RuntimeException(

                sprintf(
                    '%s does not support products',
                    $connector->supplier()
                )

            );
        }

        return $connector->products(
            $context
        );
    }
}
