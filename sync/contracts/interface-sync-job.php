<?php

namespace BlackPrint\Commerce\Sync\Contracts;

use BlackPrint\Commerce\Sync\Kernel\JobContext;
use BlackPrint\Commerce\Sync\Kernel\SyncResult;

interface SyncJobInterface
{
    /**
     * Supplier code.
     */
    public function supplier(): string;

    /**
     * Resource name.
     */
    public function resource(): string;

    /**
     * Execute the sync.
     */
    public function execute(
        JobContext $context
    ): SyncResult;
}