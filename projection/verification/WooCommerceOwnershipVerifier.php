<?php

/*
|--------------------------------------------------------------------------
| BlackPrint Commerce
|--------------------------------------------------------------------------
|
| WooCommerce Ownership Verifier
|
| Post-Ownership Verification
|
| This class performs an independent, read-only audit of WooCommerce
| ownership metadata against the authoritative verified adoption
| hand-off produced before Step 5B.
|
| IMPORTANT:
|
| - This class never writes WooCommerce data.
| - This class never changes ownership metadata.
| - This class never creates products or variations.
| - This class never modifies SKUs.
| - This class never modifies images.
| - This class never recalculates Step 3 reconciliation.
| - This class never creates or modifies the verified adoption artifact.
|
| The authoritative ownership contract is:
|
| Parent:
|   _blackprint_managed
|   _blackprint_supplier
|   _blackprint_product_id
|   _blackprint_product_code
|
| Variant:
|   _blackprint_managed
|   _blackprint_supplier
|   _blackprint_variant_code
|
*/


require_once BP_COMMERCE_PATH
    . 'projection/adoption/VerifiedAdoptionMappingStore.php';


final class WooCommerceOwnershipVerifier
{
    /*
    |--------------------------------------------------------------------------
    | Verification Contract
    |--------------------------------------------------------------------------
    */

    /**
     * Number of approved parent adoption mappings.
     */
    private const EXPECTED_APPROVED_MAPPINGS = 3710;

    /**
     * Number of WooCommerce parent ownership records expected.
     */
    private const EXPECTED_PARENT_OWNERSHIP = 3710;

    /**
     * Number of explicit WooCommerce variation ownership records expected.
     */
    private const EXPECTED_VARIANT_OWNERSHIP = 20265;

    /**
     * BlackPrint ownership marker.
     *
     * This must match WooCommerceOwnershipCommitter.
     */
    private const MANAGED = '1';

    /**
     * Supplier identifier.
     *
     * This must match WooCommerceOwnershipCommitter.
     */
    private const SUPPLIER = 'amrod';


    /*
    |--------------------------------------------------------------------------
    | Dependencies
    |--------------------------------------------------------------------------
    */

    /**
     * @var VerifiedAdoptionMappingStore
     */
    private VerifiedAdoptionMappingStore $mappingStore;


    /*
    |--------------------------------------------------------------------------
    | Constructor
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
    | Public Verification API
    |--------------------------------------------------------------------------
    */

    /**
     * Verify the newest valid Step 5B adoption hand-off.
     *
     * This method is read-only.
     *
     * @return array<string, mixed>
     */
    public function verifyLatest(): array
    {
        $artifact =
            $this->mappingStore
                ->loadLatestVerified();

        if (!is_array($artifact)) {
            return [
                'success' => false,
                'status' => 'FAIL',
                'phase' => 'POST_OWNERSHIP_VERIFICATION',
                'message' =>
                    'No valid verified adoption hand-off was found.',
                'artifact' => null,
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
                'errors' => [
                    [
                        'reason' =>
                            'VERIFIED_ADOPTION_ARTIFACT_NOT_FOUND',
                    ],
                ],
            ];
        }

        return $this->verifyPayload(
            $artifact
        );
    }


    /**
     * Verify a specific Step 5B adoption hand-off artifact.
     *
     * The VerifiedAdoptionMappingStore performs the authoritative
     * artifact validation before the payload reaches this verifier.
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
        $artifactId =
            trim($artifactId);

        if ($artifactId === '') {
            return [
                'success' => false,
                'status' => 'FAIL',
                'phase' => 'POST_OWNERSHIP_VERIFICATION',
                'message' =>
                    'An artifact ID is required.',
                'artifact' => null,
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
                'errors' => [
                    [
                        'reason' =>
                            'MISSING_ARTIFACT_ID',
                    ],
                ],
            ];
        }

        $artifact =
            $this->mappingStore->load(
                $artifactId
            );

        if (!is_array($artifact)) {
            return [
                'success' => false,
                'status' => 'FAIL',
                'phase' => 'POST_OWNERSHIP_VERIFICATION',
                'message' =>
                    'The requested verified adoption hand-off could not be loaded or failed validation.',
                'artifact' => [
                    'artifact_id' =>
                        $artifactId,
                ],
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
                'errors' => [
                    [
                        'reason' =>
                            'VERIFIED_ADOPTION_ARTIFACT_INVALID_OR_EXPIRED',
                    ],
                ],
            ];
        }

        return $this->verifyPayload(
            $artifact
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Core Verification
    |--------------------------------------------------------------------------
    */

