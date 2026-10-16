<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Projection\Verification;

use BlackPrint\Commerce\Normalization\DTO\CanonicalProduct;
use BlackPrint\Commerce\Normalization\DTO\NormalizationResult;

defined('ABSPATH') || exit;

/**
 * Read-only WooCommerce image health auditor.
 *
 * Step 6.
 *
 * Purpose:
 *
 * - Compare canonical BlackPrint media against current WooCommerce media.
 * - Identify healthy products.
 * - Identify missing/broken WooCommerce images.
 * - Identify canonical products without normal product images.
 * - Identify colour-image-only products.
 * - Produce explicit repair candidates for Step 7.
 * - Report duplicate WooCommerce ownership records separately.
 *
 * HARD SAFETY BOUNDARY:
 *
 * This class:
 *
 * - Does not modify WooCommerce products.
 * - Does not modify variations.
 * - Does not modify ownership metadata.
 * - Does not create attachments.
 * - Does not download media.
 * - Does not delete media.
 * - Does not replace images.
 * - Does not modify SKUs.
 * - Does not modify prices.
 * - Does not modify categories.
 * - Does not reconstruct adoption mappings.
 * - Does not use VerifiedAdoptionMappingStore.
 * - Does not execute Step 5B.
 *
 * Existing WooCommerce products are inspected only through their
 * already-committed BlackPrint ownership metadata.
 *
 * IMPORTANT:
 *
 * Canonical products are the primary unit of counting in this audit.
 *
 * If one canonical product has more than one WooCommerce product record
 * carrying the same committed BlackPrint ownership code, the canonical
 * product is counted once as adopted. The additional WooCommerce records
 * are reported separately as duplicate ownership records.
 */
final class WooCommerceImageHealthAuditor
{
    private const SUPPLIER = 'amrod';

    private const OWNERSHIP_META = '_blackprint_managed';

    private const SUPPLIER_META = '_blackprint_supplier';

    private const PRODUCT_ID_META = '_blackprint_product_id';

    private const PRODUCT_CODE_META = '_blackprint_product_code';

    /**
     * Request-local, read-only indexes rebuilt for every audit.
     *
     * @var array<string, array<int, true>>
     */
    private array $ownedProductIndex = [];

    /**
     * @var array<int, array{thumbnail_id: int, gallery_ids: array<int>}>
     */
    private array $wooImageReferences = [];

    /**
     * @var array<int, bool>
     */
    private array $usableAttachmentIndex = [];

    /**
     * Preserve the ordering returned by the original ownership query.
     *
     * @var array<int, int>
     */
    private array $ownedProductOrder = [];

