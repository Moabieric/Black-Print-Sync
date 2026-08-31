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
 * Step 12.1:
 *
 * - Creates a controlled BlackPrint-managed variable parent.
 * - Does not create variations.
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
        | A projection may only update a product that is explicitly identified
        | as BlackPrint-managed.
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
        | UPDATE is intentionally not implemented in Step 12.1.
        |
        | The existing product is identified correctly, but mutation of that
        | existing product belongs to the controlled update phase.
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

        return ProjectionResult::skipped(
            'Existing BlackPrint-managed WooCommerce product would be updated in a later projection phase.',
            [
                'decision' => 'update',
                'product_id' => $productId,
            ]
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
        |
        | Only BlackPrint ownership metadata is written in Step 12.1.
        |
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
        |
        | Confirm that the product we just created is actually BlackPrint
        | managed before reporting success.
        |
        */

        $savedProduct =
            wc_get_product(
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
            get_posts(
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