    /**
     * Independently verify WooCommerce against a validated adoption
     * hand-off.
     *
     * This method performs no writes.
     *
     * @param array<string, mixed> $artifact
     *
     * @return array<string, mixed>
     */
    private function verifyPayload(
        array $artifact
    ): array {
        $preflight =
            $this->validateArtifactContract(
                $artifact
            );

        if (!$preflight['pass']) {
            return [
                'success' => false,
                'status' => 'FAIL',
                'phase' => 'POST_OWNERSHIP_VERIFICATION',
                'message' =>
                    'Post-Ownership Verification could not begin because the verified adoption hand-off failed the verifier contract.',
                'artifact' =>
                    $this->artifactSummary(
                        $artifact
                    ),
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
                        $preflight[
                            'approved_mappings'
                        ],
                    'parents' => 0,
                    'variants' => 0,
                ],
                'preflight' => $preflight,
                'errors' =>
                    $preflight['errors'],
            ];
        }

        $adoptionMappings =
            $artifact['adoption_mappings'];

        $verifiedParents = 0;
        $verifiedVariants = 0;

        $parentMissing = [];
        $parentMismatches = [];
        $variantMissing = [];
        $variantMismatches = [];

        foreach (
            $adoptionMappings
            as $productId => $mapping
        ) {
            $productId =
                (int) $productId;

            /*
             * ----------------------------------------------------------
             * Parent verification
             * ----------------------------------------------------------
             */

            $parentResult =
                $this->verifyParent(
                    $productId,
                    $mapping
                );

            if (
                $parentResult['status']
                === 'verified'
            ) {
                $verifiedParents++;

            } elseif (
                $parentResult['status']
                === 'missing'
            ) {
                $parentMissing[] =
                    $parentResult;

            } else {
                $parentMismatches[] =
                    $parentResult;
            }


            /*
             * ----------------------------------------------------------
             * Explicit variant verification
             * ----------------------------------------------------------
             *
             * Simple-product mappings deliberately have no
             * WooCommerce variation ownership record.
             */

            $variants =
                isset($mapping['variants'])
                && is_array($mapping['variants'])
                    ? $mapping['variants']
                    : [];

            foreach ($variants as $variant) {

                if (!is_array($variant)) {
                    continue;
                }

                $variationId =
                    isset(
                        $variant[
                            'woocommerce_variation_id'
                        ]
                    )
                        ? (int) $variant[
                            'woocommerce_variation_id'
                        ]
                        : 0;

                if ($variationId <= 0) {
                    continue;
                }

                $variantResult =
                    $this->verifyVariant(
                        $productId,
                        $variant
                    );

                if (
                    $variantResult['status']
                    === 'verified'
                ) {
                    $verifiedVariants++;

                } elseif (
                    $variantResult['status']
                    === 'missing'
                ) {
                    $variantMissing[] =
                        $variantResult;

                } else {
                    $variantMismatches[] =
                        $variantResult;
                }
            }
        }


        /*
         * --------------------------------------------------------------
         * Final count validation
         * --------------------------------------------------------------
         */

        $errors = [];

        if (
            $verifiedParents
            !== self::EXPECTED_PARENT_OWNERSHIP
        ) {
            $errors[] = [
                'reason' =>
                    'POST_OWNERSHIP_PARENT_COUNT_MISMATCH',
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
                    'POST_OWNERSHIP_VARIANT_COUNT_MISMATCH',
                'expected' =>
                    self::EXPECTED_VARIANT_OWNERSHIP,
                'actual' =>
                    $verifiedVariants,
            ];
        }

        if (!empty($parentMissing)) {
            $errors[] = [
                'reason' =>
                    'PARENT_OWNERSHIP_RECORDS_MISSING',
                'count' =>
                    count($parentMissing),
            ];
        }

