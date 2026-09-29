<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Projection\Adoption;

defined('ABSPATH') || exit;

/**
 * Controlled Ownership Adopter.
 *
 * Step 5 of the BlackPrint OS adoption pipeline.
 *
 * Responsibilities:
 *
 * - Consume ONLY verified Step 4 adoption mappings.
 * - Establish BlackPrint ownership on existing WooCommerce objects.
 * - Never create WooCommerce products or variations.
 * - Never delete WooCommerce products or variations.
 * - Never modify SKUs.
 * - Never modify images.
 * - Never invent or infer mappings.
 * - Never adopt REVIEW or DO NOT ADOPT products.
 * - Never expand incomplete-family mappings.
 *
 * Dry-run mode performs a complete audit without writing metadata.
 */
final class ControlledOwnershipAdopter
{
    private const META_MANAGED      = '_blackprint_managed';
    private const META_SUPPLIER     = '_blackprint_supplier';
    private const META_PRODUCT_ID   = '_blackprint_product_id';
    private const META_PRODUCT_CODE = '_blackprint_product_code';
    private const META_VARIANT_CODE = '_blackprint_variant_code';

    private const SUPPLIER = 'amrod';

    /**
     * Run controlled ownership adoption.
     *
     * @param array $adoptionMappings
     * @param array $verifiedWooCommerceProducts
     * @param array $verifiedCanonicalProducts
     * @param array $verifiedCanonicalVariants
     * @param bool  $dryRun
     *
     * @return array
     */
    public function run(
        array $adoptionMappings,
        array $verifiedWooCommerceProducts,
        array $verifiedCanonicalProducts,
        array $verifiedCanonicalVariants,
        bool $dryRun = true
    ): array {
        $result = $this->createResult($dryRun);

        /*
         * ---------------------------------------------------------------
         * SAFETY GATE
         * ---------------------------------------------------------------
         *
         * Step 5 must never silently operate on an incomplete verification
         * result.
         */
        $this->validateVerificationScope(
            $verifiedWooCommerceProducts,
            $verifiedCanonicalProducts,
            $verifiedCanonicalVariants,
            $result
        );

        if ($result['errors'] !== []) {
            $result['status'] = 'FAIL';

            return $result;
        }

        /*
         * ---------------------------------------------------------------
         * PRODUCT OWNERSHIP
         * ---------------------------------------------------------------
         */
        foreach ($verifiedWooCommerceProducts as $woocommerceProductId => $canonicalProductId) {

            $woocommerceProductId = (int) $woocommerceProductId;
            $canonicalProductId   = (int) $canonicalProductId;

            $result['products_examined']++;

            if (!isset($adoptionMappings[$woocommerceProductId])) {
                $result['errors'][] = [
                    'code'    => 'MISSING_ADOPTION_MAPPING',
                    'message' => 'Verified WooCommerce product has no adoption mapping.',
                    'woocommerce_product_id' => $woocommerceProductId,
                    'canonical_product_id'   => $canonicalProductId,
                ];

                continue;
            }

            $mapping = $adoptionMappings[$woocommerceProductId];

            if (
                !is_array($mapping)
                || ($mapping['decision'] ?? null) !== 'ADOPT'
            ) {
                $result['errors'][] = [
                    'code'    => 'INVALID_ADOPTION_DECISION',
                    'message' => 'Verified product does not contain an ADOPT mapping.',
                    'woocommerce_product_id' => $woocommerceProductId,
                ];

                continue;
            }

            if (
                (int) ($mapping['canonical_product_id'] ?? 0)
                !== $canonicalProductId
            ) {
                $result['errors'][] = [
                    'code'    => 'CANONICAL_PRODUCT_ID_MISMATCH',
                    'message' => 'Verified canonical product ID differs from adoption mapping.',
                    'woocommerce_product_id' => $woocommerceProductId,
                    'verified_canonical_product_id' =>
                        $canonicalProductId,
                    'mapping_canonical_product_id' =>
                        (int) ($mapping['canonical_product_id'] ?? 0),
                ];

                continue;
            }

            $canonicalProductCode =
                (string) ($mapping['canonical_product_code'] ?? '');

            $productDecision = $this->inspectProductOwnership(
                $woocommerceProductId,
                $canonicalProductId,
                $canonicalProductCode
            );

            $this->recordProductDecision(
                $result,
                $woocommerceProductId,
                $canonicalProductId,
                $canonicalProductCode,
                $productDecision
            );

            if (
                !$dryRun
                && $productDecision['decision'] === 'ADOPT'
            ) {
                $this->writeProductOwnership(
                    $woocommerceProductId,
                    $canonicalProductId,
                    $canonicalProductCode
                );
            }
        }

        /*
         * ---------------------------------------------------------------
         * VARIANT OWNERSHIP
         * ---------------------------------------------------------------
         *
         * IMPORTANT:
         *
         * We iterate ONLY the variants present in the verified mapping.
         *
         * We deliberately do NOT use get_children() here.
         *
         * This preserves the Step 4 boundary around incomplete families.
         */
        foreach ($verifiedCanonicalVariants as $canonicalVariantCode => $variantReference) {

            $canonicalVariantCode = (string) $canonicalVariantCode;

            $result['variants_examined']++;

            if (!is_array($variantReference)) {
                $result['errors'][] = [
                    'code' => 'INVALID_VERIFIED_VARIANT_REFERENCE',
                    'message' => 'Verified canonical variant reference is not an array.',
                    'canonical_variant_code' => $canonicalVariantCode,
                ];

                continue;
            }

            $woocommerceProductId =
                (int) ($variantReference['woocommerce_product_id'] ?? 0);

            $woocommerceVariationId =
                $variantReference['woocommerce_variation_id'] ?? null;

            if ($woocommerceProductId <= 0) {
                $result['errors'][] = [
                    'code' => 'INVALID_VARIANT_PRODUCT_ID',
                    'message' => 'Verified variant has no valid WooCommerce parent/product ID.',
                    'canonical_variant_code' => $canonicalVariantCode,
                ];

                continue;
            }

            /*
             * A null variation ID means this is a simple direct-variant
             * product. The WooCommerce product itself represents the
             * canonical variant.
             */
            $targetId = $woocommerceVariationId !== null
                ? (int) $woocommerceVariationId
                : $woocommerceProductId;

            if ($targetId <= 0) {
                $result['errors'][] = [
                    'code' => 'INVALID_VARIANT_TARGET',
                    'message' => 'Verified variant has no valid WooCommerce target.',
                    'canonical_variant_code' => $canonicalVariantCode,
                ];

                continue;
            }

            $decision = $this->inspectVariantOwnership(
                $targetId,
                $canonicalVariantCode,
                $woocommerceVariationId !== null
            );

            $this->recordVariantDecision(
                $result,
                $targetId,
                $canonicalVariantCode,
                $decision
            );

            if (
                !$dryRun
                && $decision['decision'] === 'ADOPT'
            ) {
                $this->writeVariantOwnership(
                    $targetId,
                    $canonicalVariantCode
                );
            }
        }

        /*
         * ---------------------------------------------------------------
         * FINAL SAFETY REPORT
         * ---------------------------------------------------------------
         */
        $result['status'] =
            $result['errors'] === []
                && $result['ownership_conflicts'] === 0
                ? 'PASS'
                : 'FAIL';

        return $result;
    }

