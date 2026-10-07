<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Projection\Media;

defined('ABSPATH') || exit;

/**
 * Controlled WordPress media importer for BlackPrint image repair.
 *
 * Step 7B.
 *
 * HARD SAFETY BOUNDARY:
 *
 * This class:
 *
 * - Downloads only the explicitly supplied canonical image URL.
 * - Uses native WordPress media APIs.
 * - Creates an attachment only after a successful sideload.
 * - Does not create products.
 * - Does not create variations.
 * - Does not modify ownership metadata.
 * - Does not modify product identity.
 * - Does not modify SKU.
 * - Does not modify price.
 * - Does not modify title.
 * - Does not modify description.
 * - Does not modify categories.
 * - Does not modify attributes.
 * - Does not modify galleries.
 * - Does not delete existing attachments.
 *
 * The caller remains responsible for deciding whether a repair
 * is authorized. This class only performs the narrowly-scoped
 * WordPress media import.
 */
final class WordPressImageImporter
{
    /**
     * Import one remote canonical image into WordPress.
     *
     * The supplied parent product must already exist.
     *
     * @param string $imageUrl
     * @param int    $productId
     *
     * @return array<string, mixed>
     */
    public function import(
        string $imageUrl,
        int $productId
    ): array {
        $imageUrl = trim($imageUrl);

        if ($imageUrl === '') {
            return [
                'success' => false,
                'status' => 'CANONICAL_IMAGE_URL_INVALID',
                'attachment_id' => 0,
                'error' => 'Canonical image URL is empty.',
            ];
        }

        if ($productId <= 0) {
            return [
                'success' => false,
                'status' => 'PRODUCT_NOT_FOUND',
                'attachment_id' => 0,
                'error' => 'A valid existing WooCommerce product ID is required.',
            ];
        }

        if (
            ! filter_var(
                $imageUrl,
                FILTER_VALIDATE_URL
            )
        ) {
            return [
                'success' => false,
                'status' => 'CANONICAL_IMAGE_URL_INVALID',
                'attachment_id' => 0,
                'error' => 'Canonical image URL is not a valid URL.',
            ];
        }

        if (
            ! function_exists('media_sideload_image')
            || ! function_exists('media_handle_sideload')
        ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        if (
            ! function_exists('media_sideload_image')
            || ! function_exists('media_handle_sideload')
        ) {
            return [
                'success' => false,
                'status' => 'WORDPRESS_MEDIA_API_UNAVAILABLE',
                'attachment_id' => 0,
                'error' => 'Required WordPress media APIs are unavailable.',
            ];
        }

        /*
         * Download and create the attachment through WordPress.
         *
         * The attachment is associated with the existing product.
         * No existing attachment is deleted or replaced here.
         */
        $attachmentId = media_sideload_image(
            $imageUrl,
            $productId,
            null,
            'id'
        );

        if (
            is_wp_error($attachmentId)
            || ! is_numeric($attachmentId)
        ) {
            return [
                'success' => false,
                'status' => 'IMPORT_FAILED',
                'attachment_id' => 0,
                'error' =>
                    is_wp_error($attachmentId)
                        ? $attachmentId->get_error_message()
                        : 'WordPress did not return a valid attachment ID.',
            ];
        }

        $attachmentId = (int) $attachmentId;

        if ($attachmentId <= 0) {
            return [
                'success' => false,
                'status' => 'ATTACHMENT_INVALID',
                'attachment_id' => 0,
                'error' => 'WordPress returned an invalid attachment ID.',
            ];
        }

        $attachment = get_post($attachmentId);

        if (
            ! $attachment
            || $attachment->post_type !== 'attachment'
        ) {
            return [
                'success' => false,
                'status' => 'ATTACHMENT_INVALID',
                'attachment_id' => $attachmentId,
                'error' => 'Imported object is not a valid WordPress attachment.',
            ];
        }

        return [
            'success' => true,
            'status' => 'IMPORTED',
            'attachment_id' => $attachmentId,
            'error' => '',
        ];
    }
}