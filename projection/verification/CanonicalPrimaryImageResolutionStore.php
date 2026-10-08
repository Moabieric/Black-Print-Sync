<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Projection\Verification;

defined('ABSPATH') || exit;

/**
 * Stores a complete Step 7A canonical primary-image resolution artifact.
 *
 * Step 7A is the authoritative diagnostic that determines which
 * canonical image is eligible to become the WooCommerce featured image.
 *
 * This store:
 *
 * - does not write to WooCommerce;
 * - does not download images;
 * - does not perform image repair;
 * - preserves the exact locked snapshot UUID;
 * - preserves the complete per-product resolver result;
 * - allows Step 7B to consume the exact Step 7A result rather than
 *   recalculating or reconstructing it.
 */
final class CanonicalPrimaryImageResolutionStore
{
    private const OPTION_PREFIX =
        'blackprint_canonical_primary_image_resolution_';

    private const VERSION = 1;

    /**
     * Step 7A artifacts are short-lived server-side hand-offs.
     *
     * One day matches the existing verified adoption hand-off
     * convention while preventing stale resolution artifacts from
     * remaining valid indefinitely.
     */
    private const TTL_SECONDS = DAY_IN_SECONDS;

    /**
     * Create and persist a complete Step 7A resolution artifact.
     *
     * @param array<string, mixed> $resolutionCounts
     * @param array<string, array<string, mixed>> $resolutions
     *
     * @return array<string, mixed>
     */
    public function create(
        string $snapshotUuid,
        int $normalizedCount,
        int $normalizationErrorCount,
        array $resolutionCounts,
        array $resolutions
    ): array {
        if ($snapshotUuid === '') {
            return [
                'success' => false,
                'message' =>
                    'Cannot create Step 7A resolution artifact: snapshot UUID is empty.',
            ];
        }

        if ($normalizedCount < 0) {
            return [
                'success' => false,
                'message' =>
                    'Cannot create Step 7A resolution artifact: normalized count is invalid.',
            ];
        }

        if ($normalizationErrorCount < 0) {
            return [
                'success' => false,
                'message' =>
                    'Cannot create Step 7A resolution artifact: normalization error count is invalid.',
            ];
        }

        if (
            $normalizationErrorCount > 0
            && $normalizedCount === 0
        ) {
            return [
                'success' => false,
                'message' =>
                    'Cannot create Step 7A resolution artifact: no normalized products are available.',
            ];
        }

        $expectedStatuses = [
            'resolved',
            'no_default_image',
            'ambiguous_primary_image',
            'primary_image_url_unavailable',
        ];

        foreach ($expectedStatuses as $status) {
            if (
                !isset($resolutionCounts[$status])
                || !is_numeric($resolutionCounts[$status])
                || (int) $resolutionCounts[$status] < 0
            ) {
                return [
                    'success' => false,
                    'message' => sprintf(
                        'Cannot create Step 7A resolution artifact: invalid resolver count for "%s".',
                        $status
                    ),
                ];
            }
        }

        if (
            count($resolutions)
            !== $normalizedCount
        ) {
            return [
                'success' => false,
                'message' => sprintf(
                    'Cannot create Step 7A resolution artifact: expected %d resolution records, received %d.',
                    $normalizedCount,
                    count($resolutions)
                ),
            ];
        }

        $calculatedResolutionCount =
            array_sum(
                array_map(
                    static function (mixed $value): int {
                        return max(0, (int) $value);
                    },
                    $resolutionCounts
                )
            );

        if (
            $calculatedResolutionCount
            !== count($resolutions)
        ) {
            return [
                'success' => false,
                'message' => sprintf(
                    'Cannot create Step 7A resolution artifact: resolver counts total %d but %d resolution records were supplied.',
                    $calculatedResolutionCount,
                    count($resolutions)
                ),
            ];
        }

        foreach ($resolutions as $canonicalCode => $resolution) {
            if (
                !is_string($canonicalCode)
                || trim($canonicalCode) === ''
            ) {
                return [
                    'success' => false,
                    'message' =>
                        'Cannot create Step 7A resolution artifact: a canonical product code is empty.',
                ];
            }

            if (!is_array($resolution)) {
                return [
                    'success' => false,
                    'message' => sprintf(
                        'Cannot create Step 7A resolution artifact: resolution for "%s" is invalid.',
                        $canonicalCode
                    ),
                ];
            }

            if (
                !isset($resolution['status'])
                || !is_string($resolution['status'])
                || trim($resolution['status']) === ''
            ) {
                return [
                    'success' => false,
                    'message' => sprintf(
                        'Cannot create Step 7A resolution artifact: resolution status for "%s" is missing.',
                        $canonicalCode
                    ),
                ];
            }
        }

        $artifactId =
            $this->generateArtifactId();

        $createdAt = time();

        $payload = [
            'version' => self::VERSION,

            'artifact_id' => $artifactId,

            'created_at' => $createdAt,

            'expires_at' =>
                $createdAt + self::TTL_SECONDS,

            'created_by' =>
                get_current_user_id(),

            'snapshot_uuid' =>
                $snapshotUuid,

            'normalized_count' =>
                $normalizedCount,

            'normalization_error_count' =>
                $normalizationErrorCount,

            'resolution_count' =>
                count($resolutions),

            'resolver_counts' =>
                [
                    'resolved' =>
                        (int) $resolutionCounts['resolved'],

                    'no_default_image' =>
                        (int) $resolutionCounts['no_default_image'],

                    'ambiguous_primary_image' =>
                        (int) $resolutionCounts[
                            'ambiguous_primary_image'
                        ],

                    'primary_image_url_unavailable' =>
                        (int) $resolutionCounts[
                            'primary_image_url_unavailable'
                        ],
                ],

            'resolutions' =>
                $resolutions,
        ];

        $optionName =
            $this->optionName(
                $artifactId
            );

        $stored =
            add_option(
                $optionName,
                $payload,
                '',
                false
            );

        if (!$stored) {
            return [
                'success' => false,
                'message' =>
                    'Failed to create Step 7A resolution artifact in WordPress options.',
            ];
        }

        return [
            'success' => true,
            'artifact_id' => $artifactId,
            'snapshot_uuid' => $snapshotUuid,
            'normalized_count' => $normalizedCount,
            'normalization_error_count' =>
                $normalizationErrorCount,
            'resolution_count' =>
                count($resolutions),
            'resolver_counts' =>
                $payload['resolver_counts'],
            'expires_at' =>
                $payload['expires_at'],
        ];
    }

