<?php

namespace BlackPrint\Commerce\Sync\Kernel;

class JobId
{
    public static function generate(): string
    {
        return wp_generate_uuid4();
    }
}