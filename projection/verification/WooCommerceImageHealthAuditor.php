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
 */
final class WooCommerceImageHealthAuditor
{
    private const SUPPLIER = 'amrod';

    private const OWNERSHIP_META = '_blackprint_managed';

    private const SUPPLIER_META = '_blackprint_supplier';

    private const PRODUCT_ID_META = '_blackprint_product_id';

    private const PRODUCT_CODE_META = '_blackprint_product_code';

    /**
     * Audit a normalized canonical snapshot.
     *
     * @param string            $snapshotUuid
     * @param NormalizationResult $normalizationResult
     *
     * @return array<string, mixed>
     */
    public function audit(
        string $snapshotUuid,
        NormalizationResult $normalizationResult
    ): array {
        if ($snapshotUuid === '') {
            throw new \InvalidArgumentException(
                'Image health audit requires a snapshot UUID.'
            );
        }

        $products = $normalizationResult->products();

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
                'owned_woocommerce_parents' => 0,
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
            ],

            'products' => [],

            'repair_candidates' => [],

            'warnings' => [],
        ];

        for ($index = 0; $index < $products->count(); $index++) {
            $canonicalProduct = $products->get($index);

            if (!$canonicalProduct instanceof CanonicalProduct) {
                $report['warnings'][] = [
                    'type' => 'invalid_canonical_product',
                    'index' => $index,
                ];

                $report['summary']['ambiguous']++;

                continue;
            }

            $report['summary']['canonical_products']++;

            $canonical = $canonicalProduct->toArray();

            $identity = $canonicalProduct->identity();

            $canonicalCode =
                $this->extractCanonicalCode(
                    $canonicalProduct
                );

            $canonicalImages =
                $this->extractMediaItems(
                    $canonicalProduct->media()['images'] ?? []
                );

            $canonicalColourImages =
                $this->extractMediaItems(
                    $canonicalProduct->media()['colour_images'] ?? []
                );

            $hasCanonicalImages =
                !empty($canonicalImages);

            $hasCanonicalColourImages =
                !empty($canonicalColourImages);

            if ($hasCanonicalImages) {
                $report['summary']['canonical_products_with_images']++;
            } elseif ($hasCanonicalColourImages) {
                $report['summary']['colour_image_only']++;
            } else {
                $report['summary']['canonical_products_without_images']++;
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

                $report['products'][] =
                    $this->buildRow(
                        $canonicalProduct,
                        $canonicalCode,
                        $canonicalImages,
                        $canonicalColourImages,
                        null,
                        'NOT_ADOPTED'
                    );

                continue;
            }

            foreach ($wooProductIds as $wooProductId) {
                $report['summary']['owned_woocommerce_parents']++;

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

                        if ($wooState['usable_image']) {
                            $report['summary']['woocommerce_products_with_usable_images']++;
                        }

                        break;

                    case 'MISSING_CANONICAL':
                        break;

                    case 'MISSING_WOOCOMMERCE':
                        $report['summary']['woocommerce_products_missing_images']++;
                        $report['summary']['repairable']++;

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
                        $report['summary']['woocommerce_products_with_broken_images']++;
                        $report['summary']['repairable']++;

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
                        // Already counted during canonical media classification.
                        break;

                    case 'AMBIGUOUS':
                    default:
                        $report['summary']['ambiguous']++;

                        break;
                }

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

        $productIds =
            get_posts(
                [
                    'post_type' =>
                        'product',

                    'post_status' =>
                        'any',

                    'posts_per_page' =>
                        -1,

                    'fields' =>
                        'ids',

                    'meta_query' =>
                        [
                            'relation' =>
                                'AND',

                            [
                                'key' =>
                                    self::OWNERSHIP_META,

                                'value' =>
                                    'yes',

                                'compare' =>
                                    '=',

                            ],

                            [
                                'key' =>
                                    self::SUPPLIER_META,

                                'value' =>
                                    self::SUPPLIER,

                                'compare' =>
                                    '=',

                            ],

                            [
                                'relation' =>
                                    'OR',

                                [
                                    'key' =>
                                        self::PRODUCT_CODE_META,

                                    'value' =>
                                        $canonicalCode,

                                    'compare' =>
                                        '=',

                                ],

                                [
                                    'key' =>
                                        self::PRODUCT_ID_META,

                                    'value' =>
                                        $canonicalCode,

                                    'compare' =>
                                        '=',

                                ],
                            ],
                        ],
                ]
            );

        if (!is_array($productIds)) {
            return [];
        }

        return array_values(
            array_unique(
                array_map(
                    'absint',
                    $productIds
                )
            )
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
        $thumbnailId =
            (int) get_post_thumbnail_id(
                $productId
            );

        $galleryIds =
            get_post_meta(
                $productId,
                '_product_image_gallery',
                true
            );

        $galleryIds =
            is_string($galleryIds)
                ? array_filter(
                    array_map(
                        'absint',
                        explode(
                            ',',
                            $galleryIds
                        )
                    )
                )
                : [];

        $usableThumbnail =
            $this->isUsableAttachment(
                $thumbnailId
            );

        $usableGallery =
            [];

        $brokenGallery =
            [];

        foreach ($galleryIds as $attachmentId) {
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
     * Determine whether an attachment is usable.
     *
     * This checks existing WordPress media only.
     * It never creates or modifies anything.
     */
    private function isUsableAttachment(
        int $attachmentId
    ): bool {
        if ($attachmentId <= 0) {
            return false;
        }

        if (
            get_post_type($attachmentId)
            !== 'attachment'
        ) {
            return false;
        }

        $mimeType =
            get_post_mime_type(
                $attachmentId
            );

        if (
            !is_string($mimeType)
            || strpos(
                $mimeType,
                'image/'
            ) !== 0
        ) {
            return false;
        }

        $file =
            get_attached_file(
                $attachmentId
            );

        if (
            !is_string($file)
            || $file === ''
        ) {
            return false;
        }

        return is_file($file)
            && is_readable($file);
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
        return [
            'woo_product_id' =>
                $wooProductId,

            'canonical_code' =>
                $canonicalCode,

            'status' =>
                $status,

            'canonical_images' =>
                $canonicalImages,

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