<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Projection\WooCommerce;

defined('ABSPATH') || exit;

/**
 * WooCommerce Ownership Committer.
 *
 * Step 5B — Controlled Adoption / Ownership Commit.
 *
 * IMPORTANT:
 *
 * This class consumes ONLY the verified adoption mappings produced by
 * the adoption mapping / verification phase.
 *
 * It does not perform reconciliation.
 * It does not discover products.
 * It does not discover variants.
 * It does not create WooCommerce products.
 * It does not create WooCommerce variations.
 *
 * Its sole responsibility is to establish BlackPrint ownership metadata
 * on explicitly approved existing WooCommerce records.
 *
 * Ownership is intentionally separate from projection execution.
 */
final class WooCommerceOwnershipCommitter
{
    /*
    |--------------------------------------------------------------------------
    | Expected ownership constants.
    |--------------------------------------------------------------------------
    */

    private const SUPPLIER = 'amrod';

    private const MANAGED = 'yes';

    /*
    |--------------------------------------------------------------------------
    | Locked Step 5B expectations.
    |--------------------------------------------------------------------------
    */

    private const EXPECTED_APPROVED_MAPPINGS = 3710;

    private const EXPECTED_PARENT_OWNERSHIP = 3710;

    private const EXPECTED_VARIANT_OWNERSHIP = 20265;

    /*
    |--------------------------------------------------------------------------
    | Commit.
    |--------------------------------------------------------------------------
    */

