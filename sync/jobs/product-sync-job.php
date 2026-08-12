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
    private readonly \BlackPrint\Commerce\Sync\Registry\ConnectorRegistry $connectors,
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
         * Resolve the supplier connector through the registry.
         */
        $connector = $this->connectors->get(
            $context->supplier()
        );

        /*
         * Ingest supplier data through the canonical stage.
         */
        $response = $this->stage->fetch(
            $connector,
            $context
        );

        $metadata = $response->metadata();

        /*
         * Create immutable snapshot metadata.
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
         * Persist snapshot metadata first.
         */
        $this->snapshots->create(
            $snapshot
        );

        /*
         * Persist the raw supplier payload separately.
         */
        $this->payloads->save(

            $snapshot->id(),

            $response->payload()

        );

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

            ]

        );
    }

    private function snapshotType(
        JobContext $context
    ): string {

        return match ($context->jobType()) {

            'daily' => SnapshotType::FULL,

            'scheduled' => SnapshotType::INCREMENTAL,

            'replay' => SnapshotType::REPLAY,

            default => SnapshotType::MANUAL,

        };
    }
}