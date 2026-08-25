<?php

defined('ABSPATH') || exit;

/*
|--------------------------------------------------------------------------
| BlackPrint OS — Snapshot Normalization Verification Test
|--------------------------------------------------------------------------
|
| This test:
|
| 1. Loads an existing immutable snapshot.
| 2. Restores its raw payload.
| 3. Resolves the supplier normalizer.
| 4. Normalizes all supplier records.
| 5. Inspects the canonical product collection.
| 6. Reports normalization statistics and coverage.
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
| - Apply business rules.
| - Write to WooCommerce.
|
*/

add_action(
    'admin_init',
    static function (): void {

        /*
        |--------------------------------------------------------------------------
        | Prevent accidental repeated execution
        |--------------------------------------------------------------------------
        */

        if (
            ! isset($_GET['bp_test_normalization'])
            || $_GET['bp_test_normalization'] !== '1'
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
            | Basic Result Information
            |--------------------------------------------------------------------------
            */

            $sourceRecords =
                $result->sourceRecords();

            $normalized =
                $result->normalized();

            $skipped =
                $result->skipped();

            $failed =
                $result->failed();

            $errors =
                $result->errors();

            $metadata =
                $result->metadata();

            $products =
                $result->products();


            /*
            |--------------------------------------------------------------------------
            | Coverage Statistics
            |--------------------------------------------------------------------------
            |
            | These statistics inspect the already-created canonical
            | products. They do not modify them.
            |
            */

            $productsWithCategories = 0;
            $productsWithImages = 0;
            $productsWithColourImages = 0;
            $productsWithVariants = 0;
            $productsWithBrandingTemplates = 0;
            $productsWithBrandingGuide = 0;
            $productsWithRelationships = 0;

            $productsWithIdentity = 0;

            $duplicateSupplierProductIds = [];

            $seenSupplierProductIds = [];


            if ($products !== null) {

                foreach ($products->all() as $product) {

                    /*
                    |--------------------------------------------------------------------------
                    | Convert Individual Canonical Product
                    |--------------------------------------------------------------------------
                    */

                    $data =
                        $product->toArray();


                    /*
                    |--------------------------------------------------------------------------
                    | Classification
                    |--------------------------------------------------------------------------
                    */

                    $classification =
                        $data['classification']
                        ?? [];

                    $categories =
                        $classification['categories']
                        ?? [];

                    if (
                        is_array($categories)
                        && ! empty($categories)
                    ) {
                        $productsWithCategories++;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Media
                    |--------------------------------------------------------------------------
                    */

                    $media =
                        $data['media']
                        ?? [];

                    $images =
                        $media['images']
                        ?? [];

                    $colourImages =
                        $media['colour_images']
                        ?? [];

                    if (
                        is_array($images)
                        && ! empty($images)
                    ) {
                        $productsWithImages++;
                    }

                    if (
                        is_array($colourImages)
                        && ! empty($colourImages)
                    ) {
                        $productsWithColourImages++;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Variants
                    |--------------------------------------------------------------------------
                    */

                    $hierarchy =
                        $data['hierarchy']
                        ?? [];

                    $variants =
                        $hierarchy['variants']
                        ?? [];

                    if (
                        is_array($variants)
                        && ! empty($variants)
                    ) {
                        $productsWithVariants++;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Branding
                    |--------------------------------------------------------------------------
                    */

                    $branding =
                        $data['branding']
                        ?? [];

                    $templates =
                        $branding['templates']
                        ?? [];

                    $brandingGuide =
                        $branding['full_branding_guide']
                        ?? null;

                    if (
                        is_array($templates)
                        && ! empty($templates)
                    ) {
                        $productsWithBrandingTemplates++;
                    }

                    if (
                        is_string($brandingGuide)
                        && $brandingGuide !== ''
                    ) {
                        $productsWithBrandingGuide++;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Relationships
                    |--------------------------------------------------------------------------
                    */

                    $relationships =
                        $data['relationships']
                        ?? [];

                    $relationshipFound = false;

                    foreach (
                        [
                            'companion_codes',
                            'related_codes',
                            'matching_codes',
                            'grouping_codes',
                        ] as $relationshipType
                    ) {

                        $values =
                            $relationships[$relationshipType]
                            ?? [];

                        if (
                            is_array($values)
                            && ! empty($values)
                        ) {
                            $relationshipFound = true;
                            break;
                        }
                    }

                    if ($relationshipFound) {
                        $productsWithRelationships++;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Identity
                    |--------------------------------------------------------------------------
                    */

                    $identity =
                        $data['identity']
                        ?? [];

                    $supplierProductId =
                        $identity['supplier_product_id']
                        ?? null;

                    if (
                        is_string($supplierProductId)
                        && $supplierProductId !== ''
                    ) {

                        $productsWithIdentity++;

                        if (
                            isset(
                                $seenSupplierProductIds[
                                    $supplierProductId
                                ]
                            )
                        ) {

                            $duplicateSupplierProductIds[] =
                                $supplierProductId;

                        } else {

                            $seenSupplierProductIds[
                                $supplierProductId
                            ] = true;
                        }
                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Determine Verification Status
            |--------------------------------------------------------------------------
            */

            $status =
                $result->success()
                ? 'PASS'
                : 'FAILED';


            /*
            |--------------------------------------------------------------------------
            | Build Human-Readable Report
            |--------------------------------------------------------------------------
            */

            $output = [];

            $output[] =
                'BlackPrint OS — Snapshot Normalization Verification';

            $output[] =
                str_repeat(
                    '=',
                    58
                );

            $output[] = '';

            $output[] = 'SNAPSHOT';
            $output[] =
                str_repeat(
                    '-',
                    58
                );

            $output[] =
                'UUID: ' .
                ($metadata['snapshot_uuid'] ?? $snapshotUuid);

            $output[] =
                'Supplier: ' .
                ($metadata['supplier'] ?? 'unknown');

            $output[] =
                'Resource: ' .
                ($metadata['resource'] ?? 'unknown');

            $output[] =
                'Snapshot records: ' .
                ($metadata['snapshot_records_count'] ?? 'unknown');

            $output[] = '';

            $output[] = 'NORMALIZATION';
            $output[] =
                str_repeat(
                    '-',
                    58
                );

            $output[] =
                'Source records:       ' .
                $sourceRecords;

            $output[] =
                'Normalized:           ' .
                $normalized;

            $output[] =
                'Skipped:              ' .
                $skipped;

            $output[] =
                'Failed:               ' .
                $failed;

            $output[] =
                'Status:               ' .
                $status;

            $output[] = '';

            $output[] = 'CANONICAL PRODUCT COVERAGE';
            $output[] =
                str_repeat(
                    '-',
                    58
                );

            $output[] =
                'Products with identity:          ' .
                $productsWithIdentity;

            $output[] =
                'Products with categories:        ' .
                $productsWithCategories;

            $output[] =
                'Products with images:            ' .
                $productsWithImages;

            $output[] =
                'Products with colour images:     ' .
                $productsWithColourImages;

            $output[] =
                'Products with variants:          ' .
                $productsWithVariants;

            $output[] =
                'Products with branding templates:' .
                $productsWithBrandingTemplates;

            $output[] =
                'Products with branding guide:    ' .
                $productsWithBrandingGuide;

            $output[] =
                'Products with relationships:     ' .
                $productsWithRelationships;

            $output[] = '';

            $output[] = 'IDENTITY CHECK';
            $output[] =
                str_repeat(
                    '-',
                    58
                );

            $output[] =
                'Unique supplier product IDs:     ' .
                count($seenSupplierProductIds);

            $output[] =
                'Duplicate supplier product IDs:  ' .
                count(
                    array_unique(
                        $duplicateSupplierProductIds
                    )
                );

            $output[] = '';

            $output[] = 'ERRORS';
            $output[] =
                str_repeat(
                    '-',
                    58
                );

            if (empty($errors)) {

                $output[] =
                    'None';

            } else {

                foreach ($errors as $error) {

                    $output[] =
                        '- ' .
                        $error;
                }
            }

            $output[] = '';

            $output[] = 'FIRST CANONICAL PRODUCT SAMPLE';
            $output[] =
                str_repeat(
                    '-',
                    58
                );


            /*
            |--------------------------------------------------------------------------
            | First Product Sample
            |--------------------------------------------------------------------------
            */

            if (
                $products !== null
                && ! $products->isEmpty()
            ) {

                $firstProduct =
                    $products->get(0);

                if ($firstProduct !== null) {

                    $output[] =
                        print_r(
                            $firstProduct->toArray(),
                            true
                        );
                }

            } else {

                $output[] =
                    'No canonical products were produced.';
            }


            /*
            |--------------------------------------------------------------------------
            | Output
            |--------------------------------------------------------------------------
            */

            wp_die(
                '<pre>' .
                esc_html(
                    implode(
                        "\n",
                        $output
                    )
                ) .
                '</pre>'
            );

        } catch (\Throwable $exception) {

            wp_die(
                '<pre>' .
                esc_html(
                    'Normalization failed: ' .
                    $exception->getMessage()
                ) .
                '</pre>'
            );
        }
    }
);