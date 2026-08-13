<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Sync\Jobs;

use BlackPrint\Commerce\Sync\Contracts\SyncJobInterface;
use BlackPrint\Commerce\Sync\Entities\Snapshot;
use BlackPrint\Commerce\Sync\Entities\SnapshotType;
use BlackPrint\Commerce\Sync\Kernel\JobContext;
use BlackPrint\Commerce\Sync\Kernel\SyncResult;
use BlackPrint\Commerce\Sync\Registry\ConnectorRegistry;
use BlackPrint\Commerce\Sync\Repositories\SnapshotPayloadRepository;
use BlackPrint\Commerce\Sync\Repositories\SnapshotRepository;
use BlackPrint\Commerce\Sync\Stages\ProductsStage;

final class ProductSyncJob implements SyncJobInterface
{
    public function __construct(
        private readonly ProductsStage $stage,
        private readonly ConnectorRegistry $connectors,
        private readonly SnapshotRepository $snapshots,
        private readonly SnapshotPayloadRepository $payloads
    ) {
    }

    public function supplier(): string
    {
        return 'amrod';
    }

    public function resource(): string
    {
        return 'products';
    }

    public function execute(
        JobContext $context
    ): SyncResult {

        /*
        |--------------------------------------------------------------------------
        | Replay Protection
        |--------------------------------------------------------------------------
        |
        | Replay is intentionally not part of the supplier ingestion path.
        |
        | A future replay implementation must load an existing immutable
        | snapshot and process that payload without contacting the supplier.
        |
        */

        if ($context->jobType() === 'replay') {
            throw new \RuntimeException(
                'Product replay is not implemented in the supplier ingestion job.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Resolve Supplier Connector
        |--------------------------------------------------------------------------
        */

        $connector = $this->connectors->get(
            $context->supplier()
        );


        /*
        |--------------------------------------------------------------------------
        | Fetch Raw Supplier Data
        |--------------------------------------------------------------------------
        */

        $response = $this->stage->fetch(
            $connector,
            $context
        );

        $metadata = $response->metadata();


        /*
        |--------------------------------------------------------------------------
        | Create Immutable Snapshot
        |--------------------------------------------------------------------------
        */

        $snapshot = new Snapshot(

            id: wp_generate_uuid4(),

            jobId: $context->jobId(),

            sequenceNumber: 1,

            supplier: $metadata->supplier(),

            resource: $metadata->resource(),

            type: $this->snapshotType(
                $context
            ),

            checksum: $metadata->checksum(),

            recordCount: $metadata->recordCount(),

            metadata: [

                'duration_ms' => $metadata->durationMs(),

                'payload_size' => $metadata->payloadSize(),

                'requested_at' => $metadata->requestedAt(),

                'etag' => $metadata->etag(),

                'cursor' => $metadata->cursor(),

                'extra' => $metadata->extra(),

            ]

        );


        /*
        |--------------------------------------------------------------------------
        | Persist Snapshot Metadata
        |--------------------------------------------------------------------------
        */

        $this->snapshots->create(
            $snapshot
        );


        /*
        |--------------------------------------------------------------------------
        | Persist Immutable Raw Payload
        |--------------------------------------------------------------------------
        */

        $this->payloads->save(

            $snapshot->id(),

            $response->payload()

        );


        /*
        |--------------------------------------------------------------------------
        | Return Result
        |--------------------------------------------------------------------------
        */

        return new SyncResult(

            success: true,

            fetched: $metadata->recordCount(),

            processed: $metadata->recordCount(),

            snapshotId: $snapshot->id(),

            metadata: [

                'snapshot_uuid' => $snapshot->id(),

                'checksum' => $metadata->checksum(),

                'supplier' => $metadata->supplier(),

                'resource' => $metadata->resource(),

                'snapshot_type' => $snapshot->type(),

            ]

        );
    }

    private function snapshotType(
    JobContext $context
): string {

    return match ($context->jobType()) {

        'daily' =>
            SnapshotType::FULL,

        'scheduled' =>
            SnapshotType::INCREMENTAL,

        'manual' =>
            SnapshotType::MANUAL,

        default => throw new \RuntimeException(
            sprintf(
                'Unsupported product snapshot job type: %s',
                $context->jobType()
            )
        ),

    };
}