<?php

namespace BlackPrint\Commerce\Sync\Jobs;

use BlackPrint\Commerce\Sync\Contracts\SyncJobInterface;
use BlackPrint\Commerce\Sync\Kernel\JobContext;
use BlackPrint\Commerce\Sync\Kernel\SyncResult;

class BrandingSyncJob implements SyncJobInterface
{
    public function supplier(): string
    {
        return 'amrod';
    }

    public function resource(): string
    {
        return 'branding';
    }

    public function execute(
        JobContext $context
    ): SyncResult {

        return new SyncResult(

            success: true,

            processed: 0,
            skipped: 0,
            failed: 0,
            duration: 0.0,
            errors: [],
            metadata: [

                'message' => 'Branding sync placeholder.',

            ]

        );
    }
}