    /**
     * Commit BlackPrint ownership for verified adoption mappings.
     *
     * No WooCommerce mutation occurs unless the complete mapping set
     * passes the final pre-write safety validation.
     *
     * @param array<int|string, array<string, mixed>> $adoptionMappings
     *
     * @return array<string, mixed>
     */
    public function commit(array $adoptionMappings): array
    {
        /*
         * --------------------------------------------------------------
         * Phase 1 — Final pre-write safety validation.
         * --------------------------------------------------------------
         */

        $validation = $this->validateMappings($adoptionMappings);

        if (!$validation['pass']) {
            return [
                'success' => false,
                'status'  => 'ABORTED',
                'phase'   => 'PRE_WRITE_VALIDATION',
                'message' => 'Step 5B aborted. Final ownership safety validation failed.',
                'validation' => $validation,
                'audit' => [
                    'parents_written'  => 0,
                    'variants_written' => 0,
                    'parents_already_managed' => 0,
                    'variants_already_managed' => 0,
                    'parent_conflicts' => 0,
                    'variant_conflicts' => 0,
                    'write_errors' => 0,
                ],
            ];
        }

        /*
         * --------------------------------------------------------------
         * Phase 2 — Detect ownership conflicts BEFORE any write.
         * --------------------------------------------------------------
         *
         * We intentionally perform this complete conflict scan before
         * mutating anything.
         *
         *Therefore a detected ownership conflict cannot result in a partially
            committed adoption.

            Database write failures occurring after the safety checks are handled
            separately and may require post-write recovery.
         */

        $ownershipState = $this->inspectExistingOwnership(
            $adoptionMappings
        );

        if (!$ownershipState['pass']) {
            return [
                'success' => false,
                'status'  => 'ABORTED',
                'phase'   => 'OWNERSHIP_SAFETY_CHECK',
                'message' => 'Step 5B aborted. Existing BlackPrint ownership conflicts were detected. No ownership was written.',
                'validation' => $validation,
                'ownership' => $ownershipState,
                'audit' => [
                    'parents_written'  => 0,
                    'variants_written' => 0,
                    'parents_already_managed' => $ownershipState[
                        'parents_already_managed'
                    ],
                    'variants_already_managed' => $ownershipState[
                        'variants_already_managed'
                    ],
                    'parent_conflicts' => $ownershipState[
                        'parent_conflicts'
                    ],
                    'variant_conflicts' => $ownershipState[
                        'variant_conflicts'
                    ],
                    'write_errors' => 0,
                ],
            ];
        }

        /*
         * --------------------------------------------------------------
         * Phase 3 — Write ownership.
         * --------------------------------------------------------------
         */

        $parentsWritten = 0;
        $variantsWritten = 0;

        $parentsAlreadyManaged = 0;
        $variantsAlreadyManaged = 0;

        $writeErrors = [];

        foreach ($adoptionMappings as $productId => $mapping) {

            $productId = (int) $productId;

            $canonicalProductId =
                (string) $mapping['canonical_product_id'];

            $canonicalProductCode =
                (string) $mapping['canonical_product_code'];

            /*
             * Parent ownership.
             */

            $existingParentOwnership =
                $this->hasExactParentOwnership(
                    $productId,
                    $canonicalProductId,
                    $canonicalProductCode
                );

            if ($existingParentOwnership) {

                $parentsAlreadyManaged++;

            } else {

                $parentWriteResult =
                    $this->writeParentOwnership(
                        $productId,
                        $canonicalProductId,
                        $canonicalProductCode
                    );

                if (!$parentWriteResult['success']) {

                    $writeErrors[] = [
                        'product_id' => $productId,
                        'scope'     => 'parent',
                        'error'     => $parentWriteResult['error'],
                    ];

                    continue;
                }

                $parentsWritten++;
            }

            /*
             * Explicit variant ownership.
             */

            $variants =
                isset($mapping['variants'])
                && is_array($mapping['variants'])
                    ? $mapping['variants']
                    : [];

            foreach ($variants as $variant) {

                $variationId =
                    isset($variant['woocommerce_variation_id'])
                        ? (int) $variant[
                            'woocommerce_variation_id'
                        ]
                        : 0;

                /*
                 * Simple-product mappings intentionally have no
                 * WooCommerce variation ID.
                 *
                 * Their ownership is represented by the parent
                 * ownership record and therefore require no child write.
                 */

                if ($variationId <= 0) {
                    continue;
                }

                $canonicalVariantCode =
                    isset($variant['canonical_variant_code'])
                        ? trim(
                            (string)
                            $variant['canonical_variant_code']
                        )
                        : '';

                if (
                    $this->hasExactVariantOwnership(
                        $variationId,
                        $canonicalVariantCode
                    )
                ) {

                    $variantsAlreadyManaged++;

                    continue;
                }

                $variantWriteResult =
                    $this->writeVariantOwnership(
                        $variationId,
                        $canonicalVariantCode
                    );

                if (!$variantWriteResult['success']) {

                    $writeErrors[] = [
                        'product_id'   => $productId,
                        'variation_id' => $variationId,
                        'scope'        => 'variant',
                        'error'        => $variantWriteResult['error'],
                    ];

                    continue;
                }

                $variantsWritten++;
            }
        }

        /*
         * --------------------------------------------------------------
         * Phase 4 — Immediate post-write verification.
         * --------------------------------------------------------------
         */

        $postWriteVerification =
            $this->verifyCommittedOwnership(
                $adoptionMappings
            );

        $success =
            count($writeErrors) === 0
            && $postWriteVerification['pass'];

        return [
            'success' => $success,
            'status'  => $success ? 'PASS' : 'FAIL',
            'phase'   => 'COMMIT',
            'message' => $success
                ? 'Step 5B ownership commit completed successfully.'
                : 'Step 5B ownership commit completed with errors or failed post-write verification.',
            'validation' => $validation,
            'ownership' => $ownershipState,
            'audit' => [
                'parents_written' => $parentsWritten,
                'variants_written' => $variantsWritten,
                'parents_already_managed' => $parentsAlreadyManaged,
                'variants_already_managed' => $variantsAlreadyManaged,
                'parent_conflicts' => 0,
                'variant_conflicts' => 0,
                'write_errors' => count($writeErrors),
            ],
            'write_errors' => $writeErrors,
            'post_write_verification' => $postWriteVerification,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Final mapping validation.
    |--------------------------------------------------------------------------
    */

    /**
     * @param array<int|string, array<string, mixed>> $adoptionMappings
     *
     * @return array<string, mixed>
     */
    private function validateMappings(array $adoptionMappings): array
    {
        $errors = [];

        if (
            count($adoptionMappings)
            !== self::EXPECTED_APPROVED_MAPPINGS
        ) {
            $errors[] = [
                'reason' => 'INVALID_APPROVED_MAPPING_COUNT',
                'expected' => self::EXPECTED_APPROVED_MAPPINGS,
                'actual' => count($adoptionMappings),
            ];
        }

        $parentCount = 0;
        $variantCount = 0;

        $canonicalProducts = [];
        $canonicalVariants = [];

        foreach ($adoptionMappings as $productId => $mapping) {

            $productId = (int) $productId;

            if ($productId <= 0) {
                $errors[] = [
                    'product_id' => $productId,
                    'reason' => 'INVALID_WOOCOMMERCE_PRODUCT_ID',
                ];

                continue;
            }

            if (
                !is_array($mapping)
                || ($mapping['decision'] ?? '') !== 'ADOPT'
            ) {
                $errors[] = [
                    'product_id' => $productId,
                    'reason' => 'MAPPING_IS_NOT_APPROVED_ADOPT',
                ];

                continue;
            }

            $mappedProductId =
                isset($mapping['woocommerce_product_id'])
                    ? (int) $mapping[
                        'woocommerce_product_id'
                    ]
                    : 0;

            if ($mappedProductId !== $productId) {
                $errors[] = [
                    'product_id' => $productId,
                    'mapped_product_id' => $mappedProductId,
                    'reason' =>
                        'MAPPING_REFERENCES_DIFFERENT_WOOCOMMERCE_PRODUCT',
                ];

                continue;
            }

            $canonicalProductId =
                isset($mapping['canonical_product_id'])
                    ? trim(
                        (string)
                        $mapping['canonical_product_id']
                    )
                    : '';

            if ($canonicalProductId === '') {
                $errors[] = [
                    'product_id' => $productId,
                    'reason' => 'MISSING_CANONICAL_PRODUCT_ID',
                ];

                continue;
            }

            if (isset($canonicalProducts[$canonicalProductId])) {
                $errors[] = [
                    'product_id' => $productId,
                    'canonical_product_id' => $canonicalProductId,
                    'existing_product_id' =>
                        $canonicalProducts[
                            $canonicalProductId
                        ],
                    'reason' =>
                        'DUPLICATE_CANONICAL_PRODUCT_CLAIM',
                ];

                continue;
            }

            $canonicalProducts[$canonicalProductId] =
                $productId;

            $parentCount++;

            /*
             * Parent must actually exist.
             */

            if (!get_post($productId)) {
                $errors[] = [
                    'product_id' => $productId,
                    'reason' => 'WOOCOMMERCE_PARENT_DOES_NOT_EXIST',
                ];

                continue;
            }

            /*
             * Validate explicitly supplied variants.
             */

            $variants =
                isset($mapping['variants'])
                && is_array($mapping['variants'])
                    ? $mapping['variants']
                    : [];

            foreach ($variants as $variant) {

                $variationId =
                    isset($variant['woocommerce_variation_id'])
                        ? (int) $variant[
                            'woocommerce_variation_id'
                        ]
                        : 0;

                $canonicalVariantCode =
                    isset($variant['canonical_variant_code'])
                        ? trim(
                            (string)
                            $variant['canonical_variant_code']
                        )
                        : '';

                /*
                 * Simple product mapping.
                 *
                 * variation_id is deliberately null.
                 */

                if ($variationId <= 0) {

                    if ($canonicalVariantCode === '') {
                        $errors[] = [
                            'product_id' => $productId,
                            'reason' =>
                                'SIMPLE_VARIANT_MAPPING_HAS_NO_CANONICAL_VARIANT_CODE',
                        ];
                    }

                    continue;
                }

                if ($canonicalVariantCode === '') {
                    $errors[] = [
                        'product_id' => $productId,
                        'variation_id' => $variationId,
                        'reason' =>
                            'VARIABLE_MAPPING_HAS_NO_CANONICAL_VARIANT_CODE',
                    ];

                    continue;
                }

                if (!get_post($variationId)) {
                    $errors[] = [
                        'product_id' => $productId,
                        'variation_id' => $variationId,
                        'reason' =>
                            'WOOCOMMERCE_VARIATION_DOES_NOT_EXIST',
                    ];

                    continue;
                }

                $variationParentId =
                    (int) wp_get_post_parent_id(
                        $variationId
                    );

                if ($variationParentId !== $productId) {
                    $errors[] = [
                        'product_id' => $productId,
                        'variation_id' => $variationId,
                        'actual_parent_id' =>
                            $variationParentId,
                        'reason' =>
                            'WOOCOMMERCE_VARIATION_HAS_WRONG_PARENT',
                    ];

                    continue;
                }

                if (
                    isset(
                        $canonicalVariants[
                            $canonicalVariantCode
                        ]
                    )
                ) {
                    $existing =
                        $canonicalVariants[
                            $canonicalVariantCode
                        ];

                    if (
                        $existing['product_id']
                        !== $productId
                        ||
                        $existing['variation_id']
                        !== $variationId
                    ) {
                        $errors[] = [
                            'product_id' => $productId,
                            'variation_id' => $variationId,
                            'canonical_variant_code' =>
                                $canonicalVariantCode,
                            'existing' => $existing,
                            'reason' =>
                                'DUPLICATE_CANONICAL_VARIANT_CLAIM',
                        ];

                        continue;
                    }
                }

                $canonicalVariants[
                    $canonicalVariantCode
                ] = [
                    'product_id' => $productId,
                    'variation_id' => $variationId,
                ];

                $variantCount++;
            }
        }

        if (
            $parentCount
            !== self::EXPECTED_PARENT_OWNERSHIP
        ) {
            $errors[] = [
                'reason' =>
                    'INVALID_PARENT_OWNERSHIP_COUNT',
                'expected' =>
                    self::EXPECTED_PARENT_OWNERSHIP,
                'actual' =>
                    $parentCount,
            ];
        }

        if (
            $variantCount
            !== self::EXPECTED_VARIANT_OWNERSHIP
        ) {
            $errors[] = [
                'reason' =>
                    'INVALID_MAPPED_VARIANT_OWNERSHIP_COUNT',
                'expected' =>
                    self::EXPECTED_VARIANT_OWNERSHIP,
                'actual' =>
                    $variantCount,
            ];
        }

        return [
            'pass' => count($errors) === 0,
            'approved_mappings' => count($adoptionMappings),
            'parent_mappings' => $parentCount,
            'variant_mappings' => $variantCount,
            'errors' => $errors,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Existing ownership inspection.
    |--------------------------------------------------------------------------
    */

    /**
     * @param array<int|string, array<string, mixed>> $adoptionMappings
     *
     * @return array<string, mixed>
     */
    private function inspectExistingOwnership(
        array $adoptionMappings
    ): array {
        $parentConflicts = [];
        $variantConflicts = [];

        $parentsAlreadyManaged = 0;
        $variantsAlreadyManaged = 0;

        foreach ($adoptionMappings as $productId => $mapping) {

            $productId = (int) $productId;

            $canonicalProductId =
                (string) $mapping['canonical_product_id'];

            $canonicalProductCode =
                (string) $mapping['canonical_product_code'];

            $existingManaged =
                (string) get_post_meta(
                    $productId,
                    '_blackprint_managed',
                    true
                );

            $existingSupplier =
                (string) get_post_meta(
                    $productId,
                    '_blackprint_supplier',
                    true
                );

            $existingProductId =
                (string) get_post_meta(
                    $productId,
                    '_blackprint_product_id',
                    true
                );

            $existingProductCode =
                (string) get_post_meta(
                    $productId,
                    '_blackprint_product_code',
                    true
                );

            $hasOwnership =
                $existingManaged !== ''
                || $existingSupplier !== ''
                || $existingProductId !== ''
                || $existingProductCode !== '';

            if (!$hasOwnership) {
                /*
                 * Safe to adopt.
                 */
            } elseif (
                $existingManaged === self::MANAGED
                && $existingSupplier === self::SUPPLIER
                && $existingProductId === $canonicalProductId
                && $existingProductCode === $canonicalProductCode
            ) {
                $parentsAlreadyManaged++;

            } else {

                $parentConflicts[] = [
                    'woocommerce_product_id' => $productId,
                    'expected' => [
                        '_blackprint_managed' =>
                            self::MANAGED,
                        '_blackprint_supplier' =>
                            self::SUPPLIER,
                        '_blackprint_product_id' =>
                            $canonicalProductId,
                        '_blackprint_product_code' =>
                            $canonicalProductCode,
                    ],
                    'existing' => [
                        '_blackprint_managed' =>
                            $existingManaged,
                        '_blackprint_supplier' =>
                            $existingSupplier,
                        '_blackprint_product_id' =>
                            $existingProductId,
                        '_blackprint_product_code' =>
                            $existingProductCode,
                    ],
                ];

                continue;
            }

            $variants =
                isset($mapping['variants'])
                && is_array($mapping['variants'])
                    ? $mapping['variants']
                    : [];

            foreach ($variants as $variant) {

                $variationId =
                    isset($variant['woocommerce_variation_id'])
                        ? (int) $variant[
                            'woocommerce_variation_id'
                        ]
                        : 0;

                if ($variationId <= 0) {
                    continue;
                }

                $canonicalVariantCode =
                    isset($variant['canonical_variant_code'])
                        ? trim(
                            (string)
                            $variant['canonical_variant_code']
                        )
                        : '';

                $existingManaged =
                    (string) get_post_meta(
                        $variationId,
                        '_blackprint_managed',
                        true
                    );

                $existingSupplier =
                    (string) get_post_meta(
                        $variationId,
                        '_blackprint_supplier',
                        true
                    );

                $existingVariantCode =
                    (string) get_post_meta(
                        $variationId,
                        '_blackprint_variant_code',
                        true
                    );

                $hasOwnership =
                    $existingManaged !== ''
                    || $existingSupplier !== ''
                    || $existingVariantCode !== '';

                if (!$hasOwnership) {
                    continue;
                }

                if (
                    $existingManaged === self::MANAGED
                    && $existingSupplier === self::SUPPLIER
                    && $existingVariantCode ===
                        $canonicalVariantCode
                ) {
                    $variantsAlreadyManaged++;

                } else {

                    $variantConflicts[] = [
                        'woocommerce_variation_id' =>
                            $variationId,
                        'expected' => [
                            '_blackprint_managed' =>
                                self::MANAGED,
                            '_blackprint_supplier' =>
                                self::SUPPLIER,
                            '_blackprint_variant_code' =>
                                $canonicalVariantCode,
                        ],
                        'existing' => [
                            '_blackprint_managed' =>
                                $existingManaged,
                            '_blackprint_supplier' =>
                                $existingSupplier,
                            '_blackprint_variant_code' =>
                                $existingVariantCode,
                        ],
                    ];
                }
            }
        }

        return [
            'pass' =>
                count($parentConflicts) === 0
                && count($variantConflicts) === 0,
            'parents_already_managed' =>
                $parentsAlreadyManaged,
            'variants_already_managed' =>
                $variantsAlreadyManaged,
            'parent_conflicts' =>
                count($parentConflicts),
            'variant_conflicts' =>
                count($variantConflicts),
            'parent_conflict_details' =>
                $parentConflicts,
            'variant_conflict_details' =>
                $variantConflicts,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Parent ownership write.
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<string, mixed>
     */
    private function writeParentOwnership(
        int $productId,
        string $canonicalProductId,
        string $canonicalProductCode
    ): array {
        $updates = [
            '_blackprint_managed' => self::MANAGED,
            '_blackprint_supplier' => self::SUPPLIER,
            '_blackprint_product_id' => $canonicalProductId,
            '_blackprint_product_code' => $canonicalProductCode,
        ];

        foreach ($updates as $key => $value) {

            $result = update_post_meta(
                $productId,
                $key,
                $value
            );

            if ($result === false) {
                return [
                    'success' => false,
                    'error' => sprintf(
                        'Failed writing parent metadata key %s.',
                        $key
                    ),
                ];
            }
        }

        if (
            !$this->hasExactParentOwnership(
                $productId,
                $canonicalProductId,
                $canonicalProductCode
            )
        ) {
            return [
                'success' => false,
                'error' =>
                    'Parent ownership post-write verification failed.',
            ];
        }

        return [
            'success' => true,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Variant ownership write.
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<string, mixed>
     */
    private function writeVariantOwnership(
        int $variationId,
        string $canonicalVariantCode
    ): array {
        $updates = [
            '_blackprint_managed' => self::MANAGED,
            '_blackprint_supplier' => self::SUPPLIER,
            '_blackprint_variant_code' =>
                $canonicalVariantCode,
        ];

        foreach ($updates as $key => $value) {

            $result = update_post_meta(
                $variationId,
                $key,
                $value
            );

            if ($result === false) {
                return [
                    'success' => false,
                    'error' => sprintf(
                        'Failed writing variant metadata key %s.',
                        $key
                    ),
                ];
            }
        }

        if (
            !$this->hasExactVariantOwnership(
                $variationId,
                $canonicalVariantCode
            )
        ) {
            return [
                'success' => false,
                'error' =>
                    'Variant ownership post-write verification failed.',
            ];
        }

        return [
            'success' => true,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Ownership predicates.
    |--------------------------------------------------------------------------
    */

    private function hasExactParentOwnership(
        int $productId,
        string $canonicalProductId,
        string $canonicalProductCode
    ): bool {
        return
            (string) get_post_meta(
                $productId,
                '_blackprint_managed',
                true
            ) === self::MANAGED
            &&
            (string) get_post_meta(
                $productId,
                '_blackprint_supplier',
                true
            ) === self::SUPPLIER
            &&
            (string) get_post_meta(
                $productId,
                '_blackprint_product_id',
                true
            ) === $canonicalProductId
            &&
            (string) get_post_meta(
                $productId,
                '_blackprint_product_code',
                true
            ) === $canonicalProductCode;
    }

    private function hasExactVariantOwnership(
        int $variationId,
        string $canonicalVariantCode
    ): bool {
        return
            (string) get_post_meta(
                $variationId,
                '_blackprint_managed',
                true
            ) === self::MANAGED
            &&
            (string) get_post_meta(
                $variationId,
                '_blackprint_supplier',
                true
            ) === self::SUPPLIER
            &&
            (string) get_post_meta(
                $variationId,
                '_blackprint_variant_code',
                true
            ) === $canonicalVariantCode;
    }

    /*
    |--------------------------------------------------------------------------
    | Post-write verification.
    |--------------------------------------------------------------------------
    */

    /**
     * @param array<int|string, array<string, mixed>> $adoptionMappings
     *
     * @return array<string, mixed>
     */
    private function verifyCommittedOwnership(
        array $adoptionMappings
    ): array {
        $errors = [];

        $verifiedParents = 0;
        $verifiedVariants = 0;

        foreach ($adoptionMappings as $productId => $mapping) {

            $productId = (int) $productId;

            $canonicalProductId =
                (string) $mapping['canonical_product_id'];

            $canonicalProductCode =
                (string) $mapping['canonical_product_code'];

            if (
                !$this->hasExactParentOwnership(
                    $productId,
                    $canonicalProductId,
                    $canonicalProductCode
                )
            ) {
                $errors[] = [
                    'product_id' => $productId,
                    'reason' =>
                        'PARENT_OWNERSHIP_MISSING_OR_INCORRECT',
                ];

                continue;
            }

            $verifiedParents++;

            $variants =
                isset($mapping['variants'])
                && is_array($mapping['variants'])
                    ? $mapping['variants']
                    : [];

            foreach ($variants as $variant) {

                $variationId =
                    isset($variant['woocommerce_variation_id'])
                        ? (int) $variant[
                            'woocommerce_variation_id'
                        ]
                        : 0;

                /*
                 * Simple-product mapping has no variation ownership
                 * record.
                 */
                if ($variationId <= 0) {
                    continue;
                }

                $canonicalVariantCode =
                    isset($variant['canonical_variant_code'])
                        ? trim(
                            (string)
                            $variant['canonical_variant_code']
                        )
                        : '';

                if (
                    !$this->hasExactVariantOwnership(
                        $variationId,
                        $canonicalVariantCode
                    )
                ) {
                    $errors[] = [
                        'product_id' => $productId,
                        'variation_id' => $variationId,
                        'canonical_variant_code' =>
                            $canonicalVariantCode,
                        'reason' =>
                            'VARIANT_OWNERSHIP_MISSING_OR_INCORRECT',
                    ];

                    continue;
                }

                $verifiedVariants++;
            }
        }

        if (
            $verifiedParents
            !== self::EXPECTED_PARENT_OWNERSHIP
        ) {
            $errors[] = [
                'reason' =>
                    'POST_WRITE_PARENT_COUNT_MISMATCH',
                'expected' =>
                    self::EXPECTED_PARENT_OWNERSHIP,
                'actual' =>
                    $verifiedParents,
            ];
        }

        if (
            $verifiedVariants
            !== self::EXPECTED_VARIANT_OWNERSHIP
        ) {
            $errors[] = [
                'reason' =>
                    'POST_WRITE_VARIANT_COUNT_MISMATCH',
                'expected' =>
                    self::EXPECTED_VARIANT_OWNERSHIP,
                'actual' =>
                    $verifiedVariants,
            ];
        }

        return [
            'pass' => count($errors) === 0,
            'verified_parent_ownership' =>
                $verifiedParents,
            'verified_variant_ownership' =>
                $verifiedVariants,
            'missing_or_incorrect_records' =>
                count($errors),
            'errors' => $errors,
        ];
    }
}