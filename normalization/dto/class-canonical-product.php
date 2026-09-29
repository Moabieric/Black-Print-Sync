<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Normalization\DTO;

defined('ABSPATH') || exit;

/**
 * Canonical Product.
 *
 * Supplier-agnostic representation of a product
 * inside BlackPrint OS.
 *
 * This DTO intentionally contains no WooCommerce
 * concepts such as:
 *
 * - post IDs
 * - product IDs
 * - variation IDs
 * - taxonomy IDs
 * - attachment IDs
 *
 * It also does not contain BlackPrint pricing
 * rules or channel-specific projection logic.
 */
final class CanonicalProduct
{
    /**
     * @param array<string, mixed> $identity
     * @param array<string, mixed> $hierarchy
     * @param array<string, mixed> $content
     * @param array<string, mixed> $classification
     * @param array<string, mixed> $variant
     * @param array<string, mixed> $commercial
     * @param array<string, mixed> $inventory
     * @param array<string, mixed> $media
     * @param array<string, mixed> $relationships
     * @param array<string, mixed> $branding
     * @param array<string, mixed> $provenance
     */
    public function __construct(
        private readonly array $identity,
        private readonly array $hierarchy,
        private readonly array $content,
        private readonly array $classification,
        private readonly array $variant,
        private readonly array $commercial,
        private readonly array $inventory,
        private readonly array $media,
        private readonly array $relationships,
        private readonly array $branding,
        private readonly array $provenance
    ) {
    }

    /**
     * Canonical identity.
     */
    public function identity(): array
    {
        return $this->identity;
    }

    /**
     * Product hierarchy.
     */
    public function hierarchy(): array
    {
        return $this->hierarchy;
    }

    /**
     * Product content.
     */
    public function content(): array
    {
        return $this->content;
    }

    /**
     * Product classification.
     */
    public function classification(): array
    {
        return $this->classification;
    }

    /**
     * Variant information.
     */
    public function variant(): array
    {
        return $this->variant;
    }

    /**
     * Commercial supplier data.
     */
    public function commercial(): array
    {
        return $this->commercial;
    }

    /**
     * Inventory supplier data.
     */
    public function inventory(): array
    {
        return $this->inventory;
    }

    /**
     * Product media.
     */
    public function media(): array
    {
        return $this->media;
    }

    /**
     * Product relationships.
     */
    public function relationships(): array
    {
        return $this->relationships;
    }

    /**
     * Branding data.
     */
    public function branding(): array
    {
        return $this->branding;
    }

    /**
     * Source provenance.
     */
    public function provenance(): array
    {
        return $this->provenance;
    }

    /**
     * Convert the canonical product to an array.
     *
     * This is useful for:
     *
     * - validation
     * - debugging
     * - testing
     * - future persistence
     * - future projection
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [

            'identity' =>
                $this->identity,

            'hierarchy' =>
                $this->hierarchy,

            'content' =>
                $this->content,

            'classification' =>
                $this->classification,

            'variant' =>
                $this->variant,

            'commercial' =>
                $this->commercial,

            'inventory' =>
                $this->inventory,

            'media' =>
                $this->media,

            'relationships' =>
                $this->relationships,

            'branding' =>
                $this->branding,

            'provenance' =>
                $this->provenance,

        ];
    }
}