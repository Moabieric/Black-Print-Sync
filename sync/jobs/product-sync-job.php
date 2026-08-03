<?php

namespace BlackPrint\Commerce\Sync\Jobs;

use BlackPrint\Commerce\Sync\Contracts\SyncJobInterface;
use BlackPrint\Commerce\Sync\Kernel\JobContext;
use BlackPrint\Commerce\Sync\Kernel\SyncResult;
use BlackPrint\Commerce\Sync\Stages\ProductsStage;
use BlackPrint\Commerce\Sync\Repositories\SnapshotRepository;
use BlackPrint\Commerce\Sync\Storage\SnapshotPayloadRepository;

final class ProductSyncJob implements SyncJobInterface
{
    public function __construct(

        private ProductsStage $stage,

        private SnapshotRepository $snapshots,

        private SnapshotPayloadRepository $payloads

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

        $response = $this->stage->fetch($context);

        $metadata = $response->metadata();

        // Snapshot creation goes here

        public function execute(
    JobContext $context
): SyncResult {

    $response = $this->stage->fetch($context);

    $meta = $response->metadata();

    $snapshot = new Snapshot(

        id: wp_generate_uuid4(),

        jobId: $context->jobId(),

        sequenceNumber: 1,

        supplier: $meta->supplier(),

        resource: $meta->resource(),

        type: SnapshotType::MANUAL,

        checksum: $meta->checksum(),

        recordCount: $meta->recordCount(),

        metadata: [

            'duration_ms' => $meta->durationMs(),

            'payload_size' => $meta->payloadSize(),

            'requested_at' => $meta->requestedAt(),

        ]

    );

    $this->snapshots->create($snapshot);

    $this->payloads->save(

        $snapshot->id(),

        $response->payload()

    );

    return new SyncResult(

        success: true,

        processed: $meta->recordCount(),

        metadata: [

            'snapshot_uuid' => $snapshot->id(),

            'checksum' => $meta->checksum(),

        ]

    );
}
}