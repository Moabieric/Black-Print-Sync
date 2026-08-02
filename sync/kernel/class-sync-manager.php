<?php

namespace BlackPrint\Commerce\Sync\Kernel;

class SyncManager
{
    public function __construct(
        private JobRunner $runner
    ) {
    }

    /**
     * Dispatch a sync job.
     */
    public function dispatch(
    string $supplier,
    string $resource,
    array $metadata = []
): SyncResult {

    $context = new JobContext(

        jobId: uniqid('job_', true),

        supplier: $supplier,

        resource: $resource,

        jobType: $metadata['job_type'] ?? 'manual',

        attempt: 1,

        snapshotId: null,

        metadata: $metadata

    );

    return $this->runner->run($context);
}
}