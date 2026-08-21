<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Normalization\Contracts;

use BlackPrint\Commerce\Normalization\DTO\CanonicalProduct;

defined('ABSPATH') || exit;

/**
 * Canonical Normalizer Contract.
 *
 * Converts one raw supplier record into one
 * supplier-agnostic BlackPrint Canonical Product.
 *
 * A normalizer:
 *
 * - Does not call supplier APIs.
 * - Does not modify immutable snapshots.
 * - Does not write to WooCommerce.
 * - Does not apply BlackPrint business rules.
 * - Does not persist canonical products.
 *
 * Its sole responsibility is transformation:
 *
 * Raw Supplier Record
 *      ↓
 * Canonical Product
 */
interface CanonicalNormalizer
{
    /**
     * Return the supplier identifier supported
     * by this normalizer.
     */
    public function supplier(): string;

    /**
     * Return the resource supported by this
     * normalizer.
     *
     * Example:
     *
     * products
     */
    public function resource(): string;

    /**
     * Normalize one raw supplier record.
     *
     * @param array $record
     *
     * @return CanonicalProduct
     */
    public function normalize(
        array $record
    ): CanonicalProduct;
}