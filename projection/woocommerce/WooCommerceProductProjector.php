<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Projection\WooCommerce;

use BlackPrint\Commerce\Projection\Contracts\ProductProjectorInterface;
use BlackPrint\Commerce\Projection\DTO\ProjectionResult;

defined('ABSPATH') || exit;

/**
 * Builds a deterministic WooCommerce projection plan
 * from a canonical BlackPrint product.
 *
 * This class is intentionally read-only.
 *
 * It translates the canonical representation into the
 * structure required by the future WooCommerce projection
 * writer. It does not create, update, delete, or otherwise
 * mutate WooCommerce records.
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
        $identity       = $product['identity'] ?? [];
        $hierarchy      = $product['hierarchy'] ?? [];
        $content        = $product['content'] ?? [];
        $classification = $product['classification'] ?? [];
        $variant        = $product['variant'] ?? [];
        $commercial     = $product['commercial'] ?? [];
        $inventory      = $product['inventory'] ?? [];
        $media          = $product['media'] ?? [];
        $relationships  = $product['relationships'] ?? [];
        $branding       = $product['branding'] ?? [];
        $provenance     = $product['provenance'] ?? [];

        if (! is_array($identity)) {
            return ProjectionResult::failed(
                'Canonical product identity must be an array.'
            );
        }

        $supplierProductId = $identity['supplier_product_id'] ?? null;
        $supplierProductCode = $identity['supplier_product_code'] ?? null;

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

        if (! is_array($variant)) {
            return ProjectionResult::failed(
                'Canonical product variant must be an array.'
            );
        }

        $variants = $variant['items'] ?? [];

        if (! is_array($variants)) {
            return ProjectionResult::failed(
                'Canonical product variant.items must be an array.'
            );
        }

        $supplier = $provenance['supplier'] ?? null;

        if (
            ! is_string($supplier)
            || $supplier === ''
        ) {
            return ProjectionResult::failed(
                'Canonical product provenance is missing supplier.'
            );
        }

        $projection = [
            'channel' => 'woocommerce',

            'parent' => [
                'identity' => [
                    'supplier'     => $supplier,
                    'product_id'   => $supplierProductId,
                    'product_code' => $supplierProductCode,
                ],

                'sku' => $supplierProductCode,

                'name' => is_string($content['name'] ?? null)
                    ? $content['name']
                    : '',

                'description' => is_string($content['description'] ?? null)
                    ? $content['description']
                    : '',

                'hierarchy' => [
                    'type' => is_string($hierarchy['type'] ?? null)
                        ? $hierarchy['type']
                        : 'Product',

                    'decoupled' => (bool) (
                        $hierarchy['decoupled'] ?? false
                    ),
                ],

                'classification' => [
                    'categories' => is_array(
                        $classification['categories'] ?? null
                    )
                        ? $classification['categories']
                        : [],

                    'attributes' => is_array(
                        $classification['attributes'] ?? null
                    )
                        ? $classification['attributes']
                        : [],

                    'brand' => $classification['brand'] ?? null,
                ],

                'commercial' => is_array($commercial)
                    ? $commercial
                    : [],

                'inventory' => is_array($inventory)
                    ? $inventory
                    : [],

                'media' => is_array($media)
                    ? $media
                    : [],

                'relationships' => is_array($relationships)
                    ? $relationships
                    : [],

                'branding' => is_array($branding)
                    ? $branding
                    : [],

                'provenance' => is_array($provenance)
                    ? $provenance
                    : [],

                'meta' => [
                    '_blackprint_managed'      => 'yes',
                    '_blackprint_supplier'     => $supplier,
                    '_blackprint_product_id'   => $supplierProductId,
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

            $simpleCode = $item['simpleCode'] ?? null;
            $fullCode   = $item['fullCode'] ?? null;

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

            $colourName = $item['codeColourName'] ?? null;
            $sizeName   = $item['codeSizeName'] ?? null;

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
                'identity' => [
                    'supplier'    => $supplier,
                    'simple_code' => $simpleCode,
                    'full_code'   => $fullCode,
                ],

                'sku' => $fullCode,

                'attributes' => $attributes,

                'variant_data' => $item,

                'meta' => [
                    '_blackprint_managed'     => 'yes',
                    '_blackprint_supplier'    => $supplier,
                    '_blackprint_variant_code' => $fullCode,
                    '_blackprint_simple_code' => $simpleCode,
                ],
            ];
        }

        return ProjectionResult::planned(
            data: [
                'projection' => $projection,
            ]
        );
    }
}
