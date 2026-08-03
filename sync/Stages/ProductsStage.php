<?php

declare(strict_types=1);

namespace BlackPrint\Sync\Stages;

use RuntimeException;
use BlackPrint\Sync\JobContext;
use BlackPrint\Sync\Contracts\IngestionStage;
use BlackPrint\Sync\Contracts\SupportsProducts;
use BlackPrint\Sync\Contracts\SupplierConnector;
use BlackPrint\Sync\ValueObjects\SupplierResponse;

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