    /**
     * Validate the verified Step 4 scope.
     */
    private function validateVerificationScope(
        array $verifiedWooCommerceProducts,
        array $verifiedCanonicalProducts,
        array $verifiedCanonicalVariants,
        array &$result
    ): void {
        $woocommerceProductCount =
            count($verifiedWooCommerceProducts);

        $canonicalProductCount =
            count($verifiedCanonicalProducts);

        $canonicalVariantCount =
            count($verifiedCanonicalVariants);

        if ($woocommerceProductCount !== 3710) {
            $result['errors'][] = [
                'code' => 'INVALID_VERIFIED_PRODUCT_COUNT',
                'message' =>
                    'Step 5 requires exactly 3710 verified WooCommerce products.',
                'expected' => 3710,
                'actual' => $woocommerceProductCount,
            ];
        }

        if ($canonicalProductCount !== 3710) {
            $result['errors'][] = [
                'code' => 'INVALID_VERIFIED_CANONICAL_PRODUCT_COUNT',
                'message' =>
                    'Step 5 requires exactly 3710 verified canonical products.',
                'expected' => 3710,
                'actual' => $canonicalProductCount,
            ];
        }

        if ($canonicalVariantCount !== 21797) {
            $result['errors'][] = [
                'code' => 'INVALID_VERIFIED_VARIANT_COUNT',
                'message' =>
                    'Step 5 requires exactly 21797 verified canonical variants.',
                'expected' => 21797,
                'actual' => $canonicalVariantCount,
            ];
        }

        /*
         * Both verification maps must represent the same canonical product
         * population.
         */
        foreach ($verifiedWooCommerceProducts as $woocommerceProductId => $canonicalProductId) {

            if (
                !isset($verifiedCanonicalProducts[$canonicalProductId])
                || (int) $verifiedCanonicalProducts[$canonicalProductId]
                    !== (int) $woocommerceProductId
            ) {
                $result['errors'][] = [
                    'code' => 'PRODUCT_VERIFICATION_MAP_MISMATCH',
                    'message' =>
                        'Verified WooCommerce and canonical product maps disagree.',
                    'woocommerce_product_id' =>
                        (int) $woocommerceProductId,
                    'canonical_product_id' =>
                        (int) $canonicalProductId,
                ];
            }
        }
    }

