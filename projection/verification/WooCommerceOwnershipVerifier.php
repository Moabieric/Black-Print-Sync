<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Projection\Verification;

defined('ABSPATH') || exit;

use BlackPrint\Commerce\Projection\Adoption\VerifiedAdoptionMappingStore;

/**
 * WooCommerce Ownership Verifier.
 *
 * Post-Ownership Verification.
 *
 * This class performs an independent, read-only audit of WooCommerce
 * ownership metadata against the authoritative verified adoption
 * hand-off produced before Step 5B.
 *
 * IMPORTANT:
 *
 * - This class never writes WooCommerce data.
 * - This class never changes ownership metadata.
 * - This class never creates products or variations.
 * - This class never modifies SKUs.
 * - This class never modifies images.
 * - This class never recalculates Step 3 reconciliation.
 * - This class never creates or modifies the verified adoption artifact.
 * - This class does not call WooCommerceOwnershipCommitter.
 *
 * The authoritative ownership contract is:
 *
 * Parent:
 *   _blackprint_managed
 *   _blackprint_supplier
 *   _blackprint_product_id
 *   _blackprint_product_code
 *
 * Variant:
 *   _blackprint_managed
 *   _blackprint_supplier
 *   _blackprint_variant_code
 *
 * The authoritative mapping source is the verified adoption hand-off
 * stored by VerifiedAdoptionMappingStore.
 */
final class WooCommerceOwnershipVerifier
{
    /*
    |--------------------------------------------------------------------------
    | Locked verification contract.
    |--------------------------------------------------------------------------
    */

    /**
     * Number of approved adoption mappings.
     */
    private const EXPECTED_APPROVED_MAPPINGS = 3710;

    /**
     * Number of expected parent ownership records.
     */
    private const EXPECTED_PARENT_OWNERSHIP = 3710;

    /**
     * Number of explicit WooCommerce variation ownership records.
     */
    private const EXPECTED_VARIANT_OWNERSHIP = 20265;

    /**
     * BlackPrint ownership marker.
     *
     * This must match WooCommerceOwnershipCommitter.
     */
    private const MANAGED = 'yes';

    /**
     * Supplier identifier.
     *
     * This must match WooCommerceOwnershipCommitter.
     */
    private const SUPPLIER = 'amrod';


    /*
    |--------------------------------------------------------------------------
    | Dependencies.
    |--------------------------------------------------------------------------
    */

    /**
     * @var VerifiedAdoptionMappingStore
     */
    private VerifiedAdoptionMappingStore $mappingStore;


    /*
    |--------------------------------------------------------------------------
    | Constructor.
    |--------------------------------------------------------------------------
    */

    /**
     * @param VerifiedAdoptionMappingStore|null $mappingStore
     */
    public function __construct(
        ?VerifiedAdoptionMappingStore $mappingStore = null
    ) {
        $this->mappingStore =
            $mappingStore
            ?? new VerifiedAdoptionMappingStore();
    }


    /*
    |--------------------------------------------------------------------------
    | Public API.
    |--------------------------------------------------------------------------
    */

    /**
     * Verify the newest valid verified adoption hand-off.
     *
     * This method is read-only.
     *
     * @return array<string, mixed>
     */
    public function verifyLatest(): array
    {
        $artifact = $this->mappingStore->loadLatestVerified();

        if (!is_array($artifact)) {
            return $this->failureResult(
                'No valid verified adoption hand-off was found.',
                [
                    [
                        'reason' =>
                            'VERIFIED_ADOPTION_ARTIFACT_NOT_FOUND',
                    ],
                ]
            );
        }

        return $this->verifyPayload($artifact);
    }


