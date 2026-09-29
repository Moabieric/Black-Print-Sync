<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Projection\WooCommerce;

use BlackPrint\Commerce\Projection\Contracts\ProductProjectorInterface;
use BlackPrint\Commerce\Projection\DTO\ProjectionResult;

defined('ABSPATH') || exit;

/**
 * Builds a deterministic WooCommerce projection plan from a canonical
 * BlackPrint product.
 *
 * This projector is intentionally read-only. It translates the canonical
 * product into a channel-specific representation without creating or
 * modifying WooCommerce records.
 */
final class WooCommerceProductProjector implements ProductProjectorInterface
{
    /**
     * Build a WooCommerce projection plan.
     *
     * @param array<string, mixed> $product
     */
    public function project(
        array $product
    ): ProjectionResult {
        $identity        = $product['identity'] ?? [];
        $hierarchy       = $product['hierarchy'] ?? [];
        $content         = $product['content'] ?? [];
        $classification  = $product['classification'] ?? [];
        $variant         = $product['variant'] ?? [];
        $commercial      = $product['commercial'] ?? [];
        $inventory       = $product['inventory'] ?? [];
        $media           = $product['media'] ?? [];
        $relationships   = $product['relationships'] ?? [];
        $branding        = $product['branding'] ?? [];
        $provenance      = $product['provenance'] ?? [];

        $supplierProductId = $identity['supplier_product_id'] ?? '';
        $supplierProductCode = $identity['supplier_product_code'] ?? '';

        if (
            ! is_string($supplierProductId)
            || $supplierProductId === ''
        ) {
            return ProjectionResult::failed(
                'Canonical product is missing supplier_product_id.'
            );
        }

        if (
            ! is_string($supplierProductCode)
            || $supplierProductCode === ''
        ) {
            return ProjectionResult::failed(
                'Canonical product is missing supplier_product_code.'
            );
        }

        $variants = $variant['items'] ?? [];

        if (! is_array($variants)) {
            return ProjectionResult::failed(
                'Canonical product variant. items must be an array.'
            );
        }

        if ($variants === []) {
            return ProjectionResult::failed(
                'Canonical product must contain at least one variant.'
            );
        }

        $supplier = $provenance['supplier'] ?? null;

        if (
            ! is_string($supplier)
            || $supplier === ''
        ) {
            return ProjectionResult::failed(
                'Canonical product is missing supplier provenance.'
            );
        }

        $projection = [
            'channel' => 'woocommerce',

            'parent' => [
                /*
                |--------------------------------------------------------------------------
                | Structural Representation
                |--------------------------------------------------------------------------
                |
                | Every canonical product is represented by a variable parent.
                | Every canonical variant is represented by a sellable child
                | variation, regardless of the current number of variants.
                |
                */
                'type' => 'variable',

                /*
                |--------------------------------------------------------------------------
                | Identity
                |--------------------------------------------------------------------------
                */
                'identity' => [
                    'supplier' => $supplier,
                    'supplier_product_id' => $supplierProductId,
                    'supplier_product_code' => $supplierProductCode,
                ],

                /*
                |--------------------------------------------------------------------------
                | Content
                |--------------------------------------------------------------------------
                */
                'content' => [
                    'name' => $this->stringValue(
                        $content['name'] ?? ''
                    ),
                    'description' => $this->stringValue(
                        $content['description'] ?? ''
                    ),
                    'keywords' => $content['keywords'] ?? null,
                    'tags' => $content['tags'] ?? null,
                ],

                /*
                |--------------------------------------------------------------------------
                | Canonical Classification
                |--------------------------------------------------------------------------
                |
                | These remain canonical representations. A WooCommerce writer
                | or classification resolver will later map them to product_cat
                | terms without changing the canonical model.
                |
                */
                'classification' => [
                    'categories' => $classification['categories'] ?? [],
                    'brand' => $classification['brand'] ?? null,
                    'attributes' => $classification['attributes'] ?? [],
                ],

                /*
                |--------------------------------------------------------------------------
                | Hierarchy
                |--------------------------------------------------------------------------
                */
                'hierarchy' => [
                    'type' => $hierarchy['type'] ?? null,
                    'decoupled' => (bool) (
                        $hierarchy['decoupled'] ?? false
                    ),
                ],

                /*
                |--------------------------------------------------------------------------
                | Commercial & Inventory
                |--------------------------------------------------------------------------
                |
                | These values are carried into the projection plan but are not
                | yet interpreted as WooCommerce prices or stock quantities.
                |
                */
                'commercial' => $commercial,
                'inventory' => $inventory,

                /*
                |--------------------------------------------------------------------------
                | Media
                |--------------------------------------------------------------------------
                */
                'media' => [
                    'images' => $media['images'] ?? [],
                    'colour_images' => $media['colour_images'] ?? [],
                    'videos' => $media['videos'] ?? [],
                ],

                /*
                |--------------------------------------------------------------------------
                | Relationships
                |--------------------------------------------------------------------------
                */
                'relationships' => $relationships,

                /*
                |--------------------------------------------------------------------------
                | Branding
                |--------------------------------------------------------------------------
                |
                | Branding remains BlackPrint configuration. The projection
                | layer carries it forward without introducing supplier logic.
                |
                */
                'branding' => $branding,

                /*
                |--------------------------------------------------------------------------
                | Projection Ownership
                |--------------------------------------------------------------------------
                */
                'meta' => [
                    '_blackprint_managed' => 'yes',
                    '_blackprint_supplier' => $supplier,
                    '_blackprint_product_id' => $supplierProductId,
                    '_blackprint_product_code' => $supplierProductCode,
                ],
            ],

            'variants' => [],
        ];

        foreach ($variants as $item) {
            if (! is_array($item)) {
                return ProjectionResult::failed(
                'Canonical variant item must be an array.'
                );
            }

            $simpleCode = $item['simpleCode'] ?? '';
            $fullCode = $item['fullCode'] ?? '';

            if (
                ! is_string($simpleCode)
                || $simpleCode === ''
            ) {
                return ProjectionResult::failed(
                    'Canonical variant is missing simpleCode.'
                );
            }

            if (
                ! is_string($fullCode)
                || $fullCode === ''
            ) {
                return ProjectionResult::failed(
                    'Canonical variant is missing fullCode.'
                );
            }

            $attributes = [];

            $colourName = $item['codeColourName'] ?? '';
            $sizeName = $item['codeSizeName'] ?? '';

            if (
                is_string($colourName)
                && $colourName !== ''
            ) {
                $attributes['Colour'] = $colourName;
            }

            if (
                is_string($sizeName)
                && $sizeName !== ''
            ) {
                $attributes['Size'] = $sizeName;
            }

            $projection['variants'][] = [
                /*
                |--------------------------------------------------------------------------
                | Structural Representation
                |--------------------------------------------------------------------------
                */
                'type' => 'variation',

                /*
                |--------------------------------------------------------------------------
                | Identity
                |--------------------------------------------------------------------------
                */
                'identity' => [
                    'supplier' => $supplier,
                    'simple_code' => $simpleCode,
                    'full_code' => $fullCode,
                ],

                /*
                |--------------------------------------------------------------------------
                | Commerce Identity
                |--------------------------------------------------------------------------
                */
                'sku' => $fullCode,

                /*
                |--------------------------------------------------------------------------
                | Variant Attributes
                |--------------------------------------------------------------------------
                */
                'attributes' => $attributes,

                /*
                |--------------------------------------------------------------------------
                | Variant Source Data
                |--------------------------------------------------------------------------
                |
                | These remain canonical values. Channel-specific writers can
                | later determine how dimensions, components, or other data are
                | represented.
                |
                */
                'data' => [
                    'codeColour' => $item['codeColour'] ?? null,
                    'codeColourName' => $colourName,
                    'codeSize' => $item['codeSize'] ?? null,
                    'codeSizeName' => $sizeName,
                    'categorisedAttribute' => (
                        $item['categorisedAttribute'] ?? null
                    ),
                    'packagingAndDimension' => (
                        $item['packagingAndDimension'] ?? []
                    ),
                    'productDimension' => (
                        $item['productDimension'] ?? []
                    ),
                    'isLogo24' => $item['isLogo24'] ?? null,
                    'components' => $item['components'] ?? [],
                ],

                /*
                |--------------------------------------------------------------------------
                | Projection Ownership
                |--------------------------------------------------------------------------
                */
                'meta' => [
                    '_blackprint_managed' => 'yes',
                    '_blackprint_supplier' => $supplier,
                    '_blackprint_variant_code' => $fullCode,
                    '_blackprint_simple_code' => $simpleCode,
                ],
            ];
        }

        return ProjectionResult::planned(
            data: $projection
        );
    }

    /**
     * Normalize a canonical value to a string for the projection plan.
     */
    private function stringValue(
        mixed $value
    ): string {
        return is_string($value)
            ? $value
            : '';
    }
}