    /**
     * Inspect existing product ownership.
     */
    private function inspectProductOwnership(
        int $productId,
        int $canonicalProductId,
        string $canonicalProductCode
    ): array {
        if (get_post_type($productId) !== 'product') {
            return [
                'decision' => 'ERROR',
                'reason'   => 'WooCommerce product does not exist as a product post.',
            ];
        }

        $expected = [
            self::META_MANAGED      => 'yes',
            self::META_SUPPLIER     => self::SUPPLIER,
            self::META_PRODUCT_ID   => (string) $canonicalProductId,
            self::META_PRODUCT_CODE => $canonicalProductCode,
        ];

        return $this->inspectOwnershipMetadata(
            $productId,
            $expected,
            'product'
        );
    }

    /**
     * Inspect existing variant ownership.
     */
    private function inspectVariantOwnership(
        int $targetId,
        string $canonicalVariantCode,
        bool $isVariation
    ): array {
        $expectedPostType = $isVariation
            ? 'product_variation'
            : 'product';

        if (get_post_type($targetId) !== $expectedPostType) {
            return [
                'decision' => 'ERROR',
                'reason'   =>
                    'WooCommerce variant target has the wrong post type.',
            ];
        }

        $expected = [
            self::META_MANAGED      => 'yes',
            self::META_SUPPLIER     => self::SUPPLIER,
            self::META_VARIANT_CODE => $canonicalVariantCode,
        ];

        return $this->inspectOwnershipMetadata(
            $targetId,
            $expected,
            'variant'
        );
    }

    /**
     * Inspect ownership metadata without modifying anything.
     */
    private function inspectOwnershipMetadata(
        int $postId,
        array $expected,
        string $objectType
    ): array {
        $missing = [];
        $conflicts = [];
        $duplicates = [];

        foreach ($expected as $metaKey => $expectedValue) {

            $values = get_post_meta(
                $postId,
                $metaKey,
                false
            );

            if ($values === []) {
                $missing[$metaKey] = $expectedValue;

                continue;
            }

            /*
             * Multiple metadata rows are an anomaly.
             *
             * We do not silently collapse them during controlled adoption.
             */
            $uniqueValues = [];

            foreach ($values as $value) {
                $value = (string) $value;

                if (!in_array($value, $uniqueValues, true)) {
                    $uniqueValues[] = $value;
                }
            }

            if (count($uniqueValues) > 1) {
                $conflicts[$metaKey] = [
                    'expected' => $expectedValue,
                    'actual'   => $uniqueValues,
                ];

                continue;
            }

            $actualValue = $uniqueValues[0];

            if ($actualValue !== (string) $expectedValue) {
                $conflicts[$metaKey] = [
                    'expected' => $expectedValue,
                    'actual'   => $actualValue,
                ];

                continue;
            }

            if (count($values) > 1) {
                $duplicates[$metaKey] = [
                    'value' => $actualValue,
                    'count' => count($values),
                ];
            }
        }

        if ($conflicts !== []) {
            return [
                'decision'  => 'CONFLICT',
                'missing'   => $missing,
                'conflicts' => $conflicts,
                'duplicates' => $duplicates,
            ];
        }

        if ($missing !== []) {
            return [
                'decision'  => 'ADOPT',
                'missing'   => $missing,
                'conflicts' => [],
                'duplicates' => $duplicates,
            ];
        }

        return [
            'decision'  => 'ALREADY_CORRECT',
            'missing'   => [],
            'conflicts' => [],
            'duplicates' => $duplicates,
        ];
    }

    /**
     * Write product ownership.
     */
    private function writeProductOwnership(
        int $productId,
        int $canonicalProductId,
        string $canonicalProductCode
    ): void {
        update_post_meta(
            $productId,
            self::META_MANAGED,
            'yes'
        );

        update_post_meta(
            $productId,
            self::META_SUPPLIER,
            self::SUPPLIER
        );

        update_post_meta(
            $productId,
            self::META_PRODUCT_ID,
            (string) $canonicalProductId
        );

        update_post_meta(
            $productId,
            self::META_PRODUCT_CODE,
            $canonicalProductCode
        );
    }