    /**
     * Verify a specific verified adoption hand-off artifact.
     *
     * The VerifiedAdoptionMappingStore performs authoritative artifact
     * validation before the payload reaches this verifier.
     *
     * This method is read-only.
     *
     * @param string $artifactId
     *
     * @return array<string, mixed>
     */
    public function verifyArtifact(
        string $artifactId
    ): array {
        $artifactId = trim($artifactId);

        if ($artifactId === '') {
            return $this->failureResult(
                'An artifact ID is required.',
                [
                    [
                        'reason' => 'MISSING_ARTIFACT_ID',
                    ],
                ]
            );
        }

        $artifact = $this->mappingStore->load(
            $artifactId
        );

        if (!is_array($artifact)) {
            return $this->failureResult(
                'The requested verified adoption hand-off could not be loaded or failed validation.',
                [
                    [
                        'reason' =>
                            'VERIFIED_ADOPTION_ARTIFACT_INVALID_OR_EXPIRED',
                    ],
                ],
                [
                    'artifact_id' => $artifactId,
                ]
            );
        }

        return $this->verifyPayload($artifact);
    }


    /*
    |--------------------------------------------------------------------------
    | Core verification.
    |--------------------------------------------------------------------------
    */

    /**
     * Independently verify the complete authoritative artifact.
     *
     * @param array<string, mixed> $artifact
     *
     * @return array<string, mixed>
     */
    private function verifyPayload(
        array $artifact
    ): array {
        $contractErrors = $this->validateArtifactContract(
            $artifact
        );

        if ($contractErrors !== []) {
            return $this->failureResult(
                'The verified adoption artifact does not satisfy the locked post-ownership verification contract.',
                $contractErrors,
                $this->artifactSummary($artifact)
            );
        }

        $mappings = $artifact['adoption_mappings'];

        if (!is_array($mappings)) {
            return $this->failureResult(
                'The verified adoption artifact contains no valid adoption mapping array.',
                [
                    [
                        'reason' =>
                            'INVALID_ADOPTION_MAPPING_ARRAY',
                    ],
                ],
                $this->artifactSummary($artifact)
            );
        }

        $verifiedParents = 0;

        $verifiedVariants = 0;

        $parentMissing = [];

        $parentMismatches = [];

        $variantMissing = [];

        $variantMismatches = [];

        $mappingIndex = 0;

        foreach ($mappings as $mappingKey => $mapping) {
            $mappingIndex++;

            if (!is_array($mapping)) {
                continue;
            }

            $woocommerceProductId = (int) (
                $mapping['woocommerce_product_id']
                ?? 0
            );

            $canonicalProductId = trim(
                (string) (
                    $mapping['canonical_product_id']
                    ?? ''
                )
            );

            $canonicalProductCode = trim(
                (string) (
                    $mapping['canonical_product_code']
                    ?? ''
                )
            );

            $parentResult = $this->verifyParent(
                $woocommerceProductId,
                $canonicalProductId,
                $canonicalProductCode,
                $mappingIndex,
                $mappingKey
            );

            if (
                ($parentResult['status'] ?? '')
                === 'verified'
            ) {
                $verifiedParents++;
            } elseif (
                ($parentResult['status'] ?? '')
                === 'missing'
            ) {
                $parentMissing[] = $parentResult;
            } else {
                $parentMismatches[] = $parentResult;
            }

            $variants = $mapping['variants'] ?? [];

            if (!is_array($variants)) {
                continue;
            }

            foreach ($variants as $variantKey => $variant) {
                if (!is_array($variant)) {
                    continue;
                }

                $variationId = (int) (
                    $variant['woocommerce_variation_id']
                    ?? 0
                );

                /*
                 * Simple-product mappings can legitimately have no
                 * WooCommerce variation ID. These are not counted as
                 * explicit variation ownership records.
                 */
                if ($variationId <= 0) {
                    continue;
                }

                $canonicalVariantCode = trim(
                    (string) (
                        $variant['canonical_variant_code']
                        ?? ''
                    )
                );

                $variantResult = $this->verifyVariant(
                    $variationId,
                    $woocommerceProductId,
                    $canonicalVariantCode,
                    $mappingIndex,
                    $mappingKey,
                    $variantKey
                );

                if (
                    ($variantResult['status'] ?? '')
                    === 'verified'
                ) {
                    $verifiedVariants++;
                } elseif (
                    ($variantResult['status'] ?? '')
                    === 'missing'
                ) {
                    $variantMissing[] = $variantResult;
                } else {
                    $variantMismatches[] = $variantResult;
                }
            }
        }

        $errors = [];

        if (
            $verifiedParents
            !== self::EXPECTED_PARENT_OWNERSHIP
        ) {
            $errors[] = [
                'reason' =>
                    'PARENT_OWNERSHIP_COUNT_MISMATCH',
                'expected' =>
                    self::EXPECTED_PARENT_OWNERSHIP,
                'verified' =>
                    $verifiedParents,
            ];
        }

        if (
            $verifiedVariants
            !== self::EXPECTED_VARIANT_OWNERSHIP
        ) {
            $errors[] = [
                'reason' =>
                    'VARIANT_OWNERSHIP_COUNT_MISMATCH',
                'expected' =>
                    self::EXPECTED_VARIANT_OWNERSHIP,
                'verified' =>
                    $verifiedVariants,
            ];
        }

        if ($parentMissing !== []) {
            $errors[] = [
                'reason' =>
                    'PARENT_OWNERSHIP_MISSING',
                'count' =>
                    count($parentMissing),
            ];
        }

        if ($parentMismatches !== []) {
            $errors[] = [
                'reason' =>
                    'PARENT_OWNERSHIP_MISMATCH',
                'count' =>
                    count($parentMismatches),
            ];
        }

        if ($variantMissing !== []) {
            $errors[] = [
                'reason' =>
                    'VARIANT_OWNERSHIP_MISSING',
                'count' =>
                    count($variantMissing),
            ];
        }

        if ($variantMismatches !== []) {
            $errors[] = [
                'reason' =>
                    'VARIANT_OWNERSHIP_MISMATCH',
                'count' =>
                    count($variantMismatches),
            ];
        }

        $pass =
            $errors === []
            && $verifiedParents
                === self::EXPECTED_PARENT_OWNERSHIP
            && $verifiedVariants
                === self::EXPECTED_VARIANT_OWNERSHIP;

        return [
            'success' => $pass,
            'pass' => $pass,
            'status' => $pass ? 'PASS' : 'FAIL',
            'phase' => 'POST_OWNERSHIP_VERIFICATION',
            'message' => $pass
                ? 'Post-ownership verification passed. All approved BlackPrint ownership records were independently verified.'
                : 'Post-ownership verification failed. One or more approved BlackPrint ownership records are missing or incorrect.',
            'artifact' =>
                $this->artifactSummary($artifact),
            'expected' => [
                'approved_mappings' =>
                    self::EXPECTED_APPROVED_MAPPINGS,
                'parent_ownership' =>
                    self::EXPECTED_PARENT_OWNERSHIP,
                'variant_ownership' =>
                    self::EXPECTED_VARIANT_OWNERSHIP,
            ],
            'verified' => [
                'approved_mappings' =>
                    count($mappings),
                'parents' =>
                    $verifiedParents,
                'variants' =>
                    $verifiedVariants,
            ],
            'audit' => [
                'parent_missing_count' =>
                    count($parentMissing),
                'parent_mismatch_count' =>
                    count($parentMismatches),
                'variant_missing_count' =>
                    count($variantMissing),
                'variant_mismatch_count' =>
                    count($variantMismatches),
            ],
            'missing' => [
                'parents' =>
                    $parentMissing,
                'variants' =>
                    $variantMissing,
            ],
            'mismatches' => [
                'parents' =>
                    $parentMismatches,
                'variants' =>
                    $variantMismatches,
            ],
            'errors' => $errors,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Parent verification.
    |--------------------------------------------------------------------------
    */

    /**
     * Verify one WooCommerce parent ownership record.
     *
     * No WooCommerce writes occur here.
     *
     * @return array<string, mixed>
     */
    private function verifyParent(
        int $woocommerceProductId,
        string $canonicalProductId,
        string $canonicalProductCode,
        int $mappingIndex,
        int|string $mappingKey
    ): array {
        $context = [
            'mapping_index' =>
                $mappingIndex,
            'mapping_key' =>
                $mappingKey,
            'woocommerce_product_id' =>
                $woocommerceProductId,
            'canonical_product_id' =>
                $canonicalProductId,
            'canonical_product_code' =>
                $canonicalProductCode,
        ];

        if ($woocommerceProductId <= 0) {
            return array_merge(
                $context,
                [
                    'status' => 'missing',
                    'reason' =>
                        'INVALID_WOOCOMMERCE_PRODUCT_ID',
                ]
            );
        }

        $post = get_post(
            $woocommerceProductId
        );

        if (!$post) {
            return array_merge(
                $context,
                [
                    'status' => 'missing',
                    'reason' =>
                        'WOOCOMMERCE_PRODUCT_NOT_FOUND',
                ]
            );
        }

        $expected = [
            '_blackprint_managed' =>
                self::MANAGED,
            '_blackprint_supplier' =>
                self::SUPPLIER,
            '_blackprint_product_id' =>
                $canonicalProductId,
            '_blackprint_product_code' =>
                $canonicalProductCode,
        ];

        $actual = $this->readParentOwnership(
            $woocommerceProductId
        );

        $comparison = $this->compareOwnership(
            $expected,
            $actual
        );

        if (
            ($comparison['pass'] ?? false)
            === true
        ) {
            return array_merge(
                $context,
                [
                    'status' => 'verified',
                    'expected' => $expected,
                    'actual' => $actual,
                ]
            );
        }

        return array_merge(
            $context,
            [
                'status' => 'mismatch',
                'reason' =>
                    'PARENT_OWNERSHIP_METADATA_MISMATCH',
                'expected' => $expected,
                'actual' => $actual,
                'differences' =>
                    $comparison['differences'],
            ]
        );
    }


    /**
     * Read parent ownership metadata.
     *
     * This method is strictly read-only.
     *
     * @return array<string, string>
     */
    private function readParentOwnership(
        int $woocommerceProductId
    ): array {
        return [
            '_blackprint_managed' =>
                (string) get_post_meta(
                    $woocommerceProductId,
                    '_blackprint_managed',
                    true
                ),
            '_blackprint_supplier' =>
                (string) get_post_meta(
                    $woocommerceProductId,
                    '_blackprint_supplier',
                    true
                ),
            '_blackprint_product_id' =>
                (string) get_post_meta(
                    $woocommerceProductId,
                    '_blackprint_product_id',
                    true
                ),
            '_blackprint_product_code' =>
                (string) get_post_meta(
                    $woocommerceProductId,
                    '_blackprint_product_code',
                    true
                ),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Variant verification.
    |--------------------------------------------------------------------------
    */

    /**
     * Verify one WooCommerce variation ownership record.
     *
     * No WooCommerce writes occur here.
     *
     * @return array<string, mixed>
     */
    private function verifyVariant(
        int $variationId,
        int $expectedParentProductId,
        string $canonicalVariantCode,
        int $mappingIndex,
        int|string $mappingKey,
        int|string $variantKey
    ): array {
        $context = [
            'mapping_index' =>
                $mappingIndex,
            'mapping_key' =>
                $mappingKey,
            'variant_key' =>
                $variantKey,
            'woocommerce_variation_id' =>
                $variationId,
            'woocommerce_product_id' =>
                $expectedParentProductId,
            'canonical_variant_code' =>
                $canonicalVariantCode,
        ];

        if ($variationId <= 0) {
            return array_merge(
                $context,
                [
                    'status' => 'missing',
                    'reason' =>
                        'INVALID_WOOCOMMERCE_VARIATION_ID',
                ]
            );
        }

        $variation = get_post(
            $variationId
        );

        if (!$variation) {
            return array_merge(
                $context,
                [
                    'status' => 'missing',
                    'reason' =>
                        'WOOCOMMERCE_VARIATION_NOT_FOUND',
                ]
            );
        }

        $actualParentProductId = (int) wp_get_post_parent_id(
            $variationId
        );

        if (
            $actualParentProductId
            !== $expectedParentProductId
        ) {
            return array_merge(
                $context,
                [
                    'status' => 'mismatch',
                    'reason' =>
                        'WOOCOMMERCE_VARIATION_PARENT_MISMATCH',
                    'expected_parent_product_id' =>
                        $expectedParentProductId,
                    'actual_parent_product_id' =>
                        $actualParentProductId,
                ]
            );
        }

        $expected = [
            '_blackprint_managed' =>
                self::MANAGED,
            '_blackprint_supplier' =>
                self::SUPPLIER,
            '_blackprint_variant_code' =>
                $canonicalVariantCode,
        ];

        $actual = $this->readVariantOwnership(
            $variationId
        );

        $comparison = $this->compareOwnership(
            $expected,
            $actual
        );

        if (
            ($comparison['pass'] ?? false)
            === true
        ) {
            return array_merge(
                $context,
                [
                    'status' => 'verified',
                    'expected' => $expected,
                    'actual' => $actual,
                    'actual_parent_product_id' =>
                        $actualParentProductId,
                ]
            );
        }

        return array_merge(
            $context,
            [
                'status' => 'mismatch',
                'reason' =>
                    'VARIANT_OWNERSHIP_METADATA_MISMATCH',
                'expected' => $expected,
                'actual' => $actual,
                'differences' =>
                    $comparison['differences'],
                'actual_parent_product_id' =>
                    $actualParentProductId,
            ]
        );
    }


    /**
     * Read variation ownership metadata.
     *
     * This method is strictly read-only.
     *
     * @return array<string, string>
     */
    private function readVariantOwnership(
        int $variationId
    ): array {
        return [
            '_blackprint_managed' =>
                (string) get_post_meta(
                    $variationId,
                    '_blackprint_managed',
                    true
                ),
            '_blackprint_supplier' =>
                (string) get_post_meta(
                    $variationId,
                    '_blackprint_supplier',
                    true
                ),
            '_blackprint_variant_code' =>
                (string) get_post_meta(
                    $variationId,
                    '_blackprint_variant_code',
                    true
                ),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Metadata comparison.
    |--------------------------------------------------------------------------
    */

    /**
     * Compare expected and actual ownership metadata.
     *
     * @param array<string, string> $expected
     * @param array<string, string> $actual
     *
     * @return array{
     *     pass: bool,
     *     differences: array<string, array{
     *         expected: string,
     *         actual: string
     *     }>
     * }
     */
    private function compareOwnership(
        array $expected,
        array $actual
    ): array {
        $differences = [];

        foreach ($expected as $field => $expectedValue) {
            $actualValue = (string) (
                $actual[$field]
                ?? ''
            );

            if ($actualValue !== $expectedValue) {
                $differences[$field] = [
                    'expected' =>
                        $expectedValue,
                    'actual' =>
                        $actualValue,
                ];
            }
        }

        return [
            'pass' =>
                $differences === [],
            'differences' =>
                $differences,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Artifact contract validation.
    |--------------------------------------------------------------------------
    */

    /**
     * Validate the post-ownership verification artifact contract.
     *
     * This is an independent defensive validation layer.
     *
     * @param array<string, mixed> $artifact
     *
     * @return array<int, array<string, mixed>>
     */
    private function validateArtifactContract(
        array $artifact
    ): array {
        $errors = [];

        $mappings = $artifact['adoption_mappings']
            ?? null;

        if (!is_array($mappings)) {
            return [
                [
                    'reason' =>
                        'INVALID_ADOPTION_MAPPING_ARRAY',
                ],
            ];
        }

        if (
            count($mappings)
            !== self::EXPECTED_APPROVED_MAPPINGS
        ) {
            $errors[] = [
                'reason' =>
                    'APPROVED_MAPPING_COUNT_MISMATCH',
                'expected' =>
                    self::EXPECTED_APPROVED_MAPPINGS,
                'actual' =>
                    count($mappings),
            ];
        }

        $explicitVariantOwnership = 0;

        $mappingIndex = 0;

        foreach ($mappings as $mappingKey => $mapping) {
            $mappingIndex++;

            if (!is_array($mapping)) {
                $errors[] = [
                    'reason' =>
                        'INVALID_MAPPING_RECORD',
                    'mapping_index' =>
                        $mappingIndex,
                    'mapping_key' =>
                        $mappingKey,
                ];

                continue;
            }

            $decision = strtoupper(
                trim(
                    (string) (
                        $mapping['decision']
                        ?? ''
                    )
                )
            );

            if ($decision !== 'ADOPT') {
                $errors[] = [
                    'reason' =>
                        'NON_ADOPT_MAPPING_PRESENT',
                    'mapping_index' =>
                        $mappingIndex,
                    'mapping_key' =>
                        $mappingKey,
                    'decision' =>
                        $decision,
                ];
            }

            $woocommerceProductId = (int) (
                $mapping['woocommerce_product_id']
                ?? 0
            );

            $mappingProductId = (int) (
                $mappingKey
            );

            if (
                $woocommerceProductId <= 0
                || $woocommerceProductId
                    !== $mappingProductId
            ) {
                $errors[] = [
                    'reason' =>
                        'WOOCOMMERCE_PRODUCT_KEY_MISMATCH',
                    'mapping_index' =>
                        $mappingIndex,
                    'mapping_key' =>
                        $mappingKey,
                    'woocommerce_product_id' =>
                        $woocommerceProductId,
                ];
            }

            $canonicalProductId = trim(
                (string) (
                    $mapping['canonical_product_id']
                    ?? ''
                )
            );

            if ($canonicalProductId === '') {
                $errors[] = [
                    'reason' =>
                        'MISSING_CANONICAL_PRODUCT_ID',
                    'mapping_index' =>
                        $mappingIndex,
                    'mapping_key' =>
                        $mappingKey,
                ];
            }

            $canonicalProductCode = trim(
                (string) (
                    $mapping['canonical_product_code']
                    ?? ''
                )
            );

            if ($canonicalProductCode === '') {
                $errors[] = [
                    'reason' =>
                        'MISSING_CANONICAL_PRODUCT_CODE',
                    'mapping_index' =>
                        $mappingIndex,
                    'mapping_key' =>
                        $mappingKey,
                ];
            }

            $variants = $mapping['variants']
                ?? [];

            if (!is_array($variants)) {
                $errors[] = [
                    'reason' =>
                        'INVALID_VARIANT_MAPPING_ARRAY',
                    'mapping_index' =>
                        $mappingIndex,
                    'mapping_key' =>
                        $mappingKey,
                ];

                continue;
            }

            foreach ($variants as $variantKey => $variant) {
                if (!is_array($variant)) {
                    $errors[] = [
                        'reason' =>
                            'INVALID_VARIANT_MAPPING_RECORD',
                        'mapping_index' =>
                            $mappingIndex,
                        'mapping_key' =>
                            $mappingKey,
                        'variant_key' =>
                            $variantKey,
                    ];

                    continue;
                }

                $variationId = (int) (
                    $variant['woocommerce_variation_id']
                    ?? 0
                );

                if ($variationId > 0) {
                    $explicitVariantOwnership++;

                    $canonicalVariantCode = trim(
                        (string) (
                            $variant['canonical_variant_code']
                            ?? ''
                        )
                    );

                    if ($canonicalVariantCode === '') {
                        $errors[] = [
                            'reason' =>
                                'MISSING_CANONICAL_VARIANT_CODE',
                            'mapping_index' =>
                                $mappingIndex,
                            'mapping_key' =>
                                $mappingKey,
                            'variant_key' =>
                                $variantKey,
                            'woocommerce_variation_id' =>
                                $variationId,
                        ];
                    }
                }
            }
        }

        if (
            $explicitVariantOwnership
            !== self::EXPECTED_VARIANT_OWNERSHIP
        ) {
            $errors[] = [
                'reason' =>
                    'EXPLICIT_VARIANT_OWNERSHIP_COUNT_MISMATCH',
                'expected' =>
                    self::EXPECTED_VARIANT_OWNERSHIP,
                'actual' =>
                    $explicitVariantOwnership,
            ];
        }

        $storedApprovedCount = (int) (
            $artifact['approved_mapping_count']
            ?? 0
        );

        if (
            $storedApprovedCount
            !== self::EXPECTED_APPROVED_MAPPINGS
        ) {
            $errors[] = [
                'reason' =>
                    'STORED_APPROVED_MAPPING_COUNT_MISMATCH',
                'expected' =>
                    self::EXPECTED_APPROVED_MAPPINGS,
                'actual' =>
                    $storedApprovedCount,
            ];
        }

        $storedVariantCount = (int) (
            $artifact['explicit_variant_ownership_count']
            ?? 0
        );

        if (
            $storedVariantCount
            !== self::EXPECTED_VARIANT_OWNERSHIP
        ) {
            $errors[] = [
                'reason' =>
                    'STORED_VARIANT_OWNERSHIP_COUNT_MISMATCH',
                'expected' =>
                    self::EXPECTED_VARIANT_OWNERSHIP,
                'actual' =>
                    $storedVariantCount,
            ];
        }

        return $errors;
    }


    /*
    |--------------------------------------------------------------------------
    | Result helpers.
    |--------------------------------------------------------------------------
    */

    /**
     * Build a standard failure response.
     *
     * @param array<int, array<string, mixed>> $errors
     * @param array<string, mixed> $artifact
     *
     * @return array<string, mixed>
     */
    private function failureResult(
        string $message,
        array $errors,
        array $artifact = []
    ): array {
        return [
            'success' => false,
            'pass' => false,
            'status' => 'FAIL',
            'phase' => 'POST_OWNERSHIP_VERIFICATION',
            'message' => $message,
            'artifact' =>
                $artifact !== []
                    ? $artifact
                    : null,
            'expected' => [
                'approved_mappings' =>
                    self::EXPECTED_APPROVED_MAPPINGS,
                'parent_ownership' =>
                    self::EXPECTED_PARENT_OWNERSHIP,
                'variant_ownership' =>
                    self::EXPECTED_VARIANT_OWNERSHIP,
            ],
            'verified' => [
                'approved_mappings' => 0,
                'parents' => 0,
                'variants' => 0,
            ],
            'errors' => $errors,
        ];
    }


    /**
     * Return safe artifact metadata for audit output.
     *
     * @param array<string, mixed> $artifact
     *
     * @return array<string, mixed>
     */
    private function artifactSummary(
        array $artifact
    ): array {
        return [
            'artifact_id' =>
                (string) (
                    $artifact['artifact_id']
                    ?? ''
                ),
            'snapshot_uuid' =>
                (string) (
                    $artifact['snapshot_uuid']
                    ?? ''
                ),
            'mapping_hash' =>
                (string) (
                    $artifact['mapping_hash']
                    ?? ''
                ),
            'approved_mapping_count' =>
                (int) (
                    $artifact['approved_mapping_count']
                    ?? 0
                ),
            'explicit_variant_ownership_count' =>
                (int) (
                    $artifact['explicit_variant_ownership_count']
                    ?? 0
                ),
            'created_at' =>
                (int) (
                    $artifact['created_at']
                    ?? 0
                ),
            'expires_at' =>
                (int) (
                    $artifact['expires_at']
                    ?? 0
                ),
        ];
    }
}