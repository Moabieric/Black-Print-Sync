<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Sync\Contracts;

use BlackPrint\Commerce\Sync\Kernel\JobContext;
use BlackPrint\Commerce\Sync\DTO\SupplierResponse;

interface IngestionStage
{
    public function resource(): string;

    public function fetch(
        SupplierConnector $connector,
        JobContext $context
    ): SupplierResponse;
}
