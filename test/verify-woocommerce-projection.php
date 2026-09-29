<?php

defined('ABSPATH') || exit;

/*
|--------------------------------------------------------------------------
| BlackPrint OS — WooCommerce Projection Verification Test
|--------------------------------------------------------------------------
|
| This test:
|
| 1. Loads an existing verified snapshot.
| 2. Normalizes the snapshot into canonical products.
| 3. Projects every canonical product into a WooCommerce projection plan.
| 4. Verifies parent and variant structure.
| 5. Verifies identity and SKU consistency.
| 6. Verifies BlackPrint ownership metadata.
| 7. Reports projection coverage and failures.
|
| IMPORTANT:
|
| This test is READ-ONLY.
|
| It does not:
|
| - Modify snapshots.
| - Modify snapshot payloads.
| - Persist canonical products.
| - Create WooCommerce products.
| - Update WooCommerce products.
| - Delete WooCommerce products.
| - Call WooCommerce mutation APIs.
|
*/

use BlackPrint\Commerce\Projection\WooCommerce\WooCommerceProductProjector;

add_action(
    'admin_init',
    static function (): void {

        /*
        |--------------------------------------------------------------------------
        | Prevent accidental repeated execution
        |--------------------------------------------------------------------------
        */

        if (
            ! isset($_GET['bp_test_projection'])
            || $_GET['bp_test_projection'] !== '1'
        ) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Existing Verified Snapshot
        |--------------------------------------------------------------------------
        */

        $snapshotUuid =
            'e1feb722-4844-4561-bb22-a199a57522d9';


        /*
        |--------------------------------------------------------------------------
        | Execute Normalization
        |--------------------------------------------------------------------------
        */

        try {

            $result = bp_commerce()
                ->normalization()
                ->normalize(
                    $snapshotUuid
                );


            /*
            |--------------------------------------------------------------------------
            | Abort if Normalization Failed
            |--------------------------------------------------------------------------
            */

            if (! $result->success()) {

                echo '<pre>';

                echo esc_html(
                    'Projection test aborted: normalization failed.'
                );

                echo "\n\n";

                foreach (
                    $result->errors() as $error
                ) {

                    echo esc_html(
                        is_scalar($error)
                            ? (string) $error
                            : wp_json_encode($error)
                    );

                    echo "\n";
                }

                echo '</pre>';

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Canonical Products
            |--------------------------------------------------------------------------
            */

            $products =
                $result->products();

            if ($products === null) {

                echo '<pre>';

                echo esc_html(
                    'Projection test aborted: no canonical product collection was returned.'
                );

                echo '</pre>';

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Projector
            |--------------------------------------------------------------------------
            */

            $projector =
                new WooCommerceProductProjector();


            /*
            |--------------------------------------------------------------------------
            | Projection Statistics
            |--------------------------------------------------------------------------
            */

            $canonicalProducts = 0;
            $plannedProducts = 0;
            $failedProducts = 0;

            $variableParents = 0;
            $variationChildren = 0;

            $singleVariantProducts = 0;
            $multipleVariantProducts = 0;

            $missingParentIdentity = 0;
            $missingVariantIdentity = 0;

            $skuMismatches = 0;

            $attributeProducts = 0;
            $attributeVariants = 0;

            $ownershipFailures = 0;
            $decoupledProducts = 0;

            $createdActions = 0;
            $updatedActions = 0;
            $skippedActions = 0;

            $failures = [];
            $skuMismatchDetails = [];
            $ownershipFailureDetails = [];


            /*
            |--------------------------------------------------------------------------
            | Project Every Canonical Product
            |--------------------------------------------------------------------------
            */

            foreach (
                $products->all() as $product
            ) {

                $canonicalProducts++;

                $canonical =
                    $product->toArray();

                $projectionResult =
                    $projector->project(
                        $canonical
                    );


                /*
                |--------------------------------------------------------------------------
                | Projection Result
                |--------------------------------------------------------------------------
                */

                if (! $projectionResult->success()) {

                    $failedProducts++;

                    $failures[] = [
                        'message' =>
                            $projectionResult->message(),

                        'identity' =>
                            $canonical['identity']
                            ?? [],
                    ];

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Projection Must Be Planned
                |--------------------------------------------------------------------------
                */

                if (
                    $projectionResult->action() === 'planned'
                ) {

                    $plannedProducts++;

                } elseif (
                    $projectionResult->action() === 'created'
                ) {

                    $createdActions++;

                } elseif (
                    $projectionResult->action() === 'updated'
                ) {

                    $updatedActions++;

                } elseif (
                    $projectionResult->action() === 'skipped'
                ) {

                    $skippedActions++;
                }


                $projection =
                    $projectionResult->data();


                /*
                |--------------------------------------------------------------------------
                | Parent Verification
                |--------------------------------------------------------------------------
                */

                $parent =
                    $projection['parent']
                    ?? null;

                if (
                    ! is_array($parent)
                ) {

                    $failures[] = [
                        'message' =>
                            'Projection parent is missing or invalid.',

                        'identity' =>
                            $canonical['identity']
                            ?? [],
                    ];

                    $failedProducts++;

                    continue;
                }


                if (
                    ($parent['type'] ?? null)
                    === 'variable'
                ) {

                    $variableParents++;

                } else {

                    $failures[] = [
                        'message' =>
                            'Projection parent type is not variable.',

                        'identity' =>
                            $canonical['identity']
                            ?? [],
                    ];

                    $failedProducts++;
                }


                /*
                |--------------------------------------------------------------------------
                | Parent Identity Verification
                |--------------------------------------------------------------------------
                */

                $parentIdentity =
                    $parent['identity']
                    ?? [];

                if (
                    ! is_array($parentIdentity)
                    || ! is_string(
                        $parentIdentity['supplier_product_id']
                        ?? null
                    )
                    || $parentIdentity['supplier_product_id'] === ''
                    || ! is_string(
                        $parentIdentity['supplier_product_code']
                        ?? null
                    )
                    || $parentIdentity['supplier_product_code'] === ''
                ) {

                    $missingParentIdentity++;

                    $failures[] = [
                        'message' =>
                            'Projection parent identity is incomplete.',

                        'identity' =>
                            $canonical['identity']
                            ?? [],
                    ];
                }


                /*
                |--------------------------------------------------------------------------
                | Decoupled Product Preservation
                |--------------------------------------------------------------------------
                */

                $hierarchy =
                    $parent['hierarchy']
                    ?? [];

                if (
                    is_array($hierarchy)
                    && ! empty(
                        $hierarchy['decoupled']
                        ?? false
                    )
                ) {

                    $decoupledProducts++;
                }


                /*
                |--------------------------------------------------------------------------
                | Parent Attributes
                |--------------------------------------------------------------------------
                */

                $classification =
                    $parent['classification']
                    ?? [];

                $attributes =
                    is_array($classification)
                        ? (
                            $classification['attributes']
                            ?? []
                        )
                        : [];

                if (
                    is_array($attributes)
                    && ! empty($attributes)
                ) {

                    $attributeProducts++;
                }


                /*
                |--------------------------------------------------------------------------
                | Variant Verification
                |--------------------------------------------------------------------------
                */

                $variants =
                    $projection['variants']
                    ?? [];

                if (
                    ! is_array($variants)
                    || $variants === []
                ) {

                    $failures[] = [
                        'message' =>
                            'Projection contains no variants.',

                        'identity' =>
                            $canonical['identity']
                            ?? [],
                    ];

                    $failedProducts++;

                    continue;
                }


                $variantCount =
                    count($variants);


                if ($variantCount === 1) {

                    $singleVariantProducts++;

                } elseif ($variantCount > 1) {

                    $multipleVariantProducts++;
                }


                foreach (
                    $variants as $variant
                ) {

                    if (
                        ! is_array($variant)
                    ) {

                        $missingVariantIdentity++;

                        $failures[] = [
                            'message' =>
                                'Projection variant is not an array.',

                            'identity' =>
                                $canonical['identity']
                                ?? [],
                        ];

                        continue;
                    }


                    if (
    ($variant['type'] ?? null)
    !== 'variation'
) {

    $failures[] = [
        'message' =>
            'Projection variant type is not variation.',

        'identity' =>
            $variant['identity']
            ?? [],
    ];

    $failedProducts++;
}


                    $variantIdentity =
                        $variant['identity']
                        ?? [];


                    $fullCode =
                        $variantIdentity['full_code']
                        ?? null;

                    $sku =
                        $variant['sku']
                        ?? null;


                    /*
                    |--------------------------------------------------------------------------
                    | Variant Identity
                    |--------------------------------------------------------------------------
                    */

                    if (
                        ! is_string($fullCode)
                        || $fullCode === ''
                    ) {

                        $missingVariantIdentity++;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | SKU ↔ fullCode
                    |--------------------------------------------------------------------------
                    */

                    if (
                        ! is_string($sku)
                        || $sku === ''
                        || $sku !== $fullCode
                    ) {

                        $skuMismatches++;

                        $skuMismatchDetails[] = [
                            'full_code' => $fullCode,
                            'sku' => $sku,
                        ];
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Variant Attributes
                    |--------------------------------------------------------------------------
                    */

                    $variantAttributes =
                        $variant['attributes']
                        ?? [];

                    if (
                        is_array($variantAttributes)
                        && ! empty($variantAttributes)
                    ) {

                        $attributeVariants++;
                    }


                    $variationChildren++;
                }


                /*
                |--------------------------------------------------------------------------
                | BlackPrint Ownership
                |--------------------------------------------------------------------------
                */

                $parentMeta =
                    $parent['meta']
                    ?? [];

                if (
                    ($parentMeta['_blackprint_managed'] ?? null)
                    !== 'yes'
                    || ($parentMeta['_blackprint_supplier'] ?? null)
                    === null
                    || ($parentMeta['_blackprint_product_id'] ?? null)
                    === null
                    || ($parentMeta['_blackprint_product_code'] ?? null)
                    === null
                ) {

                    $ownershipFailures++;

                    $ownershipFailureDetails[] = [
                        'type' => 'parent',
                        'identity' =>
                            $parentIdentity,
                    ];
                }


                foreach (
                    $variants as $variant
                ) {

                    if (
                        ! is_array($variant)
                    ) {
                        continue;
                    }

                    $variantMeta =
                        $variant['meta']
                        ?? [];

                    if (
                        ($variantMeta['_blackprint_managed'] ?? null)
                        !== 'yes'
                        || ($variantMeta['_blackprint_supplier'] ?? null)
                        === null
                        || ($variantMeta['_blackprint_variant_code'] ?? null)
                        === null
                        || ($variantMeta['_blackprint_simple_code'] ?? null)
                        === null
                    ) {

                        $ownershipFailures++;

                        $ownershipFailureDetails[] = [
                            'type' => 'variant',
                            'identity' =>
                                $variant['identity']
                                ?? [],
                        ];
                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Final Verification Status
            |--------------------------------------------------------------------------
            */

            $status = (
                $failedProducts === 0
                && $canonicalProducts === $plannedProducts
                && $createdActions === 0
                && $updatedActions === 0
                && $skippedActions === 0
                && $missingParentIdentity === 0
                && $missingVariantIdentity === 0
                && $skuMismatches === 0
                && $ownershipFailures === 0
            )
                ? 'PASS'
                : 'FAILED';


            /*
            |--------------------------------------------------------------------------
            | Human-Readable Report
            |--------------------------------------------------------------------------
            */

            $output = [];

            $output[] =
                'TEST VERSION: WOOCOMMERCE PROJECTION v1';

            $output[] = '';

            $output[] =
                'BlackPrint OS — WooCommerce Projection Verification';

            $output[] =
                str_repeat('=', 64);

            $output[] = '';

            $output[] = 'SNAPSHOT';

            $output[] =
                str_repeat('-', 64);

            $output[] =
                'UUID: ' .
                $snapshotUuid;

            $output[] = '';

            $output[] = 'PROJECTION';

            $output[] =
                str_repeat('-', 64);

            $output[] =
                'Canonical products:       ' .
                $canonicalProducts;

            $output[] =
                'Planned projections:      ' .
                $plannedProducts;

            $output[] =
                'Failed projections:      ' .
                $failedProducts;

            $output[] =
                'Created actions:          ' .
                $createdActions;

            $output[] =
                'Updated actions:          ' .
                $updatedActions;

            $output[] =
                'Skipped actions:          ' .
                $skippedActions;

            $output[] =
                'Status:                   ' .
                $status;

            $output[] = '';

            $output[] = 'PARENT STRUCTURE';

            $output[] =
                str_repeat('-', 64);

            $output[] =
                'Variable parents:         ' .
                $variableParents;

            $output[] =
                'Missing parent identity:  ' .
                $missingParentIdentity;

            $output[] =
                'Decoupled products:       ' .
                $decoupledProducts;

            $output[] = '';

            $output[] = 'VARIANT STRUCTURE';

            $output[] =
                str_repeat('-', 64);

            $output[] =
                'Variation children:       ' .
                $variationChildren;

            $output[] =
                'Single-variant products:  ' .
                $singleVariantProducts;

            $output[] =
                'Multiple-variant products:' .
                $multipleVariantProducts;

            $output[] =
                'Missing variant identity: ' .
                $missingVariantIdentity;

            $output[] =
                'SKU ↔ fullCode mismatches:' .
                $skuMismatches;

            $output[] = '';

            $output[] = 'ATTRIBUTE COVERAGE';

            $output[] =
                str_repeat('-', 64);

            $output[] =
                'Products with attributes: ' .
                $attributeProducts;

            $output[] =
                'Variants with attributes:  ' .
                $attributeVariants;

            $output[] = '';

            $output[] = 'OWNERSHIP';

            $output[] =
                str_repeat('-', 64);

            $output[] =
                'Ownership failures:        ' .
                $ownershipFailures;

            $output[] = '';

            $output[] =
                'READ-ONLY GUARANTEE';

            $output[] =
                str_repeat('-', 64);

            $output[] =
                'WooCommerce mutations:     0';

            $output[] =
                'WooCommerce writer used:   NO';

            $output[] = '';

            /*
            |--------------------------------------------------------------------------
            | Failure Details
            |--------------------------------------------------------------------------
            */

            if (! empty($failures)) {

                $output[] =
                    'PROJECTION FAILURES';

                $output[] =
                    str_repeat('-', 64);

                foreach (
                    array_slice($failures, 0, 50)
                    as $failure
                ) {

                    $identity =
                        $failure['identity']
                        ?? [];

                    $output[] =
                        '- ' .
                        ($failure['message'] ?? 'Unknown failure')
                        .
                        ' | product=' .
                        (
                            $identity['supplier_product_code']
                            ?? $identity['supplier_product_id']
                            ?? 'unknown'
                        );
                }

                if (count($failures) > 50) {

                    $output[] =
                        '... ' .
                        (count($failures) - 50) .
                        ' additional failures omitted.';
                }

                $output[] = '';
            }


            if (! empty($skuMismatchDetails)) {

                $output[] =
                    'SKU / FULLCODE MISMATCHES';

                $output[] =
                    str_repeat('-', 64);

                foreach (
                    array_slice($skuMismatchDetails, 0, 50)
                    as $mismatch
                ) {

                    $output[] =
                        '- fullCode=' .
                        (
                            $mismatch['full_code']
                            ?? ''
                        )
                        .
                        ' sku=' .
                        (
                            $mismatch['sku']
                            ?? ''
                        );
                }

                $output[] = '';
            }


            if (! empty($ownershipFailureDetails)) {

                $output[] =
                    'OWNERSHIP FAILURES';

                $output[] =
                    str_repeat('-', 64);

                foreach (
                    array_slice($ownershipFailureDetails, 0, 50)
                    as $failure
                ) {

                    $output[] =
                        '- type=' .
                        (
                            $failure['type']
                            ?? 'unknown'
                        )
                        .
                        ' identity=' .
                        wp_json_encode(
                            $failure['identity']
                            ?? []
                        );
                }

                $output[] = '';
            }


            $output[] =
                str_repeat('=', 64);

            $output[] =
                'FINAL STATUS: ' .
                $status;


            /*
            |--------------------------------------------------------------------------
            | Output
            |--------------------------------------------------------------------------
            */

            echo '<pre>';

            echo esc_html(
                implode(
                    "\n",
                    $output
                )
            );

            echo '</pre>';

        } catch (
            Throwable $e
        ) {

            echo '<pre>';

            echo esc_html(
                'Projection test exception: ' .
                $e->getMessage()
            );

            echo "\n";

            echo esc_html(
                $e->getFile() .
                ':' .
                $e->getLine()
            );

            echo '</pre>';
        }
    }
);
