<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Projection\Media;

defined('ABSPATH') || exit;

/**
 * Controlled WordPress media importer for BlackPrint Step 7B.
 *
 * Imports only canonical Amrod default product images.
 * Redirects must remain on the approved HTTPS Amrod image host.
 *
 * This class creates an attachment for an existing product but
 * never assigns a featured image. Assignment remains the repairer's
 * responsibility after its ownership checks.
 */
final class WordPressImageImporter
{
    private const ALLOWED_HOST = 'amrcdn.amrod.co.za';

    private const MAX_DOWNLOAD_BYTES = 15728640; // 15 MiB.

    private const MAX_REDIRECTS = 3;

    /**
     * Import one canonical image for an existing product.
     *
     * @return array<string, mixed>
     */
    public function import(
        string $imageUrl,
        int $productId
    ): array {
        $imageUrl = trim($imageUrl);

        if ($productId <= 0) {
            return $this->failure(
                'PRODUCT_NOT_FOUND',
                'A valid existing WooCommerce product ID is required.'
            );
        }

        if (!$this->isAllowedImageUrl($imageUrl)) {
            return $this->failure(
                'CANONICAL_IMAGE_URL_REJECTED',
                'The image URL does not match the approved Amrod HTTPS image URL policy.'
            );
        }

        $product = get_post($productId);

        if (!$product || $product->post_type !== 'product') {
            return $this->failure(
                'PRODUCT_NOT_FOUND',
                'The target WooCommerce product no longer exists.'
            );
        }

        if (
            !function_exists('media_handle_sideload')
            || !function_exists('wp_tempnam')
        ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        if (
            !function_exists('media_handle_sideload')
            || !function_exists('wp_tempnam')
        ) {
            return $this->failure(
                'WORDPRESS_MEDIA_API_UNAVAILABLE',
                'Required WordPress media APIs are unavailable.'
            );
        }

        $download = $this->downloadImage($imageUrl);

        if (empty($download['success'])) {
            return $this->failure(
                (string) ($download['status'] ?? 'DOWNLOAD_FAILED'),
                (string) ($download['error'] ?? 'Image download failed.')
            );
        }

        $temporaryFile = (string) $download['temporary_file'];

        $file = [
            'name' => 'amrod-' . $productId . '-'
                . wp_generate_password(8, false, false)
                . '.jpg',
            'tmp_name' => $temporaryFile,
        ];

        /*
         * WordPress validates and moves the downloaded file into
         * the media library. No featured image is assigned here.
         */
        $attachmentId = media_handle_sideload(
            $file,
            $productId,
            null
        );

        if (is_file($temporaryFile)) {
            @unlink($temporaryFile);
        }

        if (is_wp_error($attachmentId)) {
            return $this->failure(
                'IMPORT_FAILED',
                $attachmentId->get_error_message()
            );
        }

        $attachmentId = (int) $attachmentId;
        $attachment = get_post($attachmentId);

        if (
            $attachmentId <= 0
            || !$attachment
            || $attachment->post_type !== 'attachment'
            || strpos(
                (string) get_post_mime_type($attachmentId),
                'image/'
            ) !== 0
        ) {
            return [
                'success' => false,
                'status' => 'ATTACHMENT_INVALID',
                'attachment_id' => $attachmentId,
                'error' => 'The imported object is not a valid image attachment.',
            ];
        }

        return [
            'success' => true,
            'status' => 'IMPORTED',
            'attachment_id' => $attachmentId,
            'error' => '',
        ];
    }

    /**
     * Download the image while validating every redirect target.
     *
     * @return array<string, mixed>
     */
    private function downloadImage(string $imageUrl): array
    {
        $url = $imageUrl;

        for ($attempt = 0; $attempt <= self::MAX_REDIRECTS; $attempt++) {
            if (!$this->isAllowedImageUrl($url)) {
                return [
                    'success' => false,
                    'status' => 'IMAGE_REDIRECT_REJECTED',
                    'error' => 'An image redirect left the approved Amrod URL policy.',
                ];
            }

            $response = wp_safe_remote_get(
                $url,
                [
                    'timeout' => 30,
                    'redirection' => 0,
                    'limit_response_size' => self::MAX_DOWNLOAD_BYTES,
                    'headers' => [
                        'Accept' => 'image/jpeg',
                    ],
                ]
            );

            if (is_wp_error($response)) {
                return [
                    'success' => false,
                    'status' => 'DOWNLOAD_FAILED',
                    'error' => $response->get_error_message(),
                ];
            }

            $statusCode = (int) wp_remote_retrieve_response_code($response);

            if (in_array($statusCode, [301, 302, 303, 307, 308], true)) {
                $location = trim(
                    (string) wp_remote_retrieve_header(
                        $response,
                        'location'
                    )
                );

                if (
                    $location === ''
                    || $attempt >= self::MAX_REDIRECTS
                    || !$this->isAllowedImageUrl($location)
                ) {
                    return [
                        'success' => false,
                        'status' => 'IMAGE_REDIRECT_REJECTED',
                        'error' => 'The image redirect target is missing, invalid, or outside the approved host.',
                    ];
                }

                $url = $location;
                continue;
            }

            if ($statusCode !== 200) {
                return [
                    'success' => false,
                    'status' => 'DOWNLOAD_FAILED',
                    'error' => sprintf(
                        'The image server returned HTTP status %d.',
                        $statusCode
                    ),
                ];
            }

            $contentType = strtolower(
                trim(
                    explode(
                        ';',
                        (string) wp_remote_retrieve_header(
                            $response,
                            'content-type'
                        )
                    )[0]
                )
            );

            if ($contentType !== 'image/jpeg') {
                return [
                    'success' => false,
                    'status' => 'IMAGE_CONTENT_TYPE_REJECTED',
                    'error' => 'The canonical image response was not JPEG content.',
                ];
            }

            $body = wp_remote_retrieve_body($response);

            if (
                !is_string($body)
                || $body === ''
                || strlen($body) > self::MAX_DOWNLOAD_BYTES
            ) {
                return [
                    'success' => false,
                    'status' => 'IMAGE_SIZE_REJECTED',
                    'error' => 'The image is empty or exceeds the 15 MiB download limit.',
                ];
            }

            $temporaryFile = wp_tempnam('blackprint-amrod-image');

            if (!$temporaryFile) {
                return [
                    'success' => false,
                    'status' => 'TEMP_FILE_FAILED',
                    'error' => 'WordPress could not create a temporary image file.',
                ];
            }

            $written = file_put_contents($temporaryFile, $body);

            if ($written === false || $written !== strlen($body)) {
                if (is_file($temporaryFile)) {
                    @unlink($temporaryFile);
                }

                return [
                    'success' => false,
                    'status' => 'TEMP_FILE_FAILED',
                    'error' => 'The downloaded image could not be written completely.',
                ];
            }

            /*
             * Validate the downloaded file itself. The HTTP Content-Type
             * header is not sufficient proof that the body is a JPEG.
             */
            $imageInfo = @getimagesize($temporaryFile);

            if (
                !is_array($imageInfo)
                || ($imageInfo[2] ?? null) !== IMAGETYPE_JPEG
                || (int) ($imageInfo[0] ?? 0) < 1
                || (int) ($imageInfo[1] ?? 0) < 1
            ) {
                if (is_file($temporaryFile)) {
                    @unlink($temporaryFile);
                }

                return [
                    'success' => false,
                    'status' => 'IMAGE_CONTENT_VALIDATION_FAILED',
                    'error' => 'The downloaded file is not a valid JPEG image.',
                ];
            }

            return [
                'success' => true,
                'temporary_file' => $temporaryFile,
            ];
        }

        return [
            'success' => false,
            'status' => 'IMAGE_REDIRECT_REJECTED',
            'error' => 'The maximum number of image redirects was exceeded.',
        ];
    }

    /**
     * Accept only HTTPS URLs for Amrod default product JPEG images.
     */
    private function isAllowedImageUrl(string $url): bool
    {
        if (
            $url === ''
            || !filter_var($url, FILTER_VALIDATE_URL)
        ) {
            return false;
        }

        $parts = wp_parse_url($url);

        if (!is_array($parts)) {
            return false;
        }

        if (
            strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || strtolower((string) ($parts['host'] ?? '')) !== self::ALLOWED_HOST
            || isset($parts['user'])
            || isset($parts['pass'])
            || (isset($parts['port']) && (int) $parts['port'] !== 443)
            || isset($parts['fragment'])
        ) {
            return false;
        }

        $path = (string) ($parts['path'] ?? '');

        return (
            strpos($path, '/ProductImages/') !== false
            && basename($path) === 'DEFAULT_1024X1024.jpg'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function failure(
        string $status,
        string $error
    ): array {
        return [
            'success' => false,
            'status' => $status,
            'attachment_id' => 0,
            'error' => $error,
        ];
    }
}
