<?php

declare(strict_types=1);

namespace BlackPrint\Sync\Contracts;

use BlackPrint\Sync\JobContext;
use BlackPrint\Sync\ValueObjects\SupplierResponse;

interface SupportsProducts
{
    public function products(
        JobContext $context
    ): SupplierResponse;
}