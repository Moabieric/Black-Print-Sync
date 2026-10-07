<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Projection\Verification;

use BlackPrint\Commerce\Projection\Media\WordPressImageImporter;

defined('ABSPATH') || exit;

/**
 * Controlled Step 7B WooCommerce image repairer.
 *
 * Step 7B — Safe Image Repair.
 *
 * HARD SAFETY BOUNDARY:
 *
 * This class may repair ONLY the featured image of an existing
 * WooCommerce product represented by an explicit Step 6 repair
 * candidate and validated against a persisted Step 7A resolution.
 *
 * It must never:
 *
 * - Create products.
 * - Create variations.
 * - Delete products.
 * - Modify ownership metadata.
 * - Modify product identity.
 * - Modify SKU.
 * - Modify title.
 * - Modify description.
 * - Modify price.
 * - Modify categories.
 * - Modify attributes.
 * - Modify variation data.
 * - Modify relationships.
 * - Modify branding.
 * - Modify product status.
 * - Reconstruct adoption mappings.
 * - Execute Step 5B.
 * - Re-run Step 7A canonical image resolution.
 *
 * Step 7A is authoritative for canonical primary-image selection.
 * This class consumes the persisted Step 7A decision.
 */
final class WooCommerceSafeImageRepairer
{
    private const OWNERSHIP_META =
        '_blackprint_managed';

    private const SUPPLIER_META =
        '_blackprint_supplier';

    private const PRODUCT_ID_META =
        '_blackprint_product_id';

    private const PRODUCT_CODE_META =
        '_blackprint_product_code';

    private const SUPPLIER =
        'amrod';

    private CanonicalPrimaryImageResolutionStore $resolutionStore;

    private WordPressImageImporter $importer;

    public function __construct(
        ?CanonicalPrimaryImageResolutionStore $resolutionStore = null,
        ?WordPressImageImporter $importer = null
    ) {
        $this->resolutionStore =
            $resolutionStore
                ?? new CanonicalPrimaryImageResolutionStore();

        $this->importer =
            $importer
                ?? new WordPressImageImporter();
    }