    /**
     * Load and validate a Step 7A resolution artifact.
     *
     * @return array<string, mixed>|null
     */
    public function load(
        string $artifactId
    ): ?array {
        if (
            !$this->isValidArtifactId(
                $artifactId
            )
        ) {
            return null;
        }

        $payload =
            get_option(
                $this->optionName(
                    $artifactId
                ),
                null
            );

        if (!is_array($payload)) {
            return null;
        }

        if (
            !$this->validatePayload(
                $payload,
                $artifactId
            )
        ) {
            return null;
        }

        return $payload;
    }

    /**
     * Load the newest valid Step 7A resolution artifact.
     *
     * @return array<string, mixed>|null
     */
    public function loadLatest(): ?array
    {
        global $wpdb;

        $optionNames =
            $wpdb->get_col(
                $wpdb->prepare(
                    "
                    SELECT option_name
                    FROM {$wpdb->options}
                    WHERE option_name LIKE %s
                    ORDER BY option_id DESC
                    LIMIT 20
                    ",
                    $wpdb->esc_like(
                        self::OPTION_PREFIX
                    ) . '%'
                )
            );

        if (
            !is_array($optionNames)
            || $optionNames === []
        ) {
            return null;
        }

        foreach ($optionNames as $optionName) {
            $candidateId =
                substr(
                    (string) $optionName,
                    strlen(
                        self::OPTION_PREFIX
                    )
                );

            if (
                !$this->isValidArtifactId(
                    $candidateId
                )
            ) {
                continue;
            }

            $candidate =
                $this->load(
                    $candidateId
                );

            if (
                !is_array($candidate)
                || empty(
                    $candidate['artifact_id']
                )
            ) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    /**
     * Delete a Step 7A resolution artifact.
     */
    public function delete(
        string $artifactId
    ): bool {
        if (
            !$this->isValidArtifactId(
                $artifactId
            )
        ) {
            return false;
        }

        return delete_option(
            $this->optionName(
                $artifactId
            )
        );
    }

    /**
     * Validate a stored payload.
     *
     * @param array<string, mixed> $payload
     */
    private function validatePayload(
        array $payload,
        string $artifactId
    ): bool {
        if (
            (int) (
                $payload['version']
                ?? 0
            )
            !== self::VERSION
        ) {
            return false;
        }

        if (
            !isset(
                $payload['artifact_id']
            )
            || !is_string(
                $payload['artifact_id']
            )
            || $payload['artifact_id']
            !== $artifactId
        ) {
            return false;
        }

        if (
            !isset(
                $payload['snapshot_uuid']
            )
            || !is_string(
                $payload['snapshot_uuid']
            )
            || trim(
                $payload['snapshot_uuid']
            ) === ''
        ) {
            return false;
        }

        $createdAt =
            (int) (
                $payload['created_at']
                ?? 0
            );

        $expiresAt =
            (int) (
                $payload['expires_at']
                ?? 0
            );

        if (
            $createdAt <= 0
            || $expiresAt <= $createdAt
        ) {
            return false;
        }

        if (
            time() > $expiresAt
        ) {
            return false;
        }

        $normalizedCount =
            (int) (
                $payload['normalized_count']
                ?? -1
            );

        $normalizationErrorCount =
            (int) (
                $payload['normalization_error_count']
                ?? -1
            );

        $resolutionCount =
            (int) (
                $payload['resolution_count']
                ?? -1
            );

        if (
            $normalizedCount < 0
            || $normalizationErrorCount < 0
            || $resolutionCount < 0
        ) {
            return false;
        }

        $resolutions =
            $payload['resolutions']
            ?? null;

        if (!is_array($resolutions)) {
            return false;
        }

        if (
            count($resolutions)
            !== $resolutionCount
        ) {
            return false;
        }

        if (
            $resolutionCount
            !== $normalizedCount
        ) {
            return false;
        }

        $resolverCounts =
            $payload['resolver_counts']
            ?? null;

        if (!is_array($resolverCounts)) {
            return false;
        }

        $expectedStatuses = [
            'resolved',
            'no_default_image',
            'ambiguous_primary_image',
            'primary_image_url_unavailable',
        ];

        $countTotal = 0;

        foreach ($expectedStatuses as $status) {
            if (
                !isset(
                    $resolverCounts[$status]
                )
                || !is_numeric(
                    $resolverCounts[$status]
                )
                || (int) (
                    $resolverCounts[$status]
                ) < 0
            ) {
                return false;
            }

            $countTotal +=
                (int) $resolverCounts[$status];
        }

        if (
            $countTotal
            !== $resolutionCount
        ) {
            return false;
        }

                $actualResolverCounts = [
            'resolved' => 0,
            'no_default_image' => 0,
            'ambiguous_primary_image' => 0,
            'primary_image_url_unavailable' => 0,
        ];

        $allowedStatuses = [
            'RESOLVED',
            'NO_DEFAULT_IMAGE',
            'AMBIGUOUS_PRIMARY_IMAGE',
            'PRIMARY_IMAGE_URL_UNAVAILABLE',
        ];

        foreach (
            $resolutions
            as $canonicalCode => $resolution
        ) {
            if (
                !is_string(
                    $canonicalCode
                )
                || trim(
                    $canonicalCode
                ) === ''
            ) {
                return false;
            }

            if (!is_array($resolution)) {
                return false;
            }

            $status =
                $resolution['status']
                ?? null;

            if (
                !is_string($status)
                || trim($status) === ''
            ) {
                return false;
            }

            if (
                !in_array(
                    $status,
                    $allowedStatuses,
                    true
                )
            ) {
                return false;
            }

            switch ($status) {
                case 'RESOLVED':
                    $actualResolverCounts['resolved']++;
                    break;

                case 'NO_DEFAULT_IMAGE':
                    $actualResolverCounts['no_default_image']++;
                    break;

                case 'AMBIGUOUS_PRIMARY_IMAGE':
                    $actualResolverCounts[
                        'ambiguous_primary_image'
                    ]++;
                    break;

                case 'PRIMARY_IMAGE_URL_UNAVAILABLE':
                    $actualResolverCounts[
                        'primary_image_url_unavailable'
                    ]++;
                    break;
            }
        }

        if (
            $actualResolverCounts
            !== [
                'resolved' =>
                    (int) $resolverCounts['resolved'],
                'no_default_image' =>
                    (int) $resolverCounts['no_default_image'],
                'ambiguous_primary_image' =>
                    (int) $resolverCounts[
                        'ambiguous_primary_image'
                    ],
                'primary_image_url_unavailable' =>
                    (int) $resolverCounts[
                        'primary_image_url_unavailable'
                    ],
            ]
        ) {
            return false;
        }

        return true;
    }

    /**
     * Generate a collision-resistant artifact ID.
     */
    private function generateArtifactId(): string
    {
        return hash(
            'sha256',
            wp_generate_uuid4()
            . '|' .
            microtime(true)
            . '|' .
            wp_rand()
        );
    }

    /**
     * Build the WordPress option name.
     */
    private function optionName(
        string $artifactId
    ): string {
        return self::OPTION_PREFIX
            . $artifactId;
    }

    /**
     * Validate the artifact ID format.
     */
    private function isValidArtifactId(
        string $artifactId
    ): bool {
        return preg_match(
            '/^[a-f0-9]{64}$/',
            $artifactId
        ) === 1;
    }
}