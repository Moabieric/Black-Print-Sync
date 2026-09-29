<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Suppliers\Amrod;

use BlackPrint\Commerce\Sync\Contracts\SupplierConnector;
use BlackPrint\Commerce\Sync\Contracts\SupportsProducts;
use BlackPrint\Commerce\Sync\DTO\SupplierMetadata;
use BlackPrint\Commerce\Sync\DTO\SupplierResponse;
use BlackPrint\Commerce\Sync\Kernel\JobContext;

final class AmrodConnector implements
    SupplierConnector,
    SupportsProducts
{
    public function __construct(
        private readonly Amrod_Product_Service $products
    ) {
    }

    public function supplier(): string
    {
        return 'amrod';
    }

    public function products(
        JobContext $context
    ): SupplierResponse {

        $started = microtime(true);

        $payload = $this->fetchProducts(
            $context
        );

        $durationMs = (int) round(
            (microtime(true) - $started) * 1000
        );

        return $this->buildResponse(
            resource: 'products',
            payload: $payload,
            durationMs: $durationMs,
            context: $context
        );
    }

    private function fetchProducts(
        JobContext $context
    ): array {

        return match ($context->jobType()) {

            /*
            |--------------------------------------------------------------------------
            | Full Product Snapshot
            |--------------------------------------------------------------------------
            */

            'manual',
            'daily' => $this->products->get_products(),


            /*
            |--------------------------------------------------------------------------
            | Incremental Product Snapshot
            |--------------------------------------------------------------------------
            */

            'scheduled' => $this->products->get_updated_products(),


            /*
            |--------------------------------------------------------------------------
            | Replay
            |--------------------------------------------------------------------------
            |
            | A replay must operate from an existing immutable snapshot.
            | It must never call the supplier API.
            |
            */

            'replay' => throw new \RuntimeException(
                'Replay jobs must use an existing snapshot and must not call the supplier API.'
            ),


            /*
            |--------------------------------------------------------------------------
            | Unknown Job Type
            |--------------------------------------------------------------------------
            */

            default => throw new \RuntimeException(
                sprintf(
                    'Unsupported Amrod products job type: %s',
                    $context->jobType()
                )
            ),

        };
    }

    private function buildResponse(
        string $resource,
        array $payload,
        int $durationMs,
        JobContext $context
    ): SupplierResponse {

        $json = wp_json_encode(
            $payload
        );

        if ($json === false) {
            throw new \RuntimeException(
                'Failed to encode supplier response payload.'
            );
        }

        return new SupplierResponse(

            payload: $payload,

            metadata: new SupplierMetadata(

                supplier: $this->supplier(),

                resource: $resource,

                recordCount: count($payload),

                checksum: hash(
                    'sha256',
                    $json
                ),

                payloadSize: strlen(
                    $json
                ),

                durationMs: $durationMs,

                requestedAt: gmdate('c'),

                extra: [

                    'job_type' => $context->jobType(),

                    'endpoint' => $this->endpointFor(
                        $context
                    ),

                ]

            )

        );
    }

    private function endpointFor(
        JobContext $context
    ): string {

        return match ($context->jobType()) {

            'scheduled' =>
                $this->products->get_updated_products_endpoint(),

            'manual',
            'daily' =>
                $this->products->get_products_endpoint(),

            'replay' =>
                'snapshot://existing',

            default => throw new \RuntimeException(
                sprintf(
                    'Unsupported Amrod products job type: %s',
                    $context->jobType()
                )
            ),

        };
    }
}