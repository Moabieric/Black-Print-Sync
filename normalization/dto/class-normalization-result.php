<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Normalization\DTO;

defined('ABSPATH') || exit;

/**
 * Normalization Result.
 *
 * Represents the result of transforming raw supplier
 * records from an immutable snapshot into canonical
 * BlackPrint products.
 *
 * This result does not imply persistence.
 *
 * A successful normalization means only that the
 * transformation completed successfully.
 */
final class NormalizationResult
{
    /**
     * @param array<int, string> $errors
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly bool $success,
        private readonly int $sourceRecords,
        private readonly int $normalized,
        private readonly int $skipped = 0,
        private readonly int $failed = 0,
        private readonly ?CanonicalProductCollection $products = null,
        private readonly array $errors = [],
        private readonly array $metadata = []
    ) {
    }

    /**
     * Determine whether normalization succeeded.
     */
    public function success(): bool
    {
        return $this->success;
    }

    /**
     * Number of source records received.
     */
    public function sourceRecords(): int
    {
        return $this->sourceRecords;
    }

    /**
     * Number of successfully normalized records.
     */
    public function normalized(): int
    {
        return $this->normalized;
    }

    /**
     * Number of skipped records.
     */
    public function skipped(): int
    {
        return $this->skipped;
    }

    /**
     * Number of failed records.
     */
    public function failed(): int
    {
        return $this->failed;
    }

    /**
     * Return normalized canonical products.
     */
    public function products(): ?CanonicalProductCollection
    {
        return $this->products;
    }

    /**
     * Return normalization errors.
     *
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Determine whether errors occurred.
     */
    public function hasErrors(): bool
    {
        return ! empty(
            $this->errors
        );
    }

    /**
     * Return operation metadata.
     *
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    /**
     * Return the result as an array.
     *
     * This is intended for:
     *
     * - diagnostics
     * - debugging
     * - testing
     * - future admin reporting
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [

            'success' =>
                $this->success,

            'source_records' =>
                $this->sourceRecords,

            'normalized' =>
                $this->normalized,

            'skipped' =>
                $this->skipped,

            'failed' =>
                $this->failed,

            'products' =>
                $this->products
                    ? $this->products->toArray()
                    : [],

            'errors' =>
                $this->errors,

            'metadata' =>
                $this->metadata,

        ];
    }
}