    /**
     * Write variant ownership.
     */
    private function writeVariantOwnership(
        int $targetId,
        string $canonicalVariantCode
    ): void {
        update_post_meta(
            $targetId,
            self::META_MANAGED,
            'yes'
        );

        update_post_meta(
            $targetId,
            self::META_SUPPLIER,
            self::SUPPLIER
        );

        update_post_meta(
            $targetId,
            self::META_VARIANT_CODE,
            $canonicalVariantCode
        );
    }

    /**
     * Record product decision.
     */
    private function recordProductDecision(
        array &$result,
        int $productId,
        int $canonicalProductId,
        string $canonicalProductCode,
        array $decision
    ): void {
        switch ($decision['decision']) {

            case 'ADOPT':
                $result['products_requiring_adoption']++;

                break;

            case 'ALREADY_CORRECT':
                $result['products_already_correctly_owned']++;

                break;

            case 'CONFLICT':
                $result['ownership_conflicts']++;

                $result['conflict_samples'][] = [
                    'object_type' => 'product',
                    'woocommerce_product_id' => $productId,
                    'canonical_product_id' => $canonicalProductId,
                    'canonical_product_code' => $canonicalProductCode,
                    'details' => $decision['conflicts'] ?? [],
                ];

                break;

            case 'ERROR':
            default:
                $result['errors'][] = [
                    'code' => 'PRODUCT_OWNERSHIP_INSPECTION_ERROR',
                    'message' => $decision['reason'] ?? 'Unknown error.',
                    'woocommerce_product_id' => $productId,
                ];

                break;
        }

        if (
            !empty($decision['duplicates'])
            && count($result['metadata_anomaly_samples']) < 20
        ) {
            $result['metadata_anomaly_samples'][] = [
                'object_type' => 'product',
                'woocommerce_product_id' => $productId,
                'duplicates' => $decision['duplicates'],
            ];
        }
    }

    /**
     * Record variant decision.
     */
    private function recordVariantDecision(
        array &$result,
        int $targetId,
        string $canonicalVariantCode,
        array $decision
    ): void {
        switch ($decision['decision']) {

            case 'ADOPT':
                $result['variants_requiring_adoption']++;

                break;

            case 'ALREADY_CORRECT':
                $result['variants_already_correctly_owned']++;

                break;

            case 'CONFLICT':
                $result['ownership_conflicts']++;

                $result['conflict_samples'][] = [
                    'object_type' => 'variant',
                    'woocommerce_target_id' => $targetId,
                    'canonical_variant_code' => $canonicalVariantCode,
                    'details' => $decision['conflicts'] ?? [],
                ];

                break;

            case 'ERROR':
            default:
                $result['errors'][] = [
                    'code' => 'VARIANT_OWNERSHIP_INSPECTION_ERROR',
                    'message' => $decision['reason'] ?? 'Unknown error.',
                    'woocommerce_target_id' => $targetId,
                    'canonical_variant_code' => $canonicalVariantCode,
                ];

                break;
        }

        if (
            !empty($decision['duplicates'])
            && count($result['metadata_anomaly_samples']) < 20
        ) {
            $result['metadata_anomaly_samples'][] = [
                'object_type' => 'variant',
                'woocommerce_target_id' => $targetId,
                'canonical_variant_code' => $canonicalVariantCode,
                'duplicates' => $decision['duplicates'],
            ];
        }
    }

    /**
     * Create result structure.
     */
    private function createResult(bool $dryRun): array
    {
        return [
            'dry_run' => $dryRun,

            'verified_products' => 0,
            'verified_variants' => 0,

            'products_examined' => 0,
            'products_requiring_adoption' => 0,
            'products_already_correctly_owned' => 0,

            'variants_examined' => 0,
            'variants_requiring_adoption' => 0,
            'variants_already_correctly_owned' => 0,

            'ownership_conflicts' => 0,

            'review_products_touched' => 0,
            'do_not_adopt_products_touched' => 0,
            'unverified_variants_touched' => 0,
            'incomplete_family_variants_added' => 0,

            'products_created' => 0,
            'variations_created' => 0,
            'products_deleted' => 0,
            'variations_deleted' => 0,

            'mappings_created' => 0,

            'metadata_anomaly_samples' => [],
            'conflict_samples' => [],
            'errors' => [],

            'status' => 'NOT_RUN',
        ];
    }
}