<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Sync\Kernel;

use BlackPrint\Commerce\Sync\Entities\SyncJob;
use BlackPrint\Commerce\Sync\Repositories\SyncJobRepository;
use BlackPrint\Commerce\Sync\Replay\SnapshotIntegrityVerifier;

defined('ABSPATH') || exit;

final class SyncManager
{
    public function __construct(
        private readonly JobRunner $runner,
        private readonly SyncJobRepository $jobs,
        private readonly SnapshotIntegrityVerifier $integrityVerifier
    ) {
    }

    /**
     * Dispatch a synchronization job.
     */
    public function dispatch(
        string $supplier,
        string $resource,
        array $metadata = []
    ): SyncResult {

        $jobId = wp_generate_uuid4();

        $jobType = $metadata['job_type'] ?? 'manual';

        /*
        |--------------------------------------------------------------------------
        | Create Job Record
        |--------------------------------------------------------------------------
        */

        $job = new SyncJob(

            uuid: $jobId,

            supplier: $supplier,

            resource: $resource,

            jobType: $jobType,

            status: 'pending'

        );

        $this->jobs->create(
            $job
        );


        /*
        |--------------------------------------------------------------------------
        | Create Runtime Context
        |--------------------------------------------------------------------------
        */

        $context = new JobContext(

            jobId: $jobId,

            supplier: $supplier,

            resource: $resource,

            jobType: $jobType,

            attempt: 1,

            snapshotId: null,

            metadata: $metadata

        );


        /*
        |--------------------------------------------------------------------------
        | Execute Job
        |--------------------------------------------------------------------------
        */

            return $this->runner->run(
                $context
        );
    }

    /**
     * Verify the integrity of an immutable snapshot.
     *
     * This is read-only and does not modify the snapshot,
     * payload, supplier data, or WooCommerce.
     */
    public function verifySnapshot(
        string $snapshotUuid
    ): array {

        return $this->integrityVerifier->verify(
            $snapshotUuid
        );
    }
}
