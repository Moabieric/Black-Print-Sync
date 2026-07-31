<?php

namespace BlackPrint\Commerce\Sync\Entities;

defined('ABSPATH') || exit;

final class SyncJob
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $supplier,
        public readonly string $resource,
        public readonly string $jobType,
        public readonly string $status,
        public readonly int $recordsProcessed = 0,
        public readonly ?string $startedAt = null,
        public readonly ?string $completedAt = null,
        public readonly ?string $errorMessage = null
    ) {
    }
}