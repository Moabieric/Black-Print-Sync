<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Projection\Contracts;

use BlackPrint\Commerce\Projection\DTO\ProjectionResult;

defined('ABSPATH') || exit;

/**
 * Contract for projecting a canonical product into a sales channel.
 *
 * A projector receives only the canonical representation of a product.
 * It must not depend on supplier-specific payloads or normalization
 * internals.
 */
interface ProductProjectorInterface
{
    /**
     * Project a canonical product into the target channel.
     *
     * @param array<string, mixed> $product
     */
    public function project(
        array $product
    ): ProjectionResult;
}