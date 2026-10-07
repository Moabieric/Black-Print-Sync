<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Projection\Verification;

defined('ABSPATH') || exit;

/**
 * BlackPrint Commerce
 *
 * WooCommerce Image Repair Orchestrator.
 *
 * Step 7B — Read-only repair preflight.
 *
 * HARD SAFETY BOUNDARY
 * --------------------
 *
 * This class performs preflight only.
 *
 * It does not:
 *
 * - modify WooCommerce products;
 * - modify variations;
 * - modify ownership metadata;
 * - modify SKUs;
 * - create attachments;
 * - download images;
 * - assign featured images;
 * - replace images;
 * - delete images;
 * - reconstruct Step 3 mappings;
 * - rerun Step 5B;
 * - call CanonicalPrimaryImageResolver;
 * - use loadLatest() for Step 7A.
 *
 * The explicit Step 7A artifact supplied by the caller is authoritative.
 */
final class WooCommerceImageRepairOrchestrator
{
    private CanonicalPrimaryImageResolutionStore $resolutionStore;

    private WooCommerceImageHealthAuditor $auditor;

    /**
     * Constructor.
     */
    public function __construct(
        ?CanonicalPrimaryImageResolutionStore $resolutionStore = null,
        ?WooCommerceImageHealthAuditor $auditor = null
    ) {
        $this->resolutionStore =
            $resolutionStore
            ?? new CanonicalPrimaryImageResolutionStore();

        $this->auditor =
            $auditor
            ?? new WooCommerceImageHealthAuditor();
    }

