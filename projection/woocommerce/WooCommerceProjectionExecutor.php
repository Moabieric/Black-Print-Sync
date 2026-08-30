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
 * layer and WooCommerce. It receives a channel-specific projection plan
 * and is responsible for creating or updating WooCommerce records.
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
| Existing Parent Lookup
|--------------------------------------------------------------------------
|
| A projection may only update a product that is explicitly identified as
| BlackPrint-managed. We never adopt arbitrary WooCommerce products.
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
| Execution Decision
|--------------------------------------------------------------------------
|
| At this stage the executor has determined what operation would be
| required, but persistence has not yet been implemented.
|
*/

if (
    $lookupStatus === 'not_found'
) {

    return ProjectionResult::skipped(
        'WooCommerce product does not exist and would be created.',
        [
            'decision' => 'create',
            'projection' => $projection,
        ]
    );
}

$productId =
    $parentLookup['product_id'];

return ProjectionResult::skipped(
    'Existing BlackPrint-managed WooCommerce product would be updated.',
    [
        'decision' => 'update',
        'product_id' => $productId,
        'projection' => $projection,
    ]
);
}


/**
 * Locate an existing BlackPrint-managed WooCommerce parent product.
 *
 * Products are identified exclusively through BlackPrint ownership
 * metadata. The executor must never adopt an arbitrary WooCommerce
 * product based on a name, SKU, or other non-ownership identifier.
 *
 * @param array<string, mixed> $parent
 *
 * @return array{
 *     status: 'not_found'|'found'|'duplicate'|'invalid',
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