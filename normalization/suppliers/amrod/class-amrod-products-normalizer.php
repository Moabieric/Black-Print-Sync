<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Normalization\Suppliers\Amrod;

use BlackPrint\Commerce\Normalization\Contracts\CanonicalNormalizer;
use BlackPrint\Commerce\Normalization\DTO\CanonicalProduct;

defined('ABSPATH') || exit;

/**
 * Amrod Products Normalizer.
 *
 * Transforms one raw Amrod product record into the
 * supplier-agnostic BlackPrint Canonical Product model.
 *
 * This normalizer:
 *
 * - Does not call the Amrod API.
 * - Does not modify immutable snapshots.
 * - Does not apply BlackPrint business rules.
 * - Does not persist canonical products.
 * - Does not write to WooCommerce.
 *
 * Its sole responsibility is supplier-to-canonical
 * structural transformation.
 */
final class AmrodProductsNormalizer implements CanonicalNormalizer
{
    /**
     * Supplier identifier.
     */
    public function supplier(): string
    {
        return 'amrod';
    }

    /**
     * Supported supplier resource.
     */
    public function resource(): string
    {
        return 'products';
    }

    /**
     * Normalize one raw Amrod product record.
     */
    public function normalize(
        array $record
    ): CanonicalProduct {

        /*
        |--------------------------------------------------------------------------
        | Source Identifiers
        |--------------------------------------------------------------------------
        */

        $simpleCode = (string) (
            $record['simpleCode'] ?? ''
        );

        $fullCode = (string) (
            $record['fullCode'] ?? ''
        );


        /*
        |--------------------------------------------------------------------------
        | Canonical Product
        |--------------------------------------------------------------------------
        */

        return new CanonicalProduct(

            /*
            |--------------------------------------------------------------------------
            | Identity
            |--------------------------------------------------------------------------
            |
            | The supplier code is used as a stable external key at
            | the normalization boundary. It is not a BlackPrint
            | internal primary identifier.
            |
            */

            identity: [

                'external_key' => $simpleCode,

            ],


            /*
            |--------------------------------------------------------------------------
            | Hierarchy
            |--------------------------------------------------------------------------
            |
            | Preserve the supplier's base-product structure.
            | Sellable variants remain attached to their parent
            | canonical product.
            |
            */

            hierarchy: [

                'type' => 'product',

                'has_variants' =>
                    ! empty(
                        $record['variants']
                    ),

            ],


            /*
            |--------------------------------------------------------------------------
            | Content
            |--------------------------------------------------------------------------
            */

            content: [

                'name' =>
                    $record['productName'] ?? '',

                'description' =>
                    $record['description'] ?? '',

                'keywords' =>
                    $record['keywords'] ?? '',

                'tags' =>
                    $record['tags'] ?? '',

            ],


            /*
            |--------------------------------------------------------------------------
            | Classification
            |--------------------------------------------------------------------------
            */

            classification: [

                'categories' =>
                    is_array(
                        $record['categories'] ?? null
                    )
                        ? $record['categories']
                        : [],

                'brand' =>
                    $record['brand'] ?? null,

                'gender' =>
                    $record['gender'] ?? null,

                'material' =>
                    $record['material'] ?? null,

                'fit' =>
                    $record['fit'] ?? null,

                'feature' =>
                    $record['feature'] ?? null,

                'attributes' =>
                    is_array(
                        $record['categorisedAttribute'] ?? null
                    )
                        ? $record['categorisedAttribute']
                        : [],

            ],


            /*
            |--------------------------------------------------------------------------
            | Variants
            |--------------------------------------------------------------------------
            |
            | Preserve the complete supplier variant structure for
            | this first structural normalization pass.
            |
            | A later refinement can introduce dedicated canonical
            | variant DTOs without changing the supplier ingestion
            | boundary.
            |
            */

            variant: [

                'variants' =>
                    is_array(
                        $record['variants'] ?? null
                    )
                        ? $record['variants']
                        : [],

            ],


            /*
            |--------------------------------------------------------------------------
            | Commercial
            |--------------------------------------------------------------------------
            */

            commercial: [

                'minimum_quantity' =>
                    $record['minimum'] ?? null,

                'maximum_quantity' =>
                    $record['maximum'] ?? null,

                'quantity_increment' =>
                    $record['incrementedBy'] ?? null,

                'promotion' =>
                    $record['promotion'] ?? null,

                'made_to_order' =>
                    $record['madeToOrder'] ?? null,

                'made_to_order_message' =>
                    $record['madeToOrderMessage'] ?? null,

            ],


            /*
            |--------------------------------------------------------------------------
            | Inventory
            |--------------------------------------------------------------------------
            */

            inventory: [

                'inventory_type' =>
                    $record['inventoryType'] ?? null,

                'behaviour' =>
                    $record['behaviour'] ?? null,

            ],


            /*
            |--------------------------------------------------------------------------
            | Media
            |--------------------------------------------------------------------------
            */

            media: [

                'images' =>
                    is_array(
                        $record['images'] ?? null
                    )
                        ? $record['images']
                        : [],

                'videos' =>
                    is_array(
                        $record['videos'] ?? null
                    )
                        ? $record['videos']
                        : [],

                'colour_images' =>
                    $record['colourImages'] ?? null,

            ],


            /*
            |--------------------------------------------------------------------------
            | Relationships
            |--------------------------------------------------------------------------
            */

            relationships: [

                'companion_codes' =>
                    $record['companionCodes'] ?? null,

                'related_codes' =>
                    is_array(
                        $record['relatedCodes'] ?? null
                    )
                        ? $record['relatedCodes']
                        : [],

                'matching_codes' =>
                    is_array(
                        $record['matchingCodes'] ?? null
                    )
                        ? $record['matchingCodes']
                        : [],

                'grouping_codes' =>
                    is_array(
                        $record['groupingCodes'] ?? null
                    )
                        ? $record['groupingCodes']
                        : [],

                'giftset_grouping_code' =>
                    $record['groupingCodeGiftsets'] ?? null,

                'components' =>
                    $this->extractComponents(
                        $record
                    ),

            ],


            /*
            |--------------------------------------------------------------------------
            | Branding
            |--------------------------------------------------------------------------
            */

            branding: [

                'templates' =>
                    is_array(
                        $record['brandingTemplates'] ?? null
                    )
                        ? $record['brandingTemplates']
                        : [],

                'full_guide' =>
                    $record['fullBrandingGuide'] ?? null,

                'logo24_guide' =>
                    $record['logo24BrandingGuide'] ?? null,

                'is_logo24' =>
                    $record['isLogo24'] ?? null,

                'logo24_branding' =>
                    $record['logo24Branding'] ?? null,

            ],


            /*
            |--------------------------------------------------------------------------
            | Provenance
            |--------------------------------------------------------------------------
            |
            | Retains source context required for traceability,
            | reconciliation and future supplier synchronization.
            |
            */

            provenance: [

                'supplier' => 'amrod',

                'resource' => 'products',

                'source_product_code' =>
                    $simpleCode,

                'source_full_code' =>
                    $fullCode,

                'source_type' =>
                    $record['type'] ?? null,

                'introduced_at' =>
                    $record['introduced'] ?? null,

            ]

        );
    }


    /**
     * Extract components from supplier variants.
     *
     * Components belong to individual variants in the
     * Amrod source structure. This helper preserves them
     * without flattening or modifying their meaning.
     */
    private function extractComponents(
        array $record
    ): array {

        $components = [];

        $variants =
            $record['variants'] ?? [];

        if (! is_array($variants)) {
            return $components;
        }

        foreach ($variants as $variant) {

            if (! is_array($variant)) {
                continue;
            }

            if (
                ! empty(
                    $variant['components']
                )
            ) {
                $components[] = [

                    'variant_code' =>
                        $variant['fullCode'] ?? null,

                    'components' =>
                        $variant['components'],

                ];
            }
        }

        return $components;
    }
}