<?php

namespace BlackPrint\Commerce\Sync\Services;

use Closure;
use BlackPrint\Commerce\Sync\Kernel\SyncResult;

interface SyncPipelineInterface
{
    public function run(
        string $supplier,
        string $resource,
        string $jobType,
        Closure $callback,
        array $metadata = []
    ): SyncResult;
}