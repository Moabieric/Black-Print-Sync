<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Sync\Contracts;

interface SupplierConnector
{
    public function supplier(): string;
}