    /**
     * Run Step 7B read-only preflight.
     *
     * @param string $snapshotUuid
     * @param string $step7aArtifactId
     *
     * @return array<string,mixed>
     */
    public function preflightCandidates(
        string $snapshotUuid,
        string $step7aArtifactId
    ): array {
        $snapshotUuid =
            trim($snapshotUuid);

        $step7aArtifactId =
            trim($step7aArtifactId);

        $result = [
            'success' => false,

            'read_only' => true,

            'snapshot_uuid' =>
                $snapshotUuid,

            'step_7a_artifact_id' =>
                $step7aArtifactId,

            'step_7a_artifact_loaded' =>
                false,

            'audit_completed' =>
                false,

            'audit_summary' => [],

            'total_candidates' =>
                0,

            'eligible_candidates' =>
                0,

            'rejected_candidates' =>
                0,

            'rejection_counts' => [
                'INVALID_CANDIDATE' => 0,
                'CANONICAL_CODE_MISSING' => 0,
                'STEP_7A_RESOLUTION_MISSING' => 0,
                'STEP_7A_AMBIGUOUS' => 0,
                'STEP_7A_IMAGE_UNAVAILABLE' => 0,
                'STEP_7A_ARTIFACT_INVALID' => 0,
            ],

            'eligible' => [],

            'rejected' => [],

            'error' => '',
        ];

        try {

            /*
             * -------------------------------------------------------------
             * Snapshot identity.
             * -------------------------------------------------------------
             */

            if ($snapshotUuid === '') {
                throw new \RuntimeException(
                    'Step 7B failed: snapshot UUID is required.'
                );
            }

            /*
             * -------------------------------------------------------------
             * Explicit Step 7A artifact identity.
             *
             * Never silently substitute loadLatest().
             * -------------------------------------------------------------
             */

            if ($step7aArtifactId === '') {
                throw new \RuntimeException(
                    'Step 7B failed: an explicit Step 7A artifact ID is required.'
                );
            }

            /*
             * -------------------------------------------------------------
             * Load the exact Step 7A artifact.
             * -------------------------------------------------------------
             */

            $artifact =
                $this->resolutionStore->load(
                    $step7aArtifactId
                );

            if (!is_array($artifact)) {
                throw new \RuntimeException(
                    'Step 7B failed: the supplied Step 7A artifact could not be loaded or has expired.'
                );
            }

            $result['step_7a_artifact_loaded'] =
                true;

            /*
             * -------------------------------------------------------------
             * Confirm artifact snapshot identity.
             * -------------------------------------------------------------
             */

            $artifactSnapshotUuid =
                trim(
                    (string) (
                        $artifact['snapshot_uuid']
                        ?? ''
                    )
                );

            if (
                $artifactSnapshotUuid === ''
                || $artifactSnapshotUuid !== $snapshotUuid
            ) {
                throw new \RuntimeException(
                    sprintf(
                        'Step 7B failed: Step 7A artifact snapshot mismatch. Expected "%s", received "%s".',
                        $snapshotUuid,
                        $artifactSnapshotUuid
                    )
                );
            }

            /*
             * -------------------------------------------------------------
             * Confirm artifact ID identity.
             * -------------------------------------------------------------
             */

            $artifactId =
                trim(
                    (string) (
                        $artifact['artifact_id']
                        ?? ''
                    )
                );

            if (
                $artifactId === ''
                || $artifactId !== $step7aArtifactId
            ) {
                throw new \RuntimeException(
                    'Step 7B failed: Step 7A artifact identity mismatch.'
                );
            }

            /*
             * -------------------------------------------------------------
             * Confirm complete Step 7A resolution coverage.
             * -------------------------------------------------------------
             */

            $resolutions =
                $artifact['resolutions']
                ?? null;

            if (!is_array($resolutions)) {
                throw new \RuntimeException(
                    'Step 7B failed: Step 7A artifact contains no valid resolution set.'
                );
            }

            $normalizedCount =
                (int) (
                    $artifact['normalized_count']
                    ?? 0
                );

            if (
                $normalizedCount <= 0
                || count($resolutions) !== $normalizedCount
            ) {
                throw new \RuntimeException(
                    sprintf(
                        'Step 7B failed: Step 7A resolution coverage is incomplete. Expected %d records, received %d.',
                        $normalizedCount,
                        count($resolutions)
                    )
                );
            }

            /*
             * -------------------------------------------------------------
             * Re-run Step 6 image health audit.
             *
             * This is diagnostic only.
             * -------------------------------------------------------------
             */

            $normalizationResult =
                bp_commerce()
                    ->normalization()
                    ->normalize(
                        $snapshotUuid
                    );

            if (!$normalizationResult->success()) {
                throw new \RuntimeException(
                    'Step 7B failed: normalization failed: '
                    . (
                        $normalizationResult->errors()[0]
                        ?? 'Unknown normalization error.'
                    )
                );
            }

            $auditResult =
                $this->auditor->audit(
                    $snapshotUuid,
                    $normalizationResult
                );

            if (!is_array($auditResult)) {
                throw new \RuntimeException(
                    'Step 7B failed: image health audit returned an invalid result.'
                );
            }

            if (
                empty(
                    $auditResult['read_only']
                )
            ) {
                throw new \RuntimeException(
                    'Step 7B failed: image health audit did not return a read-only result.'
                );
            }

            $result['audit_completed'] =
                true;

            $result['audit_summary'] =
                is_array(
                    $auditResult['summary']
                    ?? null
                )
                    ? $auditResult['summary']
                    : [];

            /*
             * -------------------------------------------------------------
             * Retrieve Step 6 repair candidates.
             * -------------------------------------------------------------
             */

            $candidates =
                $auditResult['repair_candidates']
                ?? null;

            if (!is_array($candidates)) {
                throw new \RuntimeException(
                    'Step 7B failed: image health audit returned an invalid repair candidate set.'
                );
            }

            /*
             * -------------------------------------------------------------
             * Preflight every candidate.
             * -------------------------------------------------------------
             */

            foreach ($candidates as $candidate) {

                $result['total_candidates']++;

                $preflight =
                    $this->preflightCandidate(
                        $candidate,
                        $snapshotUuid,
                        $step7aArtifactId,
                        $resolutions
                    );

                if (
                    !empty(
                        $preflight['eligible']
                    )
                ) {
                    $result['eligible_candidates']++;

                    $result['eligible'][] =
                        $preflight['candidate'];

                    continue;
                }

                $result['rejected_candidates']++;

                $status =
                    (string) (
                        $preflight['status']
                        ?? 'INVALID_CANDIDATE'
                    );

                if (
                    !array_key_exists(
                        $status,
                        $result['rejection_counts']
                    )
                ) {
                    $status =
                        'INVALID_CANDIDATE';
                }

                $result['rejection_counts'][$status]++;

                $result['rejected'][] = [
                    'candidate' =>
                        $preflight['candidate'],

                    'status' =>
                        $status,

                    'error' =>
                        (string) (
                            $preflight['error']
                            ?? ''
                        ),
                ];
            }

            $result['success'] =
                true;

        } catch (\Throwable $exception) {

            $result['error'] =
                $exception->getMessage();
        }

        return $result;
    }

