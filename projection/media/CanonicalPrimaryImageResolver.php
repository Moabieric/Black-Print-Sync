<?php

/*
|--------------------------------------------------------------------------
| Canonical Primary Image Resolver
|--------------------------------------------------------------------------
|
| Resolves the single deterministic primary image from canonical media.
|
| Safety boundary:
|
| - Read-only.
| - No WooCommerce access.
| - No WordPress media writes.
| - No HTTP requests.
| - No downloads.
| - No attachment creation.
| - No product mutation.
|
| The resolver consumes canonical media already produced by the
| BlackPrint normalization / Step 6 audit pipeline.
|
| Deterministic rule:
|
|   exactly one image with isDefault = true
|       -> eligible
|
|   zero default images
|       -> NO_DEFAULT_IMAGE
|
|   more than one default image
|       -> AMBIGUOUS_PRIMARY_IMAGE
|
|   exactly one default image but no usable URL
|       -> PRIMARY_IMAGE_URL_UNAVAILABLE
|
*/

namespace BlackPrint\Commerce\Projection\Media;

final class CanonicalPrimaryImageResolver
{
    /**
     * Resolve the deterministic primary image.
     *
     * @param array $canonicalImages Canonical image records from the
     *                               Step 6 repair candidate.
     *
     * @return array{
     *     success: bool,
     *     status: string,
     *     url: string,
     *     image: array|null
     * }
     */
    public function resolve(array $canonicalImages): array
    {
        $defaultImages = [];

        foreach ($canonicalImages as $image) {
            if (!is_array($image)) {
                continue;
            }

            if (!$this->isDefaultImage($image)) {
                continue;
            }

            $defaultImages[] = $image;
        }

        if (count($defaultImages) === 0) {
            return [
                'success' => false,
                'status' => 'NO_DEFAULT_IMAGE',
                'url' => '',
                'image' => null,
            ];
        }

        if (count($defaultImages) > 1) {
            return [
                'success' => false,
                'status' => 'AMBIGUOUS_PRIMARY_IMAGE',
                'url' => '',
                'image' => null,
            ];
        }

        $defaultImage = $defaultImages[0];

        $url = $this->extractImageUrl($defaultImage);

        if ($url === '') {
            return [
                'success' => false,
                'status' => 'PRIMARY_IMAGE_URL_UNAVAILABLE',
                'url' => '',
                'image' => $defaultImage,
            ];
        }

        return [
            'success' => true,
            'status' => 'RESOLVED',
            'url' => $url,
            'image' => $defaultImage,
        ];
    }

    /**
     * Determine whether an image is explicitly marked as the default.
     */
    private function isDefaultImage(array $image): bool
    {
        if (!array_key_exists('isDefault', $image)) {
            return false;
        }

        return $this->isTruthyDefaultValue(
            $image['isDefault']
        );
    }

    /**
     * Resolve the isDefault value without relying on PHP truthiness
     * for arbitrary supplier data.
     */
    private function isTruthyDefaultValue(mixed $value): bool
    {
        if ($value === true) {
            return true;
        }

        if ($value === 1) {
            return true;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            return in_array(
                $normalized,
                [
                    '1',
                    'true',
                    'yes',
                    'y',
                ],
                true
            );
        }

        return false;
    }

    /**
     * Extract the URL from a canonical image record.
     *
     * Actual Amrod normalized structure observed:
     *
     * images[]
     *   - name
     *   - isDefault
     *   - urls[]
     *       - url
     *       - width
     *       - height
     *
     * We also tolerate the URL keys already supported elsewhere in
     * the media pipeline, but only within the selected default image.
     */
    private function extractImageUrl(array $image): string
    {
        $directKeys = [
            'url',
            'image',
            'image_url',
            'imageUrl',
            'src',
            'source',
            'uri',
            'href',
        ];

        foreach ($directKeys as $key) {
            if (!array_key_exists($key, $image)) {
                continue;
            }

            $url = $this->normalizeUrlValue(
                $image[$key]
            );

            if ($url !== '') {
                return $url;
            }
        }

        /*
         * Actual normalized Amrod structure:
         *
         * 'urls' => [
         *     [
         *         'url' => 'https://...',
         *         'width' => 1024,
         *         'height' => 1024,
         *     ],
         * ]
         */
        if (
            isset($image['urls'])
            && is_array($image['urls'])
        ) {
            foreach ($image['urls'] as $urlRecord) {
                if (!is_array($urlRecord)) {
                    continue;
                }

                if (!array_key_exists('url', $urlRecord)) {
                    continue;
                }

                $url = $this->normalizeUrlValue(
                    $urlRecord['url']
                );

                if ($url !== '') {
                    return $url;
                }
            }
        }

        return '';
    }

    /**
     * Normalize a possible URL value.
     */
    private function normalizeUrlValue(mixed $value): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (!$this->looksLikeUrl($value)) {
            return '';
        }

        return $value;
    }

    /**
     * Basic URL validation.
     *
     * The resolver deliberately does not perform a network request.
     */
    private function looksLikeUrl(string $value): bool
    {
        return (bool) filter_var(
            $value,
            FILTER_VALIDATE_URL
        );
    }
}