<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Projection\WooCommerce;

use BlackPrint\Commerce\Projection\Contracts\ProjectionExecutorInterface;
use BlackPrint\Commerce\Projection\DTO\ProjectionResult;

defined('ABSPATH') || exit;

/**
 * Executes a WooCommerce projection plan.
 *
 * This class is the mutation boundary between the BlackPrint projection
 * layer and WooCommerce.
 *
 * Step 12.1.2:
 *
 * - Creates a controlled BlackPrint-managed variable parent when required.
 * - Creates one controlled BlackPrint-managed variation beneath an existing
 *   BlackPrint-managed parent.
 * - Does not write pricing.
 * - Does not write stock.
 * - Does not write images.
 * - Does not write branding.
 * - Does not write categories.
 * - Does not write relationships.
 * - Does not adopt arbitrary WooCommerce products.
 *
 * The executor must never receive supplier payloads directly.
 */
final class WooCommerceProjectionExecutor implements ProjectionExecutorInterface
{
    /**
     * Execute a WooCommerce projection plan.
     *
     * @param array<string, mixed> $projection
     */
    public function execute(
        array $projection
    ): ProjectionResult {

        /*
        |--------------------------------------------------------------------------
        | Projection Validation
        |--------------------------------------------------------------------------
        */

        $channel =
            $projection['channel']
            ?? null;

        if (
            $channel !== 'woocommerce'
        ) {

            return ProjectionResult::failed(
                'Projection is not a WooCommerce projection.'
            );
        }

        $parent =
            $projection['parent']
            ?? null;

        if (
            ! is_array($parent)
        ) {

            return ProjectionResult::failed(
                'WooCommerce projection is missing a valid parent.'
            );
        }

        $variants =
            $projection['variants']
            ?? null;

        if (
            ! is_array($variants)
            || $variants === []
        ) {

            return ProjectionResult::failed(
                'WooCommerce projection must contain at least one variant.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Parent Validation
        |--------------------------------------------------------------------------
        */

        $parentValidation =
            $this->validateParentProjection(
                $parent
            );

        if (
            ! $parentValidation['valid']
        ) {

            return ProjectionResult::failed(
                $parentValidation['message']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Existing Parent Lookup
        |--------------------------------------------------------------------------
        |
        | A projection may only update or extend a product that is explicitly
        | identified as BlackPrint-managed.
        |
        | We never adopt arbitrary WooCommerce products.
        |
        */

        $parentLookup =
            $this->findExistingParent(
                $parent
            );

        $lookupStatus =
            $parentLookup['status'];

        if (
            $lookupStatus === 'duplicate'
        ) {

            return ProjectionResult::failed(
                'Multiple WooCommerce products share the same BlackPrint product identity.'
            );
        }

        if (
            $lookupStatus === 'invalid'
        ) {

            return ProjectionResult::failed(
                'WooCommerce projection parent identity is invalid.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Controlled Parent Creation
        |--------------------------------------------------------------------------
        |
        | This preserves the accepted 12.1.1 behaviour.
        |
        | If the parent does not exist, create only the parent.
        | No variation is created during this branch.
        |
        */

        if (
            $lookupStatus === 'not_found'
        ) {

            return $this->createParent(
                $parent
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Existing Parent
        |--------------------------------------------------------------------------
        |
        | Step 12.1.2:
        |
        | The parent is already known and explicitly BlackPrint-managed.
        | We may now create ONE controlled variation beneath it.
        |
        */

        $productId =
            $parentLookup['product_id'];

        if (
            ! is_int($productId)
            || $productId <= 0
        ) {

            return ProjectionResult::failed(
                'Existing WooCommerce parent returned an invalid product ID.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Existing Parent Verification
        |--------------------------------------------------------------------------
        */

        $existingParent =
            \wc_get_product(
                $productId
            );

        if (
            ! $existingParent
        ) {

            return ProjectionResult::failed(
                'Existing WooCommerce parent could not be reloaded.'
            );
        }

        if (
            $existingParent->get_type()
            !== 'variable'
        ) {

            return ProjectionResult::failed(
                'Existing BlackPrint-managed parent is not a variable product.'
            );
        }

        if (
            $existingParent->get_meta(
                '_blackprint_managed'
            )
            !== 'yes'
        ) {

            return ProjectionResult::failed(
                'Existing WooCommerce parent is not BlackPrint-managed.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Controlled Variant Selection
        |--------------------------------------------------------------------------
        |
        | Step 12.1.2 deliberately creates exactly ONE variant.
        |
        | The first canonical variant is selected for the controlled test.
        |
        */

        $variant =
            $variants[0]
            ?? null;

        if (
            ! is_array($variant)
        ) {

            return ProjectionResult::failed(
                'Controlled variant creation requires a valid first variant.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Controlled Variant Creation
        |--------------------------------------------------------------------------
        */

        return $this->createVariation(
            parentId: $productId,
            variant: $variant
        );
    }

    /**
     * Validate the parent projection at the mutation boundary.
     *
     * @param array<string, mixed> $parent
     *
     * @return array{
     *     valid: bool,
     *     message: string
     * }
     */
    private function validateParentProjection(
        array $parent
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Parent Type
        |--------------------------------------------------------------------------
        */

        if (
            ($parent['type'] ?? null)
            !== 'variable'
        ) {

            return [
                'valid' => false,
                'message' =>
                    'WooCommerce parent projection must be variable.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Identity
        |--------------------------------------------------------------------------
        */

        $identity =
            $parent['identity']
            ?? null;

        if (
            ! is_array($identity)
        ) {

            return [
                'valid' => false,
                'message' =>
                    'WooCommerce parent projection is missing valid identity data.',
            ];
        }

        $supplier =
            $identity['supplier']
            ?? null;

        $supplierProductId =
            $identity['supplier_product_id']
            ?? null;

        $supplierProductCode =
            $identity['supplier_product_code']
            ?? null;

        if (
            ! is_string($supplier)
            || $supplier === ''
        ) {

            return [
                'valid' => false,
                'message' =>
                    'WooCommerce parent projection is missing supplier identity.',
            ];
        }

        if (
            ! is_string($supplierProductId)
            || $supplierProductId === ''
        ) {

            return [
                'valid' => false,
                'message' =>
                    'WooCommerce parent projection is missing supplier_product_id.',
            ];
        }

        if (
            ! is_string($supplierProductCode)
            || $supplierProductCode === ''
        ) {

            return [
                'valid' => false,
                'message' =>
                    'WooCommerce parent projection is missing supplier_product_code.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Content
        |--------------------------------------------------------------------------
        */

        $content =
            $parent['content']
            ?? [];

        if (
            ! is_array($content)
        ) {

            return [
                'valid' => false,
                'message' =>
                    'WooCommerce parent projection content is invalid.',
            ];
        }

        $name =
            $content['name']
            ?? null;

        if (
            ! is_string($name)
            || trim($name) === ''
        ) {

            return [
                'valid' => false,
                'message' =>
                    'WooCommerce parent projection is missing a product name.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Ownership Metadata
        |--------------------------------------------------------------------------
        */

        $meta =
            $parent['meta']
            ?? null;

        if (
            ! is_array($meta)
        ) {

            return [
                'valid' => false,
                'message' =>
                    'WooCommerce parent projection is missing ownership metadata.',
            ];
        }

        if (
            ($meta['_blackprint_managed'] ?? null)
            !== 'yes'
        ) {

            return [
                'valid' => false,
                'message' =>
                    'WooCommerce parent projection is not marked as BlackPrint-managed.',
            ];
        }

        if (
            ($meta['_blackprint_supplier'] ?? null)
            !== $supplier
        ) {

            return [
                'valid' => false,
                'message' =>
                    'WooCommerce parent projection supplier ownership does not match identity.',
            ];
        }

        if (
            ($meta['_blackprint_product_id'] ?? null)
            !== $supplierProductId
        ) {

            return [
                'valid' => false,
                'message' =>
                    'WooCommerce parent projection product ownership does not match identity.',
            ];
        }

        if (
            ($meta['_blackprint_product_code'] ?? null)
            !== $supplierProductCode
        ) {

            return [
                'valid' => false,
                'message' =>
                    'WooCommerce parent projection product code ownership does not match identity.',
            ];
        }

        return [
            'valid' => true,
            'message' => '',
        ];
    }

    /**
     * Create one controlled BlackPrint-managed WooCommerce parent.
     *
     * Step 12.1 intentionally creates only the parent product.
     *
     * No variation records are created here.
     *
     * @param array<string, mixed> $parent
     */
    private function createParent(
        array $parent
    ): ProjectionResult {

        if (
            ! class_exists(
                'WC_Product_Variable'
            )
        ) {

            return ProjectionResult::failed(
                'WooCommerce WC_Product_Variable is unavailable.'
            );
        }

        $identity =
            $parent['identity'];

        $content =
            $parent['content']
            ?? [];

        $meta =
            $parent['meta'];

        /*
        |--------------------------------------------------------------------------
        | Create Variable Product
        |--------------------------------------------------------------------------
        */

        $product =
            new \WC_Product_Variable();

        /*
        |--------------------------------------------------------------------------
        | Parent Content
        |--------------------------------------------------------------------------
        */

        $name =
            $content['name']
            ?? '';

        $description =
            $content['description']
            ?? '';

        if (
            ! is_string($name)
        ) {
            $name = '';
        }

        if (
            ! is_string($description)
        ) {
            $description = '';
        }

        $product->set_name(
            $name
        );

        $product->set_description(
            $description
        );

        /*
        |--------------------------------------------------------------------------
        | Controlled Ownership Metadata
        |--------------------------------------------------------------------------
        */

        $product->update_meta_data(
            '_blackprint_managed',
            'yes'
        );

        $product->update_meta_data(
            '_blackprint_supplier',
            $meta['_blackprint_supplier']
        );

        $product->update_meta_data(
            '_blackprint_product_id',
            $meta['_blackprint_product_id']
        );

        $product->update_meta_data(
            '_blackprint_product_code',
            $meta['_blackprint_product_code']
        );

        /*
        |--------------------------------------------------------------------------
        | Save
        |--------------------------------------------------------------------------
        */

        try {

            $productId =
                $product->save();

        } catch (
            \Throwable $exception
        ) {

            return ProjectionResult::failed(
                'WooCommerce parent creation failed: ' .
                $exception->getMessage()
            );
        }

        if (
            ! is_int($productId)
            || $productId <= 0
        ) {

            return ProjectionResult::failed(
                'WooCommerce parent creation returned an invalid product ID.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Post-Save Ownership Verification
        |--------------------------------------------------------------------------
        */

        $savedProduct =
            \wc_get_product(
                $productId
            );

        if (
            ! $savedProduct
        ) {

            return ProjectionResult::failed(
                'WooCommerce parent was created but could not be reloaded.'
            );
        }

        if (
            $savedProduct->get_type()
            !== 'variable'
        ) {

            return ProjectionResult::failed(
                'WooCommerce parent was created with an unexpected product type.'
            );
        }

        if (
            $savedProduct->get_meta(
                '_blackprint_managed'
            )
            !== 'yes'
        ) {

            return ProjectionResult::failed(
                'WooCommerce parent was created without BlackPrint ownership metadata.'
            );
        }

        if (
            $savedProduct->get_meta(
                '_blackprint_supplier'
            )
            !== $identity['supplier']
        ) {

            return ProjectionResult::failed(
                'WooCommerce parent supplier ownership verification failed.'
            );
        }

        if (
            $savedProduct->get_meta(
                '_blackprint_product_id'
            )
            !== $identity['supplier_product_id']
        ) {

            return ProjectionResult::failed(
                'WooCommerce parent product identity verification failed.'
            );
        }

        if (
            $savedProduct->get_meta(
                '_blackprint_product_code'
            )
            !== $identity['supplier_product_code']
        ) {

            return ProjectionResult::failed(
                'WooCommerce parent product code verification failed.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Creation Result
        |--------------------------------------------------------------------------
        */

        return ProjectionResult::created(
            productId: $productId,
            data: [
                'decision' => 'create',
                'product_type' => 'variable',
                'supplier' =>
                    $identity['supplier'],
                'supplier_product_id' =>
                    $identity['supplier_product_id'],
                'supplier_product_code' =>
                    $identity['supplier_product_code'],
            ]
        );
    }

    /**
     * Create one controlled BlackPrint-managed WooCommerce variation.
     *
     * Step 12.1.2 deliberately creates exactly one variation.
     *
     * @param int $parentId
     * @param array<string, mixed> $variant
     */
    private function createVariation(
        int $parentId,
        array $variant
    ): ProjectionResult {

        if (
            ! class_exists(
                'WC_Product_Variation'
            )
        ) {

            return ProjectionResult::failed(
                'WooCommerce WC_Product_Variation is unavailable.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Variant Identity
        |--------------------------------------------------------------------------
        */

        $identity =
            $variant['identity']
            ?? null;

        if (
            ! is_array($identity)
        ) {

            return ProjectionResult::failed(
                'Canonical variant is missing valid projection identity.'
            );
        }

        $supplier =
            $identity['supplier']
            ?? null;

        $simpleCode =
            $identity['simple_code']
            ?? null;

        $fullCode =
            $identity['full_code']
            ?? null;

        if (
            ! is_string($supplier)
            || $supplier === ''
        ) {

            return ProjectionResult::failed(
                'Canonical variant is missing supplier identity.'
            );
        }

        if (
            ! is_string($simpleCode)
            || $simpleCode === ''
        ) {

            return ProjectionResult::failed(
                'Canonical variant is missing simple_code.'
            );
        }

        if (
            ! is_string($fullCode)
            || $fullCode === ''
        ) {

            return ProjectionResult::failed(
                'Canonical variant is missing full_code.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Commerce Identity
        |--------------------------------------------------------------------------
        */

        $sku =
            $variant['sku']
            ?? null;

        if (
            ! is_string($sku)
            || $sku === ''
        ) {

            return ProjectionResult::failed(
                'Canonical variant is missing SKU.'
            );
        }

        if (
            $sku !== $fullCode
        ) {

            return ProjectionResult::failed(
                'Canonical variant SKU does not match full_code.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Parent Verification
        |--------------------------------------------------------------------------
        */

        $parent =
            \wc_get_product(
                $parentId
            );

        if (
            ! $parent
        ) {

            return ProjectionResult::failed(
                'Variation parent could not be loaded.'
            );
        }

        if (
            $parent->get_type()
            !== 'variable'
        ) {

            return ProjectionResult::failed(
                'Variation parent is not a variable WooCommerce product.'
            );
        }

        if (
            $parent->get_meta(
                '_blackprint_managed'
            )
            !== 'yes'
        ) {

            return ProjectionResult::failed(
                'Variation parent is not BlackPrint-managed.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Duplicate Variation Safety
        |--------------------------------------------------------------------------
        |
        | Never create a second BlackPrint-managed variation with the same
        | canonical fullCode beneath this parent.
        |
        */

        $existingVariationIds =
            get_posts(
                [
                    'post_type' =>
                        'product_variation',

                    'post_status' =>
                        'any',

                    'posts_per_page' =>
                        2,

                    'fields' =>
                        'ids',

                    'post_parent' =>
                        $parentId,

                    'meta_query' =>
                        [
                            'relation' =>
                                'AND',

                            [
                                'key' =>
                                    '_blackprint_managed',

                                'value' =>
                                    'yes',
                            ],

                            [
                                'key' =>
                                    '_blackprint_supplier',

                                'value' =>
                                    $supplier,
                            ],

                            [
                                'key' =>
                                    '_blackprint_variant_code',

                                'value' =>
                                    $fullCode,
                            ],
                        ],
                ]
            );

        if (
            is_array($existingVariationIds)
            && $existingVariationIds !== []
        ) {

            return ProjectionResult::failed(
                'A BlackPrint-managed WooCommerce variation already exists for full_code: ' .
                $fullCode
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Variant Attributes
        |--------------------------------------------------------------------------
        */

        $attributes =
            $variant['attributes']
            ?? [];

        if (
            ! is_array($attributes)
        ) {

            return ProjectionResult::failed(
                'Canonical variant attributes are invalid.'
            );
        }

        /*
|--------------------------------------------------------------------------
| Global WooCommerce SKU Conflict Check
|--------------------------------------------------------------------------
|
| WooCommerce SKUs must be globally unique.
|
| The BlackPrint-managed variant check above only verifies whether
| this canonical variant already exists beneath this parent.
|
| This additional check detects an SKU owned by another WooCommerce
| product so the controlled creation test can report the conflict
| explicitly instead of exposing WooCommerce's generic exception.
|
*/

$existingSkuProductId =
    \wc_get_product_id_by_sku(
        $sku
    );

if (
    is_int($existingSkuProductId)
    && $existingSkuProductId > 0
) {

    $existingSkuProduct =
        \wc_get_product(
            $existingSkuProductId
        );

    $existingSkuParentId =
        $existingSkuProduct
            ? $existingSkuProduct->get_parent_id()
            : 0;

    $existingSkuType =
        $existingSkuProduct
            ? $existingSkuProduct->get_type()
            : 'unknown';

    $existingSkuManaged =
        $existingSkuProduct
            ? $existingSkuProduct->get_meta(
                '_blackprint_managed'
            )
            : '';

    $existingSkuSupplier =
        $existingSkuProduct
            ? $existingSkuProduct->get_meta(
                '_blackprint_supplier'
            )
            : '';

    $existingSkuVariantCode =
        $existingSkuProduct
            ? $existingSkuProduct->get_meta(
                '_blackprint_variant_code'
            )
            : '';

    return ProjectionResult::failed(
        'WooCommerce SKU conflict.' .
        ' SKU="' . $sku . '"' .
        ' Existing product ID=' . $existingSkuProductId .
        ' Type=' . $existingSkuType .
        ' Parent ID=' . $existingSkuParentId .
        ' BlackPrint managed="' . $existingSkuManaged . '"' .
        ' Supplier="' . $existingSkuSupplier . '"' .
        ' Variant code="' . $existingSkuVariantCode . '"'
    );
}

        /*
        |--------------------------------------------------------------------------
        | Create Variation
        |--------------------------------------------------------------------------
        */

        $variation =
            new \WC_Product_Variation();

        $variation->set_parent_id(
            $parentId
        );

        $variation->set_sku(
            $sku
        );

        if (
            $attributes !== []
        ) {

            $variation->set_attributes(
                $attributes
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Controlled Ownership Metadata
        |--------------------------------------------------------------------------
        */

        $variation->update_meta_data(
            '_blackprint_managed',
            'yes'
        );

        $variation->update_meta_data(
            '_blackprint_supplier',
            $supplier
        );

        $variation->update_meta_data(
            '_blackprint_variant_code',
            $fullCode
        );

        $variation->update_meta_data(
            '_blackprint_simple_code',
            $simpleCode
        );

        /*
        |--------------------------------------------------------------------------
        | Save
        |--------------------------------------------------------------------------
        */

        try {

            $variationId =
                $variation->save();

        } catch (
            \Throwable $exception
        ) {

            return ProjectionResult::failed(
                'WooCommerce variation creation failed: ' .
                $exception->getMessage()
            );
        }

        if (
            ! is_int($variationId)
            || $variationId <= 0
        ) {

            return ProjectionResult::failed(
                'WooCommerce variation creation returned an invalid product ID.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Post-Save Verification
        |--------------------------------------------------------------------------
        */

        $savedVariation =
            \wc_get_product(
                $variationId
            );

        if (
            ! $savedVariation
        ) {

            return ProjectionResult::failed(
                'WooCommerce variation was created but could not be reloaded.'
            );
        }

        if (
            $savedVariation->get_type()
            !== 'variation'
        ) {

            return ProjectionResult::failed(
                'WooCommerce variation was created with an unexpected product type.'
            );
        }

        if (
            $savedVariation->get_parent_id()
            !== $parentId
        ) {

            return ProjectionResult::failed(
                'WooCommerce variation parent verification failed.'
            );
        }

        if (
            $savedVariation->get_sku()
            !== $fullCode
        ) {

            return ProjectionResult::failed(
                'WooCommerce variation SKU verification failed.'
            );
        }

        if (
            $savedVariation->get_meta(
                '_blackprint_managed'
            )
            !== 'yes'
        ) {

            return ProjectionResult::failed(
                'WooCommerce variation was created without BlackPrint ownership metadata.'
            );
        }

        if (
            $savedVariation->get_meta(
                '_blackprint_supplier'
            )
            !== $supplier
        ) {

            return ProjectionResult::failed(
                'WooCommerce variation supplier ownership verification failed.'
            );
        }

        if (
            $savedVariation->get_meta(
                '_blackprint_variant_code'
            )
            !== $fullCode
        ) {

            return ProjectionResult::failed(
                'WooCommerce variation full_code ownership verification failed.'
            );
        }

        if (
            $savedVariation->get_meta(
                '_blackprint_simple_code'
            )
            !== $simpleCode
        ) {

            return ProjectionResult::failed(
                'WooCommerce variation simple_code ownership verification failed.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Parent Child Verification
        |--------------------------------------------------------------------------
        */

        $reloadedParent =
            \wc_get_product(
                $parentId
            );

        if (
            ! $reloadedParent
        ) {

            return ProjectionResult::failed(
                'Variation was created but the parent could not be reloaded for child verification.'
            );
        }

        $children =
            $reloadedParent->get_children();

        if (
            ! in_array(
                $variationId,
                $children,
                true
            )
        ) {

            return ProjectionResult::failed(
                'Created variation is not registered beneath the WooCommerce parent.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Creation Result
        |--------------------------------------------------------------------------
        */

        return ProjectionResult::created(
            productId: $variationId,
            data: [
                'decision' => 'create_variation',
                'product_type' => 'variation',
                'parent_id' => $parentId,
                'supplier' => $supplier,
                'simple_code' => $simpleCode,
                'full_code' => $fullCode,
                'sku' => $sku,
                'attributes' => $attributes,
            ]
        );
    }

    /**
     * Locate an existing BlackPrint-managed WooCommerce parent product.
     *
     * Products are identified exclusively through BlackPrint ownership
     * metadata.
     *
     * The executor must never adopt an arbitrary WooCommerce product based
     * on name, SKU, or another non-ownership identifier.
     *
     * @param array<string, mixed> $parent
     *
     * @return array{
     *     status: string,
     *     product_id: ?int
     * }
     */
    private function findExistingParent(
        array $parent
    ): array {

        $identity =
            $parent['identity']
            ?? [];

        if (
            ! is_array($identity)
        ) {

            return [
                'status' => 'invalid',
                'product_id' => null,
            ];
        }

        $supplier =
            $identity['supplier']
            ?? null;

        $supplierProductId =
            $identity['supplier_product_id']
            ?? null;

        if (
            ! is_string($supplier)
            || $supplier === ''
            || ! is_string($supplierProductId)
            || $supplierProductId === ''
        ) {

            return [
                'status' => 'invalid',
                'product_id' => null,
            ];
        }

        $products =
            \get_posts(
                [
                    'post_type' =>
                        'product',

                    'post_status' =>
                        'any',

                    /*
                     * We only need enough records to distinguish a unique
                     * identity match from a duplicate identity condition.
                     */
                    'posts_per_page' =>
                        2,

                    'fields' =>
                        'ids',

                    'meta_query' =>
                        [
                            'relation' =>
                                'AND',

                            [
                                'key' =>
                                    '_blackprint_managed',

                                'value' =>
                                    'yes',
                            ],

                            [
                                'key' =>
                                    '_blackprint_supplier',

                                'value' =>
                                    $supplier,
                            ],

                            [
                                'key' =>
                                    '_blackprint_product_id',

                                'value' =>
                                    $supplierProductId,
                            ],
                        ],
                ]
            );

        if (
            ! is_array($products)
            || $products === []
        ) {

            return [
                'status' => 'not_found',
                'product_id' => null,
            ];
        }

        if (
            count($products) > 1
        ) {

            return [
                'status' => 'duplicate',
                'product_id' => null,
            ];
        }

        $productId =
            $products[0];

        if (
            ! is_numeric($productId)
        ) {

            return [
                'status' => 'invalid',
                'product_id' => null,
            ];
        }

        return [
            'status' => 'found',
            'product_id' => (int) $productId,
        ];
    }
}