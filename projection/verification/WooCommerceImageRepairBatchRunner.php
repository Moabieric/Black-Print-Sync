<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Projection\Verification;

defined('ABSPATH') || exit;

/**
 * Executes explicit, persisted Step 7B image-repair runs.
 *
 * Creating a run is separate from executing a batch.
 * Every execution request processes at most the stored batch size.
 */
final class WooCommerceImageRepairBatchRunner
{
    private WooCommerceImageRepairBatchStore $batchStore;

    private WooCommerceImageRepairOrchestrator $orchestrator;

    private WooCommerceSafeImageRepairer $repairer;

    public function __construct(
        ?WooCommerceImageRepairBatchStore $batchStore = null,
        ?WooCommerceImageRepairOrchestrator $orchestrator = null,
        ?WooCommerceSafeImageRepairer $repairer = null
    ) {
        $this->batchStore =
            $batchStore ?? new WooCommerceImageRepairBatchStore();

        $this->orchestrator =
            $orchestrator ?? new WooCommerceImageRepairOrchestrator();

        $this->repairer =
            $repairer ?? new WooCommerceSafeImageRepairer();
    }

    /**
     * Repeat read-only preflight against the exact artifact, then
     * persist its eligible candidates as a frozen manifest.
     *
     * This method does not import or assign images.
     *
     * @return array<string, mixed>
     */
    public function createRun(
        string $snapshotUuid,
        string $step7aArtifactId,
        int $batchSize = 10
    ): array {
        $snapshotUuid = trim($snapshotUuid);
        $step7aArtifactId = trim($step7aArtifactId);

        if ($snapshotUuid === '' || $step7aArtifactId === '') {
            return [
                'success' => false,
                'status' => 'INVALID_RUN_IDENTITY',
                'message' => 'An explicit snapshot UUID and Step 7A artifact ID are required.',
            ];
        }

        if ($batchSize < 1 || $batchSize > 10) {
            return [
                'success' => false,
                'status' => 'INVALID_BATCH_SIZE',
                'message' => 'Batch size must be between 1 and 10.',
            ];
        }

        try {
            $preflight = $this->orchestrator->preflightCandidates(
                $snapshotUuid,
                $step7aArtifactId
            );
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'status' => 'PREFLIGHT_EXCEPTION',
                'message' => $exception->getMessage(),
            ];
        }

        if (empty($preflight['success'])) {
            return [
                'success' => false,
                'status' => 'PREFLIGHT_FAILED',
                'message' => (string) (
                    $preflight['error']
                    ?? 'Read-only preflight did not complete successfully.'
                ),
            ];
        }

        if (
            ($preflight['snapshot_uuid'] ?? '') !== $snapshotUuid
            || ($preflight['step_7a_artifact_id'] ?? '') !== $step7aArtifactId
            || empty($preflight['step_7a_artifact_loaded'])
            || empty($preflight['audit_completed'])
        ) {
            return [
                'success' => false,
                'status' => 'PREFLIGHT_IDENTITY_MISMATCH',
                'message' => 'Preflight did not verify the requested snapshot, artifact, and audit.',
            ];
        }

        $eligible = $preflight['eligible'] ?? [];

        if (!is_array($eligible) || $eligible === []) {
            return [
                'success' => false,
                'status' => 'EMPTY_MANIFEST',
                'message' => 'Preflight produced no eligible repair candidates.',
            ];
        }

        /*
         * Do not silently omit rejected candidates from a run.
         * A clean preflight is required before freezing the manifest.
         */
        if ((int) ($preflight['rejected_candidates'] ?? -1) !== 0) {
            return [
                'success' => false,
                'status' => 'PREFLIGHT_REJECTIONS_PRESENT',
                'message' => sprintf(
                    'Run creation refused: preflight reported %d rejected candidates.',
                    (int) $preflight['rejected_candidates']
                ),
            ];
        }

        if (count($eligible) !== (int) ($preflight['eligible_candidates'] ?? -1)) {
            return [
                'success' => false,
                'status' => 'PREFLIGHT_COUNT_MISMATCH',
                'message' => 'The eligible candidate list does not match the preflight count.',
            ];
        }