    /**
     * Audit a normalized canonical snapshot.
     *
     * Canonical products are the primary counting unit.
     *
     * WooCommerce product records are tracked separately so that duplicate
     * ownership records do not inflate the adopted canonical product count.
     *
     * @param string              $snapshotUuid
     * @param NormalizationResult $normalizationResult
     *
     * @return array<string, mixed>
     */
    public function audit(
        string $snapshotUuid,
        NormalizationResult $normalizationResult,
        bool $includeDetailedProducts = true
    ): array {
        if ($snapshotUuid === '') {
            throw new \InvalidArgumentException(
                'Image health audit requires a snapshot UUID.'
            );
        }

        $products = $normalizationResult->products();

        $adoptedCanonicalCodes = [];

        $ownedWooProductIds = [];

        $duplicateOwnershipGroups = [];

        $report = [
            'success' => true,

            'read_only' => true,

            'snapshot_uuid' => $snapshotUuid,

            'normalization' => [
                'normalized' =>
                    $normalizationResult->normalized(),

                'error_count' =>
                    count($normalizationResult->errors()),
            ],

            'summary' => [
                'canonical_products' => 0,

                'adopted_canonical_products' => 0,

                'canonical_products_with_images' => 0,

                'canonical_products_without_images' => 0,

                'woocommerce_products_with_usable_images' => 0,

                'woocommerce_products_missing_images' => 0,

                'woocommerce_products_with_broken_images' => 0,

                'healthy' => 0,

                'repairable' => 0,

                'colour_image_only' => 0,

                'ambiguous' => 0,

                'not_adopted' => 0,

                /*
                 * These are WooCommerce ownership record counts.
                 * They are deliberately separate from canonical-product
                 * counts.
                 */
                'owned_woocommerce_parent_records' => 0,

                'duplicate_ownership_code_groups' => 0,

                'duplicate_ownership_records' => 0,
            ],

            'products' => [],

            'repair_candidates' => [],

            'duplicate_ownership_groups' => [],

            'warnings' => [],
        ];

        $this->buildAuditIndexes();

        for (
            $index = 0;
            $index < $products->count();
            $index++
        ) {
            $canonicalProduct =
                $products->get($index);

            if (
                !$canonicalProduct
                instanceof CanonicalProduct
            ) {
                $report['warnings'][] = [
                    'type' =>
                        'invalid_canonical_product',

                    'index' =>
                        $index,
                ];

                $report['summary']['ambiguous']++;

                continue;
            }

            /*
             * Canonical products are the primary unit of the audit.
             *
             * This counter must therefore increment exactly once for each
             * valid canonical product, regardless of how many WooCommerce
             * records happen to match it.
             */
            $report['summary']['canonical_products']++;

            $canonicalCode =
                $this->extractCanonicalCode(
                    $canonicalProduct
                );

            $canonicalImages =
                $this->extractMediaItems(
                    $canonicalProduct
                        ->media()['images']
                        ?? []
                );

            $canonicalColourImages =
                $this->extractMediaItems(
                    $canonicalProduct
                        ->media()['colour_images']
                        ?? []
                );

            $hasCanonicalImages =
                !empty($canonicalImages);

            $hasCanonicalColourImages =
                !empty($canonicalColourImages);

            if ($hasCanonicalImages) {
                $report['summary']
                    ['canonical_products_with_images']++;
            } elseif ($hasCanonicalColourImages) {
                $report['summary']
                    ['colour_image_only']++;
            } else {
                $report['summary']
                    ['canonical_products_without_images']++;
            }

            /*
             * Canonical data alone does not establish a WooCommerce
             * adoption relationship.
             *
             * We therefore use the committed ownership metadata already
             * present on WooCommerce products. We never reconstruct an
             * adoption mapping here.
             */
            $wooProductIds =
                $this->findOwnedWooProducts(
                    $canonicalProduct
                );

            if (empty($wooProductIds)) {
                $report['summary']['not_adopted']++;

                if ($includeDetailedProducts) {
                    $report['products'][] =
                        $this->buildRow(
                            $canonicalProduct,
                            $canonicalCode,
                            $canonicalImages,
                            $canonicalColourImages,
                            null,
                            'NOT_ADOPTED'
                        );
                }

                continue;
            }

            /*
             * This canonical product has at least one existing WooCommerce
             * ownership record.
             *
             * Count the canonical product once.
             */
            if ($canonicalCode !== '') {
                $adoptedCanonicalCodes[
                    $canonicalCode
                ] = true;
            } else {
                /*
                 * A canonical product with an empty canonical code can still
                 * be observed through WooCommerce IDs, but it cannot safely
                 * participate in duplicate-code grouping.
                 *
                 * The canonical product is still counted as adopted because
                 * findOwnedWooProducts() already established the relationship.
                 */
                $adoptedCanonicalCodes[
                    'canonical_index_' . $index
                ] = true;
            }

            /*
             * Every matching WooCommerce product record is tracked
             * independently.
             *
             * This is intentionally separate from adopted_canonical_codes.
             */
            foreach ($wooProductIds as $wooProductId) {
                $ownedWooProductIds[
                    $wooProductId
                ] = true;
            }

            /*
             * More than one WooCommerce product for the same canonical code
             * is a duplicate ownership condition.
             *
             * This is diagnostic only.
             *
             * No ownership metadata is changed here.
             */
            if (
                $canonicalCode !== ''
                && count($wooProductIds) > 1
            ) {
                $duplicateOwnershipGroups[
                    $canonicalCode
                ] = [
                    'canonical_code' =>
                        $canonicalCode,

                    'woocommerce_product_ids' =>
                        array_values(
                            array_map(
                                'absint',
                                $wooProductIds
                            )
                        ),

                    'count' =>
                        count($wooProductIds),
                ];
            }

            /*
             * Image health remains canonical-product-first.
             *
             * Each matching WooCommerce record is inspected so that the
             * detailed report and repair candidates identify the exact
             * WooCommerce product requiring attention.
             *
             * The adopted canonical product counter is NOT incremented here.
             */
            foreach ($wooProductIds as $wooProductId) {
                $wooState =
                    $this->inspectWooImages(
                        $wooProductId
                    );

                $status =
                    $this->classify(
                        $hasCanonicalImages,
                        $hasCanonicalColourImages,
                        $wooState
                    );

                switch ($status) {
                    case 'HEALTHY':
                        $report['summary']['healthy']++;

                        if (
                            $wooState['usable_image']
                        ) {
                            $report['summary']
                                ['woocommerce_products_with_usable_images']++;
                        }

                        break;

                    case 'MISSING_CANONICAL':
                        break;

                    case 'MISSING_WOOCOMMERCE':
                        $report['summary']
                            ['woocommerce_products_missing_images']++;

                        $report['summary']
                            ['repairable']++;

                        $report['repair_candidates'][] =
                            $this->buildRepairCandidate(
                                $canonicalProduct,
                                $canonicalCode,
                                $canonicalImages,
                                $wooProductId,
                                $status,
                                $wooState
                            );

                        break;

                    case 'BROKEN_WOOCOMMERCE':
                        $report['summary']
                            ['woocommerce_products_with_broken_images']++;

                        $report['summary']
                            ['repairable']++;

                        $report['repair_candidates'][] =
                            $this->buildRepairCandidate(
                                $canonicalProduct,
                                $canonicalCode,
                                $canonicalImages,
                                $wooProductId,
                                $status,
                                $wooState
                            );

                        break;

                    case 'CANONICAL_COLOUR_ONLY':
                        /*
                         * Already counted during canonical media
                         * classification.
                         */
                        break;

                    case 'AMBIGUOUS':
                    default:
                        $report['summary']['ambiguous']++;

                        break;
                }

                if ($includeDetailedProducts) {
                    $report['products'][] =
                        $this->buildRow(
                            $canonicalProduct,
                            $canonicalCode,
                            $canonicalImages,
                            $canonicalColourImages,
                            $wooProductId,
                            $status,
                            $wooState
                        );
                }
            }
        }

        /*
         * Finalize canonical-first adoption counts.
         */
        $report['summary']
            ['adopted_canonical_products'] =
                count(
                    $adoptedCanonicalCodes
                );

        /*
         * Finalize WooCommerce ownership record counts separately.
         */
        $report['summary']
            ['owned_woocommerce_parent_records'] =
                count(
                    $ownedWooProductIds
                );

        /*
         * Finalize duplicate ownership diagnostics.
         */
        $report['summary']
            ['duplicate_ownership_code_groups'] =
                count(
                    $duplicateOwnershipGroups
                );

        $report['summary']
            ['duplicate_ownership_records'] =
                array_sum(
                    array_map(
                        static function (
                            array $group
                        ): int {
                            return max(
                                0,
                                (int) (
                                    $group['count']
                                    ?? 0
                                ) - 1
                            );
                        },
                        $duplicateOwnershipGroups
                    )
                );

        $report['duplicate_ownership_groups'] =
            array_values(
                $duplicateOwnershipGroups
            );

        return $report;
    }

