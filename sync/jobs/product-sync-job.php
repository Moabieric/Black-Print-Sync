<?php

namespace BlackPrint\Commerce\Sync\Jobs;

use BlackPrint\Commerce\Sync\Contracts\SyncJobInterface;
use BlackPrint\Commerce\Sync\Kernel\JobContext;
use BlackPrint\Commerce\Sync\Kernel\SyncResult;

class ProductSyncJob implements SyncJobInterface
{
    public function name(): string
    {
        return 'products';
    }

    public function supplier(): string
    {
        return 'amrod';
    }

    public function schedule(): string
    {
        return 'daily';
    }

    public function execute(JobContext $context): SyncResult
    {
        return new SyncResult(
            success: true,
            metadata: [
                'message' => 'Product sync placeholder.'
            ]
        );
    }
}