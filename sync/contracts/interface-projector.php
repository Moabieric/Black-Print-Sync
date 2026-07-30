<?php

namespace BlackPrint\Commerce\Sync\Contracts;

interface ProjectorInterface
{
    /**
     * Project canonical entities to target.
     */
    public function project(array $entities): bool;
}