    /**
     * Locate WooCommerce products already marked as BlackPrint managed
     * for the canonical product.
     *
     * This is an inspection-only query.
     *
     * @return array<int>
     */
    /**
     * Build request-local ownership and image indexes using bounded,
     * read-only database queries.
     *
     * No post metadata cache is primed by these reads.
     */
    private function buildAuditIndexes(): void
    {
        global $wpdb;

        $this->ownedProductIndex = [];
        $this->wooImageReferences = [];
        $this->usableAttachmentIndex = [];
        $this->ownedProductOrder = [];

        $wpdb->last_error = '';

        $productIds = get_posts([
            'post_type' => 'product',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => self::OWNERSHIP_META,
                    'value' => 'yes',
                    'compare' => '=',
                ],
                [
                    'key' => self::SUPPLIER_META,
                    'value' => self::SUPPLIER,
                    'compare' => '=',
                ],
            ],
        ]);

        if (!is_array($productIds)) {
            throw new \RuntimeException(
                'Step 6/7B could not load owned WooCommerce product IDs.'
            );
        }

        if ($wpdb->last_error !== '') {
            throw new \RuntimeException(
                'Step 6/7B ownership query failed: '
                . $wpdb->last_error
            );
        }

        $orderedIds = [];

        foreach ($productIds as $productId) {
            $productId = absint($productId);

            if ($productId > 0 && !isset($this->ownedProductOrder[$productId])) {
                $this->ownedProductOrder[$productId] = count($orderedIds);
                $orderedIds[] = $productId;
            }
        }

        $imageAttachmentIds = [];

        $batchSize = 400;

        for ($offset = 0; $offset < count($orderedIds); $offset += $batchSize) {
            $batch = array_slice($orderedIds, $offset, $batchSize);
            $placeholders = implode(',', array_fill(0, count($batch), '%d'));

            $metaKeys = [
                self::PRODUCT_CODE_META,
                self::PRODUCT_ID_META,
                '_thumbnail_id',
                '_product_image_gallery',
            ];

            $keyPlaceholders = implode(
                ',',
                array_fill(0, count($metaKeys), '%s')
            );

            $sql = "
                SELECT post_id, meta_id, meta_key, meta_value
                FROM {$wpdb->postmeta}
                WHERE post_id IN ({$placeholders})
                  AND meta_key IN ({$keyPlaceholders})
                ORDER BY post_id ASC, meta_id ASC
            ";

            $params = array_merge($batch, $metaKeys);

            $wpdb->last_error = '';
            $rows = $wpdb->get_results(
                $wpdb->prepare($sql, $params),
                ARRAY_A
            );

            if (!is_array($rows) || $wpdb->last_error !== '') {
                throw new \RuntimeException(
                    'Step 6/7B product metadata batch failed: '
                    . ($wpdb->last_error ?: 'No result returned.')
                );
            }

            $seenImageMeta = [];

            foreach ($batch as $productId) {
                $this->wooImageReferences[$productId] = [
                    'thumbnail_id' => 0,
                    'gallery_ids' => [],
                ];
            }

            foreach ($rows as $row) {
                $productId = absint($row['post_id'] ?? 0);
                $metaKey = (string) ($row['meta_key'] ?? '');
                $metaValue = (string) ($row['meta_value'] ?? '');

                if ($productId <= 0) {
                    continue;
                }

                if (
                    $metaKey === self::PRODUCT_CODE_META
                    || $metaKey === self::PRODUCT_ID_META
                ) {
                    if ($metaValue !== '') {
                        $this->ownedProductIndex[$metaValue][$productId] = true;
                    }

                    continue;
                }

                if (isset($seenImageMeta[$productId][$metaKey])) {
                    continue;
                }

                if ($metaKey === '_thumbnail_id') {
                    $seenImageMeta[$productId][$metaKey] = true;
                    $attachmentId = absint($metaValue);

                    $this->wooImageReferences[$productId]['thumbnail_id'] =
                        $attachmentId;

                    if ($attachmentId > 0) {
                        $imageAttachmentIds[$attachmentId] = true;
                    }

                    continue;
                }

                if ($metaKey === '_product_image_gallery') {
                    $seenImageMeta[$productId][$metaKey] = true;

                    $galleryIds = array_values(
                        array_filter(
                            array_map(
                                'absint',
                                explode(',', $metaValue)
                            )
                        )
                    );

                    $this->wooImageReferences[$productId]['gallery_ids'] =
                        $galleryIds;

                    foreach ($galleryIds as $attachmentId) {
                        $imageAttachmentIds[$attachmentId] = true;
                    }
                }
            }
        }

        $attachmentIds = array_map(
            'absint',
            array_keys($imageAttachmentIds)
        );

        $uploadInfo = wp_get_upload_dir();

        if (!is_array($uploadInfo) || empty($uploadInfo['basedir'])) {
            throw new \RuntimeException(
                'Step 6/7B could not resolve the WordPress uploads directory.'
            );
        }

        $baseDirectory = (string) $uploadInfo['basedir'];

        for (
            $offset = 0;
            $offset < count($attachmentIds);
            $offset += $batchSize
        ) {
            $batch = array_slice($attachmentIds, $offset, $batchSize);
            $placeholders = implode(',', array_fill(0, count($batch), '%d'));

            $sql = "
                SELECT p.ID, p.post_type, p.post_mime_type,
                       pm.meta_id, pm.meta_value AS attached_file
                FROM {$wpdb->posts} AS p
                LEFT JOIN {$wpdb->postmeta} AS pm
                    ON pm.post_id = p.ID
                   AND pm.meta_key = %s
                WHERE p.ID IN ({$placeholders})
                ORDER BY p.ID ASC, pm.meta_id ASC
            ";

            $params = array_merge(['_wp_attached_file'], $batch);

            $wpdb->last_error = '';
            $rows = $wpdb->get_results(
                $wpdb->prepare($sql, $params),
                ARRAY_A
            );

            if (!is_array($rows) || $wpdb->last_error !== '') {
                throw new \RuntimeException(
                    'Step 6/7B attachment metadata batch failed: '
                    . ($wpdb->last_error ?: 'No result returned.')
                );
            }

            $seenAttachments = [];

            foreach ($rows as $row) {
                $attachmentId = absint($row['ID'] ?? 0);

                if (
                    $attachmentId <= 0
                    || isset($seenAttachments[$attachmentId])
                ) {
                    continue;
                }

                $seenAttachments[$attachmentId] = true;
                $this->usableAttachmentIndex[$attachmentId] = false;

                if (($row['post_type'] ?? '') !== 'attachment') {
                    continue;
                }

                $mimeType = (string) ($row['post_mime_type'] ?? '');

                if (strpos($mimeType, 'image/') !== 0) {
                    continue;
                }

                $attachedFile = $row['attached_file'] ?? null;

                if (!is_string($attachedFile) || $attachedFile === '') {
                    continue;
                }

                $file = $attachedFile;

                $isAbsolutePath =
                    strpos($file, '/') === 0
                    || preg_match('/^[A-Za-z]:[\\\\\/]/', $file) === 1;

                if (!$isAbsolutePath) {
                    $file = path_join($baseDirectory, $file);
                }

                $file = apply_filters(
                    'get_attached_file',
                    $file,
                    $attachmentId
                );

                if (
                    is_string($file)
                    && $file !== ''
                    && is_file($file)
                    && is_readable($file)
                ) {
                    $this->usableAttachmentIndex[$attachmentId] = true;
                }
            }
        }
    }

    /**
     * Locate WooCommerce products already marked as BlackPrint managed
     * for the canonical product.
     *
     * @return array<int>
     */
    private function findOwnedWooProducts(
        CanonicalProduct $canonicalProduct
    ): array {
        $canonicalCode =
            $this->extractCanonicalCode(
                $canonicalProduct
            );

        if ($canonicalCode === '') {
            return [];
        }

        $matches =
            $this->ownedProductIndex[$canonicalCode]
            ?? [];

        if (empty($matches)) {
            return [];
        }

        $productIds = array_map(
            'absint',
            array_keys($matches)
        );

        /*
         * Keep the original get_posts() ordering for matching products.
         * This makes duplicate-ownership diagnostics deterministic and
         * avoids changing the order of detailed Step 6 rows.
         */
        usort(
            $productIds,
            function (int $left, int $right): int {
                return (
                    ($this->ownedProductOrder[$left] ?? PHP_INT_MAX)
                    <=>
                    ($this->ownedProductOrder[$right] ?? PHP_INT_MAX)
                );
            }
        );

        return array_values(
            array_unique($productIds)
        );
    }

    /**
     * Inspect current WooCommerce image state.
     *
     * No writes are performed.
     *
     * @return array<string, mixed>
     */
    private function inspectWooImages(
        int $productId
    ): array {
        $references =
            $this->wooImageReferences[$productId]
            ?? [
                'thumbnail_id' => 0,
                'gallery_ids' => [],
            ];

        $thumbnailId =
            (int) ($references['thumbnail_id'] ?? 0);

        $galleryIds =
            is_array($references['gallery_ids'] ?? null)
                ? $references['gallery_ids']
                : [];

        $usableThumbnail =
            $this->isUsableAttachment(
                $thumbnailId
            );

        $usableGallery = [];

        $brokenGallery = [];

        foreach ($galleryIds as $attachmentId) {
            $attachmentId = absint($attachmentId);

            if (
                $this->isUsableAttachment(
                    $attachmentId
                )
            ) {
                $usableGallery[] =
                    $attachmentId;
            } else {
                $brokenGallery[] =
                    $attachmentId;
            }
        }

        $hasAnyReferencedImage =
            $thumbnailId > 0
            || !empty($galleryIds);

        $hasUsableImage =
            $usableThumbnail
            || !empty($usableGallery);

        $hasBrokenReference =
            (
                $thumbnailId > 0
                && !$usableThumbnail
            )
            || !empty($brokenGallery);

        return [
            'product_id' =>
                $productId,

            'thumbnail_id' =>
                $thumbnailId,

            'thumbnail_usable' =>
                $usableThumbnail,

            'gallery_ids' =>
                array_values(
                    $galleryIds
                ),

            'usable_gallery_ids' =>
                array_values(
                    $usableGallery
                ),

            'broken_gallery_ids' =>
                array_values(
                    $brokenGallery
                ),

            'has_any_referenced_image' =>
                $hasAnyReferencedImage,

            'usable_image' =>
                $hasUsableImage,

            'broken_reference' =>
                $hasBrokenReference,

            'image_count' =>
                (
                    ($usableThumbnail ? 1 : 0)
                    + count($usableGallery)
                ),
        ];
    }

    /**
     * Determine whether an existing attachment is usable.
     *
     * Attachment type, MIME type, file metadata, and file readability
     * were checked while building the bounded attachment index.
     *
     * This method performs no WordPress metadata API calls and no writes.
     */
    private function isUsableAttachment(
        int $attachmentId
    ): bool {
        if ($attachmentId <= 0) {
            return false;
        }

        return $this->usableAttachmentIndex[$attachmentId]
            ?? false;
    }

    /**
     * Classify image health.
     *
     * @param bool                 $hasCanonicalImages
     * @param bool                 $hasCanonicalColourImages
     * @param array<string, mixed> $wooState
     */
    private function classify(
        bool $hasCanonicalImages,
        bool $hasCanonicalColourImages,
        array $wooState
    ): string {
        if (!$hasCanonicalImages) {
            if (
                $hasCanonicalColourImages
                && !$wooState['usable_image']
            ) {
                return 'CANONICAL_COLOUR_ONLY';
            }

            if (
                $wooState['broken_reference']
            ) {
                return 'MISSING_CANONICAL';
            }

            return 'MISSING_CANONICAL';
        }

        if (
            $wooState['usable_image']
            && !$wooState['broken_reference']
        ) {
            return 'HEALTHY';
        }

        if (
            !$wooState['has_any_referenced_image']
        ) {
            return 'MISSING_WOOCOMMERCE';
        }

        if (
            $wooState['broken_reference']
        ) {
            return 'BROKEN_WOOCOMMERCE';
        }

        return 'AMBIGUOUS';
    }

    /**
     * Build the detailed report row.
     *
     * @param array<int, array<string, mixed>> $canonicalImages
     * @param array<int, array<string, mixed>> $canonicalColourImages
     * @param array<string, mixed>|null         $wooState
     *
     * @return array<string, mixed>
     */
    private function buildRow(
        CanonicalProduct $canonicalProduct,
        string $canonicalCode,
        array $canonicalImages,
        array $canonicalColourImages,
        ?int $wooProductId,
        string $status,
        ?array $wooState = null
    ): array {
        return [
            'woo_product_id' =>
                $wooProductId,

            'canonical_code' =>
                $canonicalCode,

            'canonical_images' =>
                $canonicalImages,

            'canonical_image_count' =>
                count($canonicalImages),

            'canonical_colour_images' =>
                $canonicalColourImages,

            'canonical_colour_image_count' =>
                count($canonicalColourImages),

            'woo_image' =>
                $wooState,

            'status' =>
                $status,
        ];
    }

    /**
     * Build an explicit Step 7 repair candidate.
     *
     * @param array<int, array<string, mixed>> $canonicalImages
     * @param array<string, mixed>              $wooState
     *
     * @return array<string, mixed>
     */
    private function buildRepairCandidate(
    CanonicalProduct $canonicalProduct,
    string $canonicalCode,
    array $canonicalImages,
    int $wooProductId,
    string $status,
    array $wooState
): array {
    $canonicalMedia =
        $canonicalProduct->media();

    $canonicalPrimaryImageCandidates =
        $canonicalMedia['images']
        ?? [];

    if (!is_array($canonicalPrimaryImageCandidates)) {
        $canonicalPrimaryImageCandidates = [];
    }

    return [
        'woo_product_id' =>
            $wooProductId,

        'canonical_code' =>
            $canonicalCode,

        'status' =>
            $status,

        /*
         * Existing Step 6 diagnostic representation.
         *
         * Preserve this unchanged for the audit/report contract.
         */
        'canonical_images' =>
            $canonicalImages,

        /*
         * Original normalized canonical image records.
         *
         * Step 7A consumes this structure because deterministic
         * primary-image resolution depends on:
         *
         * - isDefault
         * - urls[].url
         *
         * This field is authoritative for Step 7 image selection.
         */
        'canonical_primary_image_candidates' =>
            $canonicalPrimaryImageCandidates,

        'woo_state' =>
            $wooState,

        'repair_authority' =>
            'canonical_blackprint_media',

        'repair_allowed' =>
            true,

        'requires_deterministic_canonical_image' =>
            true,

        'requires_existing_owned_woocommerce_product' =>
            true,

        'requires_step_7_validation' =>
            true,
    ];
}

    /**
     * Extract the canonical supplier product code.
     *
     * The canonical identity contract uses:
     *
     * - supplier_product_id   = Amrod simpleCode
     * - supplier_product_code = Amrod fullCode
     *
     * WooCommerce ownership metadata `_blackprint_product_code`
     * is committed from supplier_product_code, so image-health
     * matching must use that same canonical identity field.
     */
    private function extractCanonicalCode(
        CanonicalProduct $canonicalProduct
    ): string {
        $identity =
            $canonicalProduct->identity();

        $candidate =
            $identity['supplier_product_code']
            ?? null;

        if (
            is_scalar($candidate)
            && trim((string) $candidate) !== ''
        ) {
            return trim(
                (string) $candidate
            );
        }

        return '';
    }

    /**
     * Normalize media items without assuming a single supplier payload shape.
     *
     * The canonical media contract currently preserves Amrod media data.
     * This helper therefore accepts:
     *
     * - URL strings
     * - scalar identifiers
     * - arrays containing common URL fields
     *
     * It intentionally does not download or validate remote media.
     *
     * @param mixed $media
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractMediaItems(
        mixed $media
    ): array {
        if (!is_array($media)) {
            return [];
        }

        $items = [];

        foreach ($media as $item) {
            if (
                is_string($item)
                || is_numeric($item)
            ) {
                $value =
                    trim(
                        (string) $item
                    );

                if ($value === '') {
                    continue;
                }

                $items[] = [
                    'value' =>
                        $value,

                    'url' =>
                        $this->looksLikeUrl(
                            $value
                        )
                            ? $value
                            : null,
                ];

                continue;
            }

            if (!is_array($item)) {
                continue;
            }

            $url =
                $this->extractUrl(
                    $item
                );

            $items[] = [
                'value' =>
                    $url !== ''
                        ? $url
                        : $item,

                'url' =>
                    $url !== ''
                        ? $url
                        : null,

                'raw' =>
                    $item,
            ];
        }

        return $items;
    }

    /**
     * Extract a URL from a media item without making assumptions about
     * the complete supplier payload.
     *
     * @param array<string, mixed> $item
     */
    private function extractUrl(
        array $item
    ): string {
        $keys = [
            'url',
            'image',
            'image_url',
            'imageUrl',
            'src',
            'source',
            'uri',
            'href',
        ];

        foreach ($keys as $key) {
            if (
                isset($item[$key])
                && is_scalar($item[$key])
            ) {
                $value =
                    trim(
                        (string) $item[$key]
                    );

                if (
                    $value !== ''
                    && $this->looksLikeUrl(
                        $value
                    )
                ) {
                    return $value;
                }
            }
        }

        return '';
    }

    private function looksLikeUrl(
        string $value
    ): bool {
        return (bool) preg_match(
            '#^https?://#i',
            $value
        );
    }
}
