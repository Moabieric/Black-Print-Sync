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
        string $jobName,
        array $metadata = []
    ): SyncResult {

        $context = new JobContext(
            jobId: uniqid('job_', true),
            jobName: $jobName,
            supplier: $supplier,
            attempt: 1,
            metadata: $metadata
        );

        return $this->runner->run($context);
    }
}