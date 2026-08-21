<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Normalization\Registry;

use BlackPrint\Commerce\Normalization\Contracts\CanonicalNormalizer;
use InvalidArgumentException;

defined('ABSPATH') || exit;

/**
 * Canonical Normalizer Registry.
 *
 * Registers and resolves supplier-specific canonical
 * normalizers.
 *
 * Resolution is based on:
 *
 * supplier
 *      +
 * resource
 *
 * Example:
 *
 * amrod + products
 *      ↓
 * AmrodProductsNormalizer
 */
final class CanonicalNormalizerRegistry
{
    /**
     * Registered normalizers.
     *
     * @var array<string, array<string, CanonicalNormalizer>>
     */
    private array $normalizers = [];

    /**
     * Register a canonical normalizer.
     */
    public function register(
        CanonicalNormalizer $normalizer
    ): void {

        $this->normalizers[
            $normalizer->supplier()
        ][
            $normalizer->resource()
        ] = $normalizer;
    }

    /**
     * Resolve a canonical normalizer.
     *
     * @throws InvalidArgumentException
     */
    public function resolve(
        string $supplier,
        string $resource
    ): CanonicalNormalizer {

        if (
            ! isset(
                $this->normalizers[
                    $supplier
                ][
                    $resource
                ]
            )
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'No canonical normalizer is registered for [%s:%s].',
                    $supplier,
                    $resource
                )
            );
        }

        return $this->normalizers[
            $supplier
        ][
            $resource
        ];
    }

    /**
     * Determine whether a normalizer is registered.
     */
    public function has(
        string $supplier,
        string $resource
    ): bool {

        return isset(
            $this->normalizers[
                $supplier
            ][
                $resource
            ]
        );
    }

    /**
     * Return all registered normalizers.
     *
     * @return array<string, array<string, CanonicalNormalizer>>
     */
    public function all(): array
    {
        return $this->normalizers;
    }
}
