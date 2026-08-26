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
 * - Does not modify snapshots.
 * - Does not read or write WooCommerce.
 * - Does not apply BlackPrint business rules.
 * - Does not persist canonical products.
 */
final class AmrodProductsNormalizer implements CanonicalNormalizer
{
    /**
     * Supplier supported by this normalizer.
     */
    public function supplier(): string
    {
        return 'amrod';
    }

    /**
     * Resource supported by this normalizer.
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

        return new CanonicalProduct(

            /*
            |--------------------------------------------------------------------------
            | Identity
            |--------------------------------------------------------------------------
            */

            identity: [

                'supplier_product_id' =>
                    $record['simpleCode'] ?? null,

                'supplier_product_code' =>
                    $record['fullCode'] ?? null,

            ],


            /*
|--------------------------------------------------------------------------
| Hierarchy
|--------------------------------------------------------------------------
|
| Describes the structural characteristics of the canonical product.
| Sellable variant records belong exclusively to variant.items.
|
*/

hierarchy: [

    'type' =>
        $record['type'] ?? null,

    'decoupled' =>
        $record['decoupled'] ?? null,

],


            /*
            |--------------------------------------------------------------------------
            | Content
            |--------------------------------------------------------------------------
            */

            content: [

                'name' =>
                    $record['productName'] ?? null,

                'description' =>
                    $record['description'] ?? null,

                'keywords' =>
                    $record['keywords'] ?? null,

                'tags' =>
                    $record['tags'] ?? null,

            ],


            /*
            |--------------------------------------------------------------------------
            | Classification
            |--------------------------------------------------------------------------
            */

            classification: [

                'categories' =>
                    $record['categories'] ?? [],

                'brand' =>
                    $record['brand'] ?? null,

                'attributes' => [

                    'categorised' =>
                        $record['categorisedAttribute'] ?? null,

                    'gender' =>
                        $record['gender'] ?? null,

                    'material' =>
                        $record['material'] ?? null,

                    'fit' =>
                        $record['fit'] ?? null,

                    'feature' =>
                        $record['feature'] ?? null,

                ],

            ],


            /*
            |--------------------------------------------------------------------------
            | Variant
            |--------------------------------------------------------------------------
            |
            | Variant data remains supplier-neutral at this stage.
            | The raw variant records are preserved for the canonical
            | model to interpret without introducing WooCommerce concepts.
            |
            */

            variant: [

                'items' =>
                    $record['variants'] ?? [],

            ],


            /*
            |--------------------------------------------------------------------------
            | Commercial
            |--------------------------------------------------------------------------
            |
            | Supplier ordering constraints only.
            | No BlackPrint pricing or margin rules belong here.
            |
            */

            commercial: [

                'minimum' =>
                    $record['minimum'] ?? null,

                'maximum' =>
                    $record['maximum'] ?? null,

                'increment' =>
                    $record['incrementedBy'] ?? null,

                'promotion' =>
                    $record['promotion'] ?? null,

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

                'made_to_order' =>
                    $record['madeToOrder'] ?? null,

                'made_to_order_message' =>
                    $record['madeToOrderMessage'] ?? null,

            ],


            /*
            |--------------------------------------------------------------------------
            | Media
            |--------------------------------------------------------------------------
            */

            media: [

                'images' =>
                    $record['images'] ?? [],

                'colour_images' =>
                    $record['colourImages'] ?? [],

                'videos' =>
                    $record['videos'] ?? [],

            ],


            /*
            |--------------------------------------------------------------------------
            | Relationships
            |--------------------------------------------------------------------------
            */

            relationships: [

                'companion_codes' =>
                    $record['companionCodes'] ?? [],

                'related_codes' =>
                    $record['relatedCodes'] ?? [],

                'matching_codes' =>
                    $record['matchingCodes'] ?? [],

                'grouping_codes' =>
                    $record['groupingCodes'] ?? [],

                'giftset_grouping_code' =>
                    $record['groupingCodeGiftsets'] ?? null,

            ],


            /*
            |--------------------------------------------------------------------------
            | Branding
            |--------------------------------------------------------------------------
            */

            branding: [

                'templates' =>
                    $record['brandingTemplates'] ?? [],

                'full_branding_guide' =>
                    $record['fullBrandingGuide'] ?? null,

                'logo24_branding_guide' =>
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
            | Records where this canonical product originated.
            | This is not business data and must not be interpreted
            | as a sales-channel concern.
            |
            */

            provenance: [

                'supplier' =>
                    $this->supplier(),

                'resource' =>
                    $this->resource(),

                'simple_code' =>
                    $record['simpleCode'] ?? null,

                'full_code' =>
                    $record['fullCode'] ?? null,

                'introduced' =>
                    $record['introduced'] ?? null,

            ]

        );
    }
}