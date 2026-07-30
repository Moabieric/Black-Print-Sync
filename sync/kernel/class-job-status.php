<?php

namespace BlackPrint\Commerce\Sync\Kernel;

class JobStatus
{
    public const PENDING = 'pending';

    public const RUNNING = 'running';

    public const COMPLETED = 'completed';

    public const FAILED = 'failed';

    public const CANCELLED = 'cancelled';
}