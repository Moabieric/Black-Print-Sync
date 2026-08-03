<?php

declare(strict_types=1);

namespace BlackPrint\Suppliers\Amrod;

use BlackPrint\Sync\Contracts\SupplierConnector;
use BlackPrint\Sync\Contracts\SupportsProducts;
use BlackPrint\Sync\JobContext;
use BlackPrint\Sync\ValueObjects\SupplierMetadata;
use BlackPrint\Sync\ValueObjects\SupplierResponse;

final class AmrodConnector implements
    SupplierConnector,
    SupportsProducts
{
    public function __construct(
        private readonly AmrodHttpClient $client
    ) {
    }

    public function supplier(): string
    {
        return 'amrod';
    }

    public function products(
        JobContext $context
    ): SupplierResponse {

        $http = $this->client->get(
            '/products'
        );

        return $this->buildResponse(
            resource: 'products',
            response: $http
        );
    }

    private function buildResponse(
        string $resource,
        \BlackPrint\Suppliers\Http\HttpResponse $response
    ): SupplierResponse {

        $payload = $response->body();

        $json = wp_json_encode($payload);

        return new SupplierResponse(

            payload: $payload,

            metadata: new SupplierMetadata(

                supplier: $this->supplier(),

                resource: $resource,

                recordCount: count($payload),

                checksum: hash('sha256', $json),

                payloadSize: strlen($json),

                durationMs: $response->durationMs(),

                requestedAt: gmdate('c'),

                etag: $response->header('etag'),

                extra: [

                    'http_status' => $response->status(),

                ]

            )

        );
    }
}