    /**
     * Repair one explicit Step 6 candidate.
     *
     * The candidate must be validated against the persisted Step 7A
     * resolution artifact before any image import is attempted.
     *
     * @param array<string, mixed> $candidate
     *
     * @return array<string, mixed>
     */
    public function repair(
        array $candidate
    ): array {
        $validation =
            $this->validateCandidate($candidate);

        if (! $validation['valid']) {
            return $this->result(
                $candidate,
                (string) $validation['status'],
                (string) $validation['error']
            );
        }

        $productId =
            (int) $candidate['woo_product_id'];

        $canonicalCode =
            trim(
                (string) $candidate['canonical_code']
            );

        /*
         * The Step 7A artifact is the authoritative canonical
         * primary-image decision.
         *
         * Do not reconstruct or re-run Step 7A resolution here.
         */
        $resolution =
            $this->loadStep7AResolution(
                $candidate
            );

        if (! $resolution['valid']) {
            return $this->result(
                $candidate,
                (string) $resolution['status'],
                (string) $resolution['error']
            );
        }

        $resolvedImage =
            $resolution['resolution'];

        $imageUrl =
            trim(
                (string) (
                    $resolvedImage['url']
                    ?? ''
                )
            );

        if ($imageUrl === '') {
            return $this->result(
                $candidate,
                'CANONICAL_IMAGE_UNAVAILABLE',
                'Persisted Step 7A resolution contains no usable canonical image URL.'
            );
        }

        /*
         * Re-read the actual WooCommerce product immediately
         * before any mutation.
         */
        $product =
            get_post($productId);

        if (
            ! $product
            || $product->post_type !== 'product'
        ) {
            return $this->result(
                $candidate,
                'PRODUCT_NOT_FOUND',
                'Existing WooCommerce product no longer exists.'
            );
        }

        /*
         * Ownership must be revalidated immediately before write.
         */
        $ownership =
            $this->validateOwnership(
                $productId,
                $canonicalCode
            );

        if (! $ownership['valid']) {
            return $this->result(
                $candidate,
                (string) $ownership['status'],
                (string) $ownership['error']
            );
        }

        /*
         * Re-check the current image state.
         *
         * If another process repaired the product after Step 6,
         * do not create another attachment.
         */
        $currentThumbnailId =
            (int) get_post_thumbnail_id(
                $productId
            );

        if ($currentThumbnailId > 0) {
            $currentThumbnail =
                get_post(
                    $currentThumbnailId
                );

            if (
                $currentThumbnail
                && $currentThumbnail->post_type === 'attachment'
                && $this->attachmentIsUsable(
                    $currentThumbnailId
                )
            ) {
                return $this->result(
                    $candidate,
                    'ALREADY_HEALTHY',
                    ''
                ) + [
                    'attachment_id' =>
                        $currentThumbnailId,
                ];
            }
        }

        /*
         * Import the canonical image selected by the persisted
         * Step 7A artifact.
         *
         * Existing/broken attachments are intentionally preserved.
         */
        $import =
            $this->importer->import(
                $imageUrl,
                $productId
            );

        if (
            empty($import['success'])
        ) {
            return $this->result(
                $candidate,
                (string) (
                    $import['status']
                    ?? 'IMPORT_FAILED'
                ),
                (string) (
                    $import['error']
                    ?? 'Canonical image import failed.'
                )
            );
        }

        $attachmentId =
            (int) (
                $import['attachment_id']
                ?? 0
            );

        if ($attachmentId <= 0) {
            return $this->result(
                $candidate,
                'ATTACHMENT_INVALID',
                'Image import returned no valid attachment ID.'
            );
        }

        /*
         * Final ownership check immediately before the featured
         * image assignment.
         *
         * This protects against an ownership change between
         * import and product mutation.
         */
        $ownership =
            $this->validateOwnership(
                $productId,
                $canonicalCode
            );

        if (! $ownership['valid']) {
            return $this->result(
                $candidate,
                (string) $ownership['status'],
                (string) $ownership['error']
            ) + [
                'attachment_id' =>
                    $attachmentId,
            ];
        }

        /*
         * Only the featured image is changed.
         *
         * Gallery/media collections remain untouched.
         */
        $assigned =
            set_post_thumbnail(
                $productId,
                $attachmentId
            );

        if (! $assigned) {
            return $this->result(
                $candidate,
                'FEATURED_IMAGE_ASSIGNMENT_FAILED',
                'WordPress failed to assign the imported attachment as the featured image.'
            ) + [
                'attachment_id' =>
                    $attachmentId,
            ];
        }

        /*
         * Verify the actual persisted featured-image state.
         */
        $verifiedThumbnailId =
            (int) get_post_thumbnail_id(
                $productId
            );

        if (
            $verifiedThumbnailId !== $attachmentId
        ) {
            return $this->result(
                $candidate,
                'FEATURED_IMAGE_ASSIGNMENT_FAILED',
                'Featured image assignment could not be verified.'
            ) + [
                'attachment_id' =>
                    $attachmentId,
                'verified_attachment_id' =>
                    $verifiedThumbnailId,
            ];
        }

        return $this->result(
            $candidate,
            'REPAIR_VERIFIED',
            ''
        ) + [
            'attachment_id' =>
                $attachmentId,
            'image_url' =>
                $imageUrl,
            'verified_attachment_id' =>
                $verifiedThumbnailId,
        ];
    }