        if (!empty($parentMismatches)) {
            $errors[] = [
                'reason' =>
                    'PARENT_OWNERSHIP_RECORDS_MISMATCH',
                'count' =>
                    count($parentMismatches),
            ];
        }

        if (!empty($variantMissing)) {
            $errors[] = [
                'reason' =>
                    'VARIANT_OWNERSHIP_RECORDS_MISSING',
                'count' =>
                    count($variantMissing),
            ];
        }

        if (!empty($variantMismatches)) {
            $errors[] = [
                'reason' =>
                    'VARIANT_OWNERSHIP_RECORDS_MISMATCH',
                'count' =>
                    count($variantMismatches),
            ];
        }

        $success =
            count($errors) === 0;

        return [
            'success' =>
                $success,

            'status' =>
                $success
                    ? 'PASS'
                    : 'FAIL',

            'phase' =>
                'POST_OWNERSHIP_VERIFICATION',

            'message' =>
                $success
                    ? 'Post-Ownership Verification passed. All approved BlackPrint ownership records were verified in WooCommerce.'
                    : 'Post-Ownership Verification failed. One or more approved BlackPrint ownership records are missing or incorrect.',

            'artifact' =>
                $this->artifactSummary(
                    $artifact
                ),

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
                    count($adoptionMappings),

                'parents' =>
                    $verifiedParents,

                'variants' =>
                    $verifiedVariants,
            ],

            'audit' => [
                'parents_missing' =>
                    count($parentMissing),

                'parents_mismatched' =>
                    count($parentMismatches),

                'variants_missing' =>
                    count($variantMissing),

                'variants_mismatched' =>
                    count($variantMismatches),

                'total_errors' =>
                    count($errors),
            ],

            'details' => [
                'parent_missing' =>
                    $parentMissing,

                'parent_mismatches' =>
                    $parentMismatches,

                'variant_missing' =>
                    $variantMissing,

                'variant_mismatches' =>
                    $variantMismatches,
            ],

            'errors' =>
                $errors,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Parent Verification
    |--------------------------------------------------------------------------
    */

    /**
     * Verify one WooCommerce parent against its authoritative mapping.
     *
     * This method is strictly read-only.
     *
     * @param int $productId
     * @param array<string, mixed> $mapping
     *
     * @return array<string, mixed>
     */
    private function verifyParent(
        int $productId,
        array $mapping
    ): array {
        $canonicalProductId =
            isset(
                $mapping['canonical_product_id']
            )
                ? trim(
                    (string)
                    $mapping[
                        'canonical_product_id'
                    ]
                )
                : '';

        $canonicalProductCode =
            isset(
                $mapping['canonical_product_code']
            )
                ? trim(
                    (string)
                    $mapping[
                        'canonical_product_code'
                    ]
                )
                : '';

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

        /*
         * The product itself must exist.
         */
        $product =
            get_post(
                $productId
            );

        if (!$product) {
            return [
                'status' =>
                    'missing',

                'woocommerce_product_id' =>
                    $productId,

                'expected' =>
                    $expected,

                'actual' =>
                    null,

                'reason' =>
                    'WOOCOMMERCE_PARENT_DOES_NOT_EXIST',
            ];
        }

        $actual =
            $this->readParentOwnership(
                $productId
            );

        $comparison =
            $this->compareOwnership(
                $expected,
                $actual
            );

        if ($comparison['pass']) {
            return [
                'status' =>
                    'verified',

                'woocommerce_product_id' =>
                    $productId,

                'expected' =>
                    $expected,

                'actual' =>
                    $actual,
            ];
        }

        return [
            'status' =>
                'mismatch',

            'woocommerce_product_id' =>
                $productId,

            'expected' =>
                $expected,

            'actual' =>
                $actual,

            'differences' =>
                $comparison['differences'],

            'reason' =>
                'PARENT_OWNERSHIP_MISSING_OR_INCORRECT',
        ];
    }


