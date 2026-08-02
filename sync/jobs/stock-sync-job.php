<?php

namespace BlackPrint\Commerce\Sync\Jobs;

use BlackPrint\Commerce\Sync\Contracts\SyncJobInterface;
use BlackPrint\Commerce\Sync\Kernel\JobContext;
use BlackPrint\Commerce\Sync\Kernel\SyncResult;

class StockSyncJob implements SyncJobInterface
{
    public function supplier(): string
    {
        return 'amrod';
    }

    public function resource(): string
    {
        return 'stock';
    }

    public function execute(
        JobContext $context
    ): SyncResult {

        return new SyncResult(

            success: true,

            metadata: [

                'message' => 'Stock sync placeholder.',

            ]

        );
    }
}