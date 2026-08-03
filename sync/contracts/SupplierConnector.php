<?php

declare(strict_types=1);

namespace BlackPrint\Sync\Contracts;

interface SupplierConnector
{
    public function supplier(): string;
}