    /**
     * Read parent ownership metadata.
     *
     * READ ONLY.
     *
     * @param int $productId
     *
     * @return array<string, string>
     */
    private function readParentOwnership(
        int $productId
    ): array {
        return [
            '_blackprint_managed' =>
                (string) get_post_meta(
                    $productId,
                    '_blackprint_managed',
                    true
                ),

            '_blackprint_supplier' =>
                (string) get_post_meta(
                    $productId,
                    '_blackprint_supplier',
                    true
                ),

            '_blackprint_product_id' =>
                (string) get_post_meta(
                    $productId,
                    '_blackprint_product_id',
                    true
                ),

            '_blackprint_product_code' =>
                (string) get_post_meta(
                    $productId,
                    '_blackprint_product_code',
                    true
                ),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Variant Verification
    |--------------------------------------------------------------------------
    */

    /**
     * Verify one WooCommerce variation against its authoritative
     * canonical variant mapping.
     *
     * This method is strictly read-only.
     *
     * @param int $productId
     * @param array<string, mixed> $variant
     *
     * @return array<string, mixed>
     */
    private function verifyVariant(
        int $productId,
        array $variant
    ): array {
        $variationId =
            isset(
                $variant[
                    'woocommerce_variation_id'
                ]
            )
                ? (int) $variant[
                    'woocommerce_variation_id'
                ]
                : 0;

        $canonicalVariantCode =
            isset(
                $variant[
                    'canonical_variant_code'
                ]
            )
                ? trim(
                    (string) $variant[
                        'canonical_variant_code'
                    ]
                )
                : '';

        $expected = [
            '_blackprint_managed' =>
                self::MANAGED,

            '_blackprint_supplier' =>
                self::SUPPLIER,

            '_blackprint_variant_code' =>
                $canonicalVariantCode,
        ];

        /*
         * Variation must exist.
         */
        $variation =
            get_post(
                $variationId
            );

        if (!$variation) {
            return [
                'status' =>
                    'missing',

                'woocommerce_product_id' =>
                    $productId,

                'woocommerce_variation_id' =>
                    $variationId,

                'canonical_variant_code' =>
                    $canonicalVariantCode,

                'expected' =>
                    $expected,

                'actual' =>
                    null,

                'reason' =>
                    'WOOCOMMERCE_VARIATION_DOES_NOT_EXIST',
            ];
        }

        /*
         * Variation must still belong to the approved parent.
         */
        $actualParentId =
            (int) wp_get_post_parent_id(
                $variationId
            );

        if (
            $actualParentId
            !== $productId
        ) {
            return [
                'status' =>
                    'mismatch',

                'woocommerce_product_id' =>
                    $productId,

                'woocommerce_variation_id' =>
                    $variationId,

                'canonical_variant_code' =>
                    $canonicalVariantCode,

                'expected_parent_id' =>
                    $productId,

                'actual_parent_id' =>
                    $actualParentId,

                'expected' =>
                    $expected,

                'actual' =>
                    $this->readVariantOwnership(
                        $variationId
                    ),

                'reason' =>
                    'WOOCOMMERCE_VARIATION_HAS_WRONG_PARENT',
            ];
        }

        $actual =
            $this->readVariantOwnership(
                $variationId
            );

        $comparison =
            $this->compareOwnership(
                $expected,
                $actual
            );

        if ($comparison['pass']) {
            return [
                'status' =>
                    'verified',

                'woocommerce_product_id' =>
                    $productId,

                'woocommerce_variation_id' =>
                    $variationId,

                'canonical_variant_code' =>
                    $canonicalVariantCode,

                'expected_parent_id' =>
                    $productId,

                'actual_parent_id' =>
                    $actualParentId,

                'expected' =>
                    $expected,

                'actual' =>
                    $actual,
            ];
        }

        return [
            'status' =>
                'mismatch',

            'woocommerce_product_id' =>
                $productId,

            'woocommerce_variation_id' =>
                $variationId,

            'canonical_variant_code' =>
                $canonicalVariantCode,

            'expected_parent_id' =>
                $productId,

            'actual_parent_id' =>
                $actualParentId,

            'expected' =>
                $expected,

            'actual' =>
                $actual,

            'differences' =>
                $comparison['differences'],

            'reason' =>
                'VARIANT_OWNERSHIP_MISSING_OR_INCORRECT',
        ];
    }


    /**
     * Read variant ownership metadata.
     *
     * READ ONLY.
     *
     * @param int $variationId
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
    | Comparison
    |--------------------------------------------------------------------------
    */

    /**
     * Compare expected and actual ownership metadata.
     *
     * @param array<string, string> $expected
     * @param array<string, string> $actual
     *
     * @return array<string, mixed>
     */
    private function compareOwnership(
        array $expected,
        array $actual
    ): array {
        $differences = [];

        foreach ($expected as $key => $expectedValue) {

            $actualValue =
                $actual[$key]
                ?? '';

            if (
                (string) $actualValue
                !== (string) $expectedValue
            ) {
                $differences[] = [
                    'field' =>
                        $key,

                    'expected' =>
                        (string) $expectedValue,

                    'actual' =>
                        (string) $actualValue,
                ];
            }
        }

        return [
            'pass' =>
                count($differences) === 0,

            'differences' =>
                $differences,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Artifact Contract Validation
    |--------------------------------------------------------------------------
    */

    /**
     * Validate the adoption payload against the Post-Ownership
     * Verification contract.
     *
     * The VerifiedAdoptionMappingStore has already performed its
     * authoritative artifact validation. This is an additional
     * defensive validation at the verification boundary.
     *
     * @param array<string, mixed> $artifact
     *
     * @return array<string, mixed>
     */
    private function validateArtifactContract(
        array $artifact
    ): array {
        $errors = [];

        $mappings =
            $artifact['adoption_mappings']
            ?? null;

        if (!is_array($mappings)) {
            return [
                'pass' => false,
                'approved_mappings' => 0,
                'parent_mappings' => 0,
                'variant_mappings' => 0,
                'errors' => [
                    [
                        'reason' =>
                            'ADOPTION_MAPPINGS_NOT_ARRAY',
                    ],
                ],
            ];
        }

        $approvedMappingCount =
            count($mappings);

        if (
            $approvedMappingCount
            !== self::EXPECTED_APPROVED_MAPPINGS
        ) {
            $errors[] = [
                'reason' =>
                    'INVALID_APPROVED_MAPPING_COUNT',
                'expected' =>
                    self::EXPECTED_APPROVED_MAPPINGS,
                'actual' =>
                    $approvedMappingCount,
            ];
        }

        $parentMappings = 0;
        $variantMappings = 0;

        foreach (
            $mappings
            as $productId => $mapping
        ) {
            $productId =
                (int) $productId;

            if ($productId <= 0) {
                $errors[] = [
                    'product_id' =>
                        $productId,
                    'reason' =>
                        'INVALID_WOOCOMMERCE_PRODUCT_ID',
                ];

                continue;
            }

            if (!is_array($mapping)) {
                $errors[] = [
                    'product_id' =>
                        $productId,
                    'reason' =>
                        'INVALID_ADOPTION_MAPPING',
                ];

                continue;
            }

            if (
                ($mapping['decision'] ?? '')
                !== 'ADOPT'
            ) {
                $errors[] = [
                    'product_id' =>
                        $productId,
                    'reason' =>
                        'MAPPING_IS_NOT_APPROVED_ADOPT',
                ];

                continue;
            }

            $mappedProductId =
                isset(
                    $mapping[
                        'woocommerce_product_id'
                    ]
                )
                    ? (int) $mapping[
                        'woocommerce_product_id'
                    ]
                    : 0;

            if (
                $mappedProductId
                !== $productId
            ) {
                $errors[] = [
                    'product_id' =>
                        $productId,
                    'mapped_product_id' =>
                        $mappedProductId,
                    'reason' =>
                        'MAPPING_REFERENCES_DIFFERENT_WOOCOMMERCE_PRODUCT',
                ];

                continue;
            }

            $canonicalProductId =
                isset(
                    $mapping[
                        'canonical_product_id'
                    ]
                )
                    ? trim(
                        (string)
                        $mapping[
                            'canonical_product_id'
                        ]
                    )
                    : '';

            if (
                $canonicalProductId
                === ''
            ) {
                $errors[] = [
                    'product_id' =>
                        $productId,
                    'reason' =>
                        'MISSING_CANONICAL_PRODUCT_ID',
                ];

                continue;
            }

            $canonicalProductCode =
                isset(
                    $mapping[
                        'canonical_product_code'
                    ]
                )
                    ? trim(
                        (string)
                        $mapping[
                            'canonical_product_code'
                        ]
                    )
                    : '';

            if (
                $canonicalProductCode
                === ''
            ) {
                $errors[] = [
                    'product_id' =>
                        $productId,
                    'reason' =>
                        'MISSING_CANONICAL_PRODUCT_CODE',
                ];

                continue;
            }

            $parentMappings++;

            $variants =
                isset($mapping['variants'])
                && is_array($mapping['variants'])
                    ? $mapping['variants']
                    : [];

            foreach ($variants as $variant) {

                if (!is_array($variant)) {
                    $errors[] = [
                        'product_id' =>
                            $productId,
                        'reason' =>
                            'INVALID_VARIANT_MAPPING',
                    ];

                    continue;
                }

                $variationId =
                    isset(
                        $variant[
                            'woocommerce_variation_id'
                        ]
                    )
                        ? (int) $variant[
                            'woocommerce_variation_id'
                        ]
                        : 0;

                $canonicalVariantCode =
                    isset(
                        $variant[
                            'canonical_variant_code'
                        ]
                    )
                        ? trim(
                            (string)
                            $variant[
                                'canonical_variant_code'
                            ]
                        )
                        : '';

                /*
                 * Simple product mappings intentionally contain no
                 * WooCommerce variation ownership record.
                 */
                if ($variationId <= 0) {

                    if (
                        $canonicalVariantCode
                        === ''
                    ) {
                        $errors[] = [
                            'product_id' =>
                                $productId,
                            'reason' =>
                                'SIMPLE_VARIANT_MAPPING_HAS_NO_CANONICAL_VARIANT_CODE',
                        ];
                    }

                    continue;
                }

                if (
                    $canonicalVariantCode
                    === ''
                ) {
                    $errors[] = [
                        'product_id' =>
                            $productId,
                        'variation_id' =>
                            $variationId,
                        'reason' =>
                            'VARIABLE_MAPPING_HAS_NO_CANONICAL_VARIANT_CODE',
                    ];

                    continue;
                }

                $variantMappings++;
            }
        }

        if (
            $parentMappings
            !== self::EXPECTED_PARENT_OWNERSHIP
        ) {
            $errors[] = [
                'reason' =>
                    'INVALID_PARENT_OWNERSHIP_COUNT',
                'expected' =>
                    self::EXPECTED_PARENT_OWNERSHIP,
                'actual' =>
                    $parentMappings,
            ];
        }

        if (
            $variantMappings
            !== self::EXPECTED_VARIANT_OWNERSHIP
        ) {
            $errors[] = [
                'reason' =>
                    'INVALID_VARIANT_OWNERSHIP_COUNT',
                'expected' =>
                    self::EXPECTED_VARIANT_OWNERSHIP,
                'actual' =>
                    $variantMappings,
            ];
        }

        return [
            'pass' =>
                count($errors) === 0,

            'approved_mappings' =>
                $approvedMappingCount,

            'parent_mappings' =>
                $parentMappings,

            'variant_mappings' =>
                $variantMappings,

            'errors' =>
                $errors,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Artifact Summary
    |--------------------------------------------------------------------------
    */

    /**
     * Return non-sensitive artifact information suitable for an
     * admin verification result.
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
                isset(
                    $artifact['artifact_id']
                )
                    ? (string)
                        $artifact['artifact_id']
                    : '',

            'snapshot_uuid' =>
                isset(
                    $artifact['snapshot_uuid']
                )
                    ? (string)
                        $artifact['snapshot_uuid']
                    : '',

            'mapping_hash' =>
                isset(
                    $artifact['mapping_hash']
                )
                    ? (string)
                        $artifact['mapping_hash']
                    : '',

            'approved_mapping_count' =>
                isset(
                    $artifact[
                        'approved_mapping_count'
                    ]
                )
                    ? (int)
                        $artifact[
                            'approved_mapping_count'
                        ]
                    : 0,

            'explicit_variant_ownership_count' =>
                isset(
                    $artifact[
                        'explicit_variant_ownership_count'
                    ]
                )
                    ? (int)
                        $artifact[
                            'explicit_variant_ownership_count'
                        ]
                    : 0,

            'created_at' =>
                isset(
                    $artifact['created_at']
                )
                    ? (int)
                        $artifact['created_at']
                    : 0,

            'expires_at' =>
                isset(
                    $artifact['expires_at']
                )
                    ? (int)
                        $artifact['expires_at']
                    : 0,
        ];
    }
}