    /**
     * Load and validate the persisted Step 7A resolution.
     *
     * The Step 7A artifact must:
     *
     * - exist;
     * - match the candidate snapshot;
     * - contain the canonical code;
     * - contain a RESOLVED result;
     * - contain a non-empty URL.
     *
     * @param array<string, mixed> $candidate
     *
     * @return array<string, mixed>
     */
    private function loadStep7AResolution(
        array $candidate
    ): array {
        $artifactId =
            trim(
                (string) (
                    $candidate['step_7a_artifact_id']
                    ?? ''
                )
            );

        if ($artifactId === '') {
            return [
                'valid' => false,
                'status' => 'STEP_7A_ARTIFACT_REQUIRED',
                'error' =>
                    'Step 7B requires an explicit persisted Step 7A artifact ID.',
            ];
        }

        $artifact =
            $this->resolutionStore->load(
                $artifactId
            );

        if (! is_array($artifact)) {
            return [
                'valid' => false,
                'status' => 'STEP_7A_ARTIFACT_NOT_FOUND',
                'error' =>
                    'The required Step 7A resolution artifact could not be loaded.',
            ];
        }

        $candidateSnapshotUuid =
            trim(
                (string) (
                    $candidate['snapshot_uuid']
                    ?? ''
                )
            );

        $artifactSnapshotUuid =
            trim(
                (string) (
                    $artifact['snapshot_uuid']
                    ?? ''
                )
            );

        if (
            $candidateSnapshotUuid === ''
            || $artifactSnapshotUuid === ''
            || $candidateSnapshotUuid
                !== $artifactSnapshotUuid
        ) {
            return [
                'valid' => false,
                'status' => 'STEP_7A_SNAPSHOT_MISMATCH',
                'error' =>
                    'Step 7A resolution artifact does not match the candidate snapshot.',
            ];
        }

        $canonicalCode =
            trim(
                (string) (
                    $candidate['canonical_code']
                    ?? ''
                )
            );

        if ($canonicalCode === '') {
            return [
                'valid' => false,
                'status' => 'INVALID_REPAIR_CANDIDATE',
                'error' =>
                    'Repair candidate does not contain a canonical product code.',
            ];
        }

        $resolutions =
            $artifact['resolutions']
            ?? null;

        if (!is_array($resolutions)) {
            return [
                'valid' => false,
                'status' => 'STEP_7A_ARTIFACT_INVALID',
                'error' =>
                    'Step 7A resolution artifact does not contain a valid resolution set.',
            ];
        }

        if (
            !array_key_exists(
                $canonicalCode,
                $resolutions
            )
        ) {
            return [
                'valid' => false,
                'status' => 'STEP_7A_RESOLUTION_NOT_FOUND',
                'error' =>
                    'No persisted Step 7A resolution exists for the canonical product code.',
            ];
        }

        $resolution =
            $resolutions[$canonicalCode];

        if (!is_array($resolution)) {
            return [
                'valid' => false,
                'status' => 'STEP_7A_ARTIFACT_INVALID',
                'error' =>
                    'Persisted Step 7A resolution is not a valid resolution record.',
            ];
        }

        $status =
            (string) (
                $resolution['status']
                ?? ''
            );

        if ($status !== 'RESOLVED') {
            if (
                $status === 'AMBIGUOUS_PRIMARY_IMAGE'
            ) {
                return [
                    'valid' => false,
                    'status' => 'CANONICAL_IMAGE_AMBIGUOUS',
                    'error' =>
                        'Step 7A marked this canonical product primary image as ambiguous.',
                ];
            }

            return [
                'valid' => false,
                'status' => 'CANONICAL_IMAGE_UNAVAILABLE',
                'error' =>
                    'Step 7A did not produce a deterministic canonical primary image.',
            ];
        }

        $imageUrl =
            trim(
                (string) (
                    $resolution['url']
                    ?? ''
                )
            );

        if ($imageUrl === '') {
            return [
                'valid' => false,
                'status' => 'CANONICAL_IMAGE_UNAVAILABLE',
                'error' =>
                    'Step 7A marked the image as resolved but stored no usable image URL.',
            ];
        }

        return [
            'valid' => true,
            'status' => 'RESOLVED',
            'error' => '',
            'resolution' => $resolution,
        ];
    }

    /**
     * Validate the candidate contract before touching WooCommerce.
     *
     * @param array<string, mixed> $candidate
     *
     * @return array<string, mixed>
     */
    private function validateCandidate(
        array $candidate
    ): array {
        if (
            empty(
                $candidate['repair_allowed']
            )
        ) {
            return [
                'valid' => false,
                'status' => 'INVALID_REPAIR_CANDIDATE',
                'error' =>
                    'Repair candidate is not explicitly authorized.',
            ];
        }

        if (
            ($candidate['repair_authority'] ?? '')
            !== 'canonical_blackprint_media'
        ) {
            return [
                'valid' => false,
                'status' => 'INVALID_REPAIR_CANDIDATE',
                'error' =>
                    'Repair candidate does not use the canonical BlackPrint media authority.',
            ];
        }

        if (
            empty(
                $candidate['requires_deterministic_canonical_image']
            )
        ) {
            return [
                'valid' => false,
                'status' => 'INVALID_REPAIR_CANDIDATE',
                'error' =>
                    'Repair candidate does not require deterministic canonical image resolution.',
            ];
        }

        if (
            empty(
                $candidate['requires_existing_owned_woocommerce_product']
            )
        ) {
            return [
                'valid' => false,
                'status' => 'INVALID_REPAIR_CANDIDATE',
                'error' =>
                    'Repair candidate does not require an existing owned WooCommerce product.',
            ];
        }

        if (
            empty(
                $candidate['requires_step_7_validation']
            )
        ) {
            return [
                'valid' => false,
                'status' => 'INVALID_REPAIR_CANDIDATE',
                'error' =>
                    'Repair candidate does not require Step 7 validation.',
            ];
        }

        $productId =
            (int) (
                $candidate['woo_product_id']
                ?? 0
            );

        if ($productId <= 0) {
            return [
                'valid' => false,
                'status' => 'PRODUCT_NOT_FOUND',
                'error' =>
                    'Repair candidate does not contain a valid WooCommerce product ID.',
            ];
        }

        $canonicalCode =
            trim(
                (string) (
                    $candidate['canonical_code']
                    ?? ''
                )
            );

        if ($canonicalCode === '') {
            return [
                'valid' => false,
                'status' => 'INVALID_REPAIR_CANDIDATE',
                'error' =>
                    'Repair candidate does not contain a canonical product code.',
            ];
        }

        return [
            'valid' => true,
            'status' => '',
            'error' => '',
        ];
    }