    /**
     * Preflight one Step 6 repair candidate.
     *
     * @param mixed $candidate
     * @param string $snapshotUuid
     * @param string $step7aArtifactId
     * @param array<string,mixed> $resolutions
     *
     * @return array<string,mixed>
     */
    private function preflightCandidate(
        mixed $candidate,
        string $snapshotUuid,
        string $step7aArtifactId,
        array $resolutions
    ): array {
        $candidateForResult =
            is_array($candidate)
                ? $candidate
                : [];

        /*
         * -------------------------------------------------------------
         * Candidate must be an array.
         * -------------------------------------------------------------
         */

        if (!is_array($candidate)) {
            return [
                'eligible' => false,
                'status' => 'INVALID_CANDIDATE',
                'candidate' => $candidateForResult,
                'error' =>
                    'Step 6 repair candidate is not an array.',
            ];
        }

        /*
         * -------------------------------------------------------------
         * Candidate safety contract.
         * -------------------------------------------------------------
         */

        $requiredBooleanFields = [
            'repair_allowed',
            'requires_deterministic_canonical_image',
            'requires_existing_owned_woocommerce_product',
            'requires_step_7_validation',
        ];

        foreach (
            $requiredBooleanFields
            as $field
        ) {

            if (
                empty(
                    $candidate[$field]
                )
            ) {
                return [
                    'eligible' => false,
                    'status' => 'INVALID_CANDIDATE',
                    'candidate' => $candidateForResult,
                    'error' =>
                        sprintf(
                            'Required candidate safety flag "%s" is not enabled.',
                            $field
                        ),
                ];
            }
        }

        if (
            ($candidate['repair_authority'] ?? '')
            !== 'canonical_blackprint_media'
        ) {
            return [
                'eligible' => false,
                'status' => 'INVALID_CANDIDATE',
                'candidate' => $candidateForResult,
                'error' =>
                    'Candidate repair authority is not canonical_blackprint_media.',
            ];
        }

        /*
         * -------------------------------------------------------------
         * WooCommerce product identity.
         * -------------------------------------------------------------
         */

        $wooProductId =
            (int) (
                $candidate['woo_product_id']
                ?? 0
            );

        if ($wooProductId <= 0) {
            return [
                'eligible' => false,
                'status' => 'INVALID_CANDIDATE',
                'candidate' => $candidateForResult,
                'error' =>
                    'Candidate does not contain a valid WooCommerce product ID.',
            ];
        }

        /*
         * -------------------------------------------------------------
         * Canonical identity.
         * -------------------------------------------------------------
         */

        $canonicalCode =
            trim(
                (string) (
                    $candidate['canonical_code']
                    ?? ''
                )
            );

        if ($canonicalCode === '') {
            return [
                'eligible' => false,
                'status' => 'CANONICAL_CODE_MISSING',
                'candidate' => $candidateForResult,
                'error' =>
                    'Candidate does not contain a canonical supplier product code.',
            ];
        }

        /*
         * -------------------------------------------------------------
         * Exact Step 7A resolution lookup.
         * -------------------------------------------------------------
         */

        if (
            !array_key_exists(
                $canonicalCode,
                $resolutions
            )
        ) {
            return [
                'eligible' => false,
                'status' => 'STEP_7A_RESOLUTION_MISSING',
                'candidate' => $candidateForResult,
                'error' =>
                    'Canonical code is not present in the supplied Step 7A artifact.',
            ];
        }

        $resolution =
            $resolutions[$canonicalCode];

        if (!is_array($resolution)) {
            return [
                'eligible' => false,
                'status' => 'STEP_7A_ARTIFACT_INVALID',
                'candidate' => $candidateForResult,
                'error' =>
                    'Step 7A resolution record is invalid.',
            ];
        }

        /*
         * -------------------------------------------------------------
         * Preserve the authoritative Step 7A status.
         * -------------------------------------------------------------
         */

        $resolutionStatus =
            strtoupper(
                trim(
                    (string) (
                        $resolution['status']
                        ?? ''
                    )
                )
            );

        if (
            $resolutionStatus
            === 'AMBIGUOUS_PRIMARY_IMAGE'
        ) {
            return [
                'eligible' => false,
                'status' => 'STEP_7A_AMBIGUOUS',
                'candidate' => $candidateForResult,
                'error' =>
                    'Step 7A marked this canonical product as having an ambiguous primary image.',
            ];
        }

        if (
            $resolutionStatus !== 'RESOLVED'
        ) {
            return [
                'eligible' => false,
                'status' => 'STEP_7A_IMAGE_UNAVAILABLE',
                'candidate' => $candidateForResult,
                'error' =>
                    sprintf(
                        'Step 7A did not resolve a deterministic primary image. Status: %s.',
                        $resolutionStatus !== ''
                            ? $resolutionStatus
                            : 'UNKNOWN'
                    ),
            ];
        }

        /*
         * -------------------------------------------------------------
         * A RESOLVED record must contain a URL.
         * -------------------------------------------------------------
         */

        $resolvedUrl =
            trim(
                (string) (
                    $resolution['url']
                    ?? ''
                )
            );

        if ($resolvedUrl === '') {
            return [
                'eligible' => false,
                'status' => 'STEP_7A_IMAGE_UNAVAILABLE',
                'candidate' => $candidateForResult,
                'error' =>
                    'Step 7A reports RESOLVED but contains no canonical image URL.',
            ];
        }

        /*
         * -------------------------------------------------------------
         * Construct the explicit Step 7B hand-off.
         *
         * These values are intentionally added here rather than
         * reconstructed later by the repairer.
         * -------------------------------------------------------------
         */

        $candidateForResult[
            'snapshot_uuid'
        ] =
            $snapshotUuid;

        $candidateForResult[
            'step_7a_artifact_id'
        ] =
            $step7aArtifactId;

        $candidateForResult[
            'step_7a_resolution_status'
        ] =
            $resolutionStatus;

        $candidateForResult[
            'step_7a_resolution_verified'
        ] =
            true;

        return [
            'eligible' => true,
            'status' => 'ELIGIBLE',
            'candidate' => $candidateForResult,
            'error' => '',
        ];
    }
}