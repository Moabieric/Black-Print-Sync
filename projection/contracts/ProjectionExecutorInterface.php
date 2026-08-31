<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Projection\Contracts;

use BlackPrint\Commerce\Projection\DTO\ProjectionResult;

defined('ABSPATH') || exit;

/**
 * Contract for executing a channel-specific projection plan.
 *
 * An executor receives only a channel-specific projection plan produced
 * by a projector. It represents the mutation boundary between the
 * BlackPrint OS projection layer and the target sales channel.
 *
 * Executors must never receive supplier-specific payloads directly.
 */
interface ProjectionExecutorInterface
{
    /**
     * Execute a projection plan.
     *
     * The executor is responsible for determining and performing the
     * appropriate channel operation represented by the projection plan.
     *
     * @param array<string, mixed> $projection
     */
    public function execute(
        array $projection
    ): ProjectionResult;
}