    /**
     * Validate committed BlackPrint ownership immediately before mutation.
     *
     * @return array<string, mixed>
     */
    private function validateOwnership(
        int $productId,
        string $canonicalCode
    ): array {
        $managed =
            get_post_meta(
                $productId,
                self::OWNERSHIP_META,
                true
            );

        if ((string) $managed !== 'yes') {
            return [
                'valid' => false,
                'status' => 'OWNERSHIP_NOT_MANAGED',
                'error' =>
                    'WooCommerce product is not marked as BlackPrint managed.',
            ];
        }

        $supplier =
            get_post_meta(
                $productId,
                self::SUPPLIER_META,
                true
            );

        if (
            strtolower(
                trim((string) $supplier)
            )
            !== self::SUPPLIER
        ) {
            return [
                'valid' => false,
                'status' => 'OWNERSHIP_SUPPLIER_MISMATCH',
                'error' =>
                    'WooCommerce ownership supplier does not match Amrod.',
            ];
        }

        $productCode =
            trim(
                (string) get_post_meta(
                    $productId,
                    self::PRODUCT_CODE_META,
                    true
                )
            );

        $productIdentity =
            trim(
                (string) get_post_meta(
                    $productId,
                    self::PRODUCT_ID_META,
                    true
                )
            );

        if (
            $productCode !== $canonicalCode
            && $productIdentity !== $canonicalCode
        ) {
            return [
                'valid' => false,
                'status' => 'OWNERSHIP_CODE_MISMATCH',
                'error' =>
                    'Committed WooCommerce ownership does not agree with the canonical product code.',
            ];
        }

        return [
            'valid' => true,
            'status' => '',
            'error' => '',
        ];
    }

    /**
     * Confirm that an attachment remains a usable local WordPress image.
     */
    private function attachmentIsUsable(
        int $attachmentId
    ): bool {
        if ($attachmentId <= 0) {
            return false;
        }

        $attachment =
            get_post($attachmentId);

        if (
            ! $attachment
            || $attachment->post_type !== 'attachment'
        ) {
            return false;
        }

        $mime =
            (string) get_post_mime_type(
                $attachmentId
            );

        if (
            $mime === ''
            || strpos($mime, 'image/') !== 0
        ) {
            return false;
        }

        $file =
            get_attached_file(
                $attachmentId
            );

        return (
            is_string($file)
            && $file !== ''
            && is_readable($file)
        );
    }

    /**
     * Build a consistent Step 7B result.
     *
     * @param array<string, mixed> $candidate
     *
     * @return array<string, mixed>
     */
    private function result(
        array $candidate,
        string $status,
        string $error
    ): array {
        return [
            'success' =>
                $status === 'REPAIR_VERIFIED'
                || $status === 'ALREADY_HEALTHY',

            'status' =>
                $status,

            'woo_product_id' =>
                (int) (
                    $candidate['woo_product_id']
                    ?? 0
                ),

            'canonical_code' =>
                (string) (
                    $candidate['canonical_code']
                    ?? ''
                ),

            'attachment_id' =>
                0,

            'error' =>
                $error,
        ];
    }
}