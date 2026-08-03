<?php

declare(strict_types=1);

namespace BlackPrint\Sync;

use Throwable;
use BlackPrint\Sync\Registry\StageRegistry;
use BlackPrint\Sync\Registry\ConnectorRegistry;

final class SyncPipeline
{
    public function __construct(

        private readonly SyncManager $syncManager,

        private readonly StageRegistry $stages,

        private readonly ConnectorRegistry $connectors,

        private readonly SnapshotRepository $snapshots,

        private readonly SnapshotPayloadRepository $payloads

    ) {
    }

    public function execute(
        JobContext $context
    ): SyncResult {

        $job = $this->syncManager->start(

            supplier: $context->supplier(),

            resource: $context->resource(),

            type: $context->jobType()

        );

        try {

            $connector = $this->connectors->get(

                $context->supplier()

            );

            $stage = $this->stages->get(

                $context->resource()

            );

            $response = $stage->fetch(

                $connector,

                $context

            );

            $metadata = $response->metadata();

            $snapshot = Snapshot::create(

                jobUuid: $job->uuid(),

                supplier: $metadata->supplier(),

                resource: $metadata->resource(),

                recordCount: $metadata->recordCount(),

                checksum: $metadata->checksum(),

                payloadSize: $metadata->payloadSize(),

                durationMs: $metadata->durationMs(),

                requestedAt: $metadata->requestedAt()

            );

            $this->snapshots->save(

                $snapshot

            );

            $this->payloads->save(

                $snapshot->uuid(),

                $response->payload()

            );

            $this->syncManager->complete(

                $job->uuid(),

                $snapshot->uuid()

            );

            return SyncResult::success(

                $job->uuid(),

                $snapshot->uuid()

            );

        } catch (Throwable $e) {

            $this->syncManager->fail(

                $job->uuid(),

                $e->getMessage()

            );

            return SyncResult::failure(

                $job->uuid(),

                $e->getMessage()

            );
        }
    }
}