<?php

namespace BlackPrint\Commerce\Sync\Contracts;

use BlackPrint\Commerce\Sync\Kernel\JobContext;
use BlackPrint\Commerce\Sync\Kernel\SyncResult;

interface SyncJobInterface
{
    /**
     * Unique job name.
     */
    public function name(): string;

    /**
     * Supplier code.
     */
    public function supplier(): string;

    /**
     * Cron schedule.
     */
    public function schedule(): string;

    /**
     * Execute the sync.
     */
    public function execute(JobContext $context): SyncResult;
}