        return $this->batchStore->createRun(
            $snapshotUuid,
            $step7aArtifactId,
            $eligible,
            $batchSize
        );
    }

    /**
     * Execute one explicit batch from a persisted run.
     *
     * The cursor and outcome are saved together after each product.
     * If persistence cannot be verified, execution stops immediately.
     *
     * @return array<string, mixed>
     */
    public function executeNextBatch(string $runId): array
    {
        $runId = strtolower(trim($runId));

        $run = $this->batchStore->loadRun($runId);

        if (!is_array($run)) {
            return [
                'success' => false,
                'status' => 'RUN_NOT_FOUND_OR_INVALID',
                'message' => 'The repair run could not be loaded or its manifest checksum failed.',
            ];
        }

        $lockToken = $this->batchStore->acquireLock($runId);

        if ($lockToken === false) {
            return [
                'success' => false,
                'status' => 'RUN_LOCKED',
                'message' => 'Another request holds this repair run lock. No repairs were started.',
            ];
        }

        try {
            /*
             * Reload only this explicitly requested run after acquiring
             * the lock. Never discover a "latest" run automatically.
             */
            $run = $this->batchStore->loadRun($runId);

            if (!is_array($run)) {
                return [
                    'success' => false,
                    'status' => 'RUN_INVALID_AFTER_LOCK',
                    'message' => 'The run failed validation after the lock was acquired.',
                ];
            }

            if (!in_array($run['status'] ?? '', ['READY', 'RUNNING'], true)) {
                return [
                    'success' => false,
                    'status' => 'RUN_NOT_EXECUTABLE',
                    'message' => 'The run is not in READY or RUNNING status.',
                    'run' => $run,
                ];
            }

            $manifest = $run['manifest'];
            $manifestCount = count($manifest);
            $startIndex = (int) $run['next_index'];
            $batchSize = (int) $run['batch_size'];
            $endIndex = min($startIndex + $batchSize, $manifestCount);
            $processed = 0;

            while ($startIndex < $endIndex) {
                $candidate = $manifest[$startIndex] ?? null;

                if (!is_array($candidate)) {
                    return [
                        'success' => false,
                        'status' => 'MANIFEST_CANDIDATE_INVALID',
                        'message' => sprintf(
                            'Manifest entry %d is invalid. Execution stopped.',
                            $startIndex
                        ),
                    ];
                }

                /*
                 * The stored manifest is authoritative for this run.
                 * Re-check the identity fields before invoking repair().
                 */
                if (
                    ($candidate['snapshot_uuid'] ?? '') !== ($run['snapshot_uuid'] ?? '')
                    || ($candidate['step_7a_artifact_id'] ?? '') !== ($run['step_7a_artifact_id'] ?? '')
                    || empty($candidate['repair_allowed'])
                    || ($candidate['repair_authority'] ?? '') !== 'canonical_blackprint_media'
                ) {
                    return [
                        'success' => false,
                        'status' => 'MANIFEST_SAFETY_VALIDATION_FAILED',
                        'message' => sprintf(
                            'Manifest entry %d failed its safety validation. Execution stopped.',
                            $startIndex
                        ),
                    ];
                }

                $indexKey = (string) $startIndex;
                $existingOutcome = $run['outcomes'][$indexKey] ?? null;

                /*
                 * An earlier request may have caused side effects before
                 * interruption. Never automatically retry that product.
                 */
                if (
                    is_array($existingOutcome)
                    && ($existingOutcome['status'] ?? '') === 'IN_PROGRESS'
                ) {
                    $run['status'] = 'NEEDS_RECONCILIATION';

                    $this->batchStore->saveRun(
                        $runId,
                        $run,
                        $lockToken
                    );

                    $persisted = $this->batchStore->loadRun($runId);

                    $reconciliationVerified = is_array($persisted)
                        && ($persisted['status'] ?? '') === 'NEEDS_RECONCILIATION'
                        && (int) ($persisted['next_index'] ?? -1) === $startIndex
                        && isset($persisted['outcomes'][$indexKey])
                        && ($persisted['outcomes'][$indexKey]['status'] ?? '')
                            === 'IN_PROGRESS';

                    return [
                        'success' => false,
                        'status' => $reconciliationVerified
                            ? 'NEEDS_RECONCILIATION'
                            : 'RECONCILIATION_STATE_UNVERIFIED',
                        'message' => $reconciliationVerified
                            ? sprintf(
                                'Manifest entry %d may have partial side effects. It was not retried; reconcile it before continuing.',
                                $startIndex
                            )
                            : sprintf(
                                'Manifest entry %d may have partial side effects, but the reconciliation state could not be verified. Do not retry or clear the lock automatically.',
                                $startIndex
                            ),
                        'run_state_verified' => $reconciliationVerified,
                        'run_id' => $runId,
                        'manifest_index' => $startIndex,
                    ];
                }

                $inProgress = [
                    'manifest_index' => $startIndex,
                    'woo_product_id' => (int) $candidate['woo_product_id'],
                    'canonical_code' => (string) $candidate['canonical_code'],
                    'success' => false,
                    'status' => 'IN_PROGRESS',
                    'attachment_id' => 0,
                    'error' => '',
                    'started_at' => time(),
                ];

                $run['outcomes'][$indexKey] = $inProgress;
                $run['status'] = 'RUNNING';

                $this->batchStore->saveRun(
                    $runId,
                    $run,
                    $lockToken
                );

                $persistedMarker = $this->batchStore->loadRun($runId);

                $markerVerified = is_array($persistedMarker)
                    && ($persistedMarker['status'] ?? '') === 'RUNNING'
                    && (int) ($persistedMarker['next_index'] ?? -1) === $startIndex
                    && isset($persistedMarker['outcomes'][$indexKey])
                    && $persistedMarker['outcomes'][$indexKey] === $inProgress;

                if (!$markerVerified) {
                    return [
                        'success' => false,
                        'status' => 'IN_PROGRESS_MARKER_PERSISTENCE_FAILED',
                        'message' => sprintf(
                            'Could not verify the write-ahead marker for manifest entry %d. The repairer was not called.',
                            $startIndex
                        ),
                        'run_id' => $runId,
                        'manifest_index' => $startIndex,
                    ];
                }

                try {
                    $outcome = $this->repairer->repair($candidate);
                } catch (\Throwable $exception) {
                    $outcome = [
                        'success' => false,
                        'status' => 'REPAIR_EXCEPTION',
                        'woo_product_id' => (int) $candidate['woo_product_id'],
                        'canonical_code' => (string) $candidate['canonical_code'],
                        'attachment_id' => 0,
                        'error' => $exception->getMessage(),
                    ];
                }

                /*
                 * Persist a bounded, serializable outcome. Do not store
                 * the full candidate or the imported image body.
                 */
                $safeOutcome = [
                    'manifest_index' => $startIndex,
                    'woo_product_id' => (int) ($candidate['woo_product_id'] ?? 0),
                    'canonical_code' => (string) ($candidate['canonical_code'] ?? ''),
                    'success' => !empty($outcome['success']),
                    'status' => sanitize_key((string) ($outcome['status'] ?? 'UNKNOWN')),
                    'attachment_id' => (int) ($outcome['attachment_id'] ?? 0),
                    'error' => sanitize_text_field((string) ($outcome['error'] ?? '')),
                    'completed_at' => time(),
                ];

                $run['outcomes'][(string) $startIndex] = $safeOutcome;
                $run['next_index'] = $startIndex + 1;
                $run['status'] = 'RUNNING';

                if ((int) $run['next_index'] >= $manifestCount) {
                    $hasFailures = false;

                    foreach ($run['outcomes'] as $savedOutcome) {
                        if (
                            is_array($savedOutcome)
                            && empty($savedOutcome['success'])
                        ) {
                            $hasFailures = true;
                            break;
                        }
                    }

                    $run['status'] = $hasFailures
                        ? 'COMPLETED_WITH_ERRORS'
                        : 'COMPLETED';
                }

                /*
                 * update_option() may return false for an unchanged value.
                 * Verify the persisted cursor and exact outcome before
                 * deciding whether saving succeeded.
                 */
                $saved = $this->batchStore->saveRun(
                    $runId,
                    $run,
                    $lockToken
                );

                if (!$saved) {
                    $persisted = $this->batchStore->loadRun($runId);
                    $indexKey = (string) $startIndex;

                    $verified = is_array($persisted)
                        && (int) ($persisted['next_index'] ?? -1) === $startIndex + 1
                        && isset($persisted['outcomes'][$indexKey])
                        && $persisted['outcomes'][$indexKey] === $safeOutcome
                        && ($persisted['status'] ?? '') === ($run['status'] ?? '');

                    if (!$verified) {
                        return [
                            'success' => false,
                            'status' => 'RUN_STATE_PERSISTENCE_FAILED',
                            'message' => sprintf(
                                'Could not verify saved progress for manifest entry %d. Execution stopped.',
                                $startIndex
                            ),
                        ];
                    }

                    $run = $persisted;
                }

                $startIndex++;
                $processed++;
            }

            $finalRun = $this->batchStore->loadRun($runId);

            if (!is_array($finalRun)) {
                return [
                    'success' => false,
                    'status' => 'RUN_STATE_UNREADABLE',
                    'message' => 'Batch work finished, but the persisted run could not be reloaded.',
                ];
            }

            return [
                'success' => true,
                'status' => (string) $finalRun['status'],
                'run_id' => $runId,
                'processed_this_batch' => $processed,
                'next_index' => (int) $finalRun['next_index'],
                'manifest_count' => (int) $finalRun['manifest_count'],
                'batch_size' => (int) $finalRun['batch_size'],
                'run' => $finalRun,
            ];
        } finally {
            $this->batchStore->releaseLock($runId, $lockToken);
        }
    }

    /**
     * Load a run for display without executing it.
     *
     * @return array<string, mixed>|null
     */
    public function getRun(string $runId): ?array
    {
        return $this->batchStore->loadRun($runId);
    }
}
