<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Normalization\DTO;

defined('ABSPATH') || exit;

/**
 * Canonical Product Collection.
 *
 * Immutable collection of normalized canonical products.
 *
 * This collection does not:
 *
 * - Persist products.
 * - Modify products.
 * - Apply business rules.
 * - Project products to WooCommerce.
 */
final class CanonicalProductCollection
{
    /**
     * @var array<int, CanonicalProduct>
     */
    private array $products;

    /**
     * @param array<int, CanonicalProduct> $products
     */
    public function __construct(
        array $products = []
    ) {
        foreach ($products as $product) {

            if (! $product instanceof CanonicalProduct) {

                throw new \InvalidArgumentException(
                    'CanonicalProductCollection accepts only CanonicalProduct instances.'
                );
            }
        }

        $this->products = array_values(
            $products
        );
    }

    /**
     * Return all canonical products.
     *
     * @return array<int, CanonicalProduct>
     */
    public function all(): array
    {
        return $this->products;
    }

    /**
     * Return the number of products.
     */
    public function count(): int
    {
        return count(
            $this->products
        );
    }

    /**
     * Determine whether the collection is empty.
     */
    public function isEmpty(): bool
    {
        return $this->products === [];
    }

    /**
     * Return the canonical product at an index.
     */
    public function get(
        int $index
    ): ?CanonicalProduct {

        return $this->products[$index]
            ?? null;
    }

    /**
     * Convert the collection to an array.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (
                CanonicalProduct $product
            ): array => $product->toArray(),
            $this->products
        );
    }
}
