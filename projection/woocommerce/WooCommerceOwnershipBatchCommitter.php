<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Projection\WooCommerce;

use BlackPrint\Commerce\Projection\Adoption\WooCommerceOwnershipCommitState;

defined('ABSPATH') || exit;

/**
 * Resumable Step 5B WooCommerce ownership batch committer.
 *
 * This class is an orchestration layer only.
 *
 * It does not implement ownership rules itself.
 * All ownership decisions and writes are delegated to
 * WooCommerceOwnershipCommitter.
 *
 * Step 5B is processed in bounded requests to avoid gateway and
 * execution-time limits.
 */
final class WooCommerceOwnershipBatchCommitter
{
    /*
    |--------------------------------------------------------------------------
    | Batch configuration.
    |--------------------------------------------------------------------------
    */

    private const PARENT_BATCH_SIZE = 250;

    private const VARIANT_BATCH_SIZE = 500;

    /*
    |--------------------------------------------------------------------------
    | Locked Step 5B expectations.
    |--------------------------------------------------------------------------
    */

    private const EXPECTED_PARENTS = 3710;

    private const EXPECTED_VARIANTS = 20265;

    private WooCommerceOwnershipCommitState $stateStore;

    private WooCommerceOwnershipCommitter $ownershipCommitter;

    public function __construct()
    {
        $this->stateStore =
            new WooCommerceOwnershipCommitState();

        $this->ownershipCommitter =
            new WooCommerceOwnershipCommitter();
    }

    /*
    |--------------------------------------------------------------------------
    | Start.
    |--------------------------------------------------------------------------
    */

    /**
     * Start a new resumable Step 5B commit.
     *
     * This method performs no WooCommerce writes.
     *
     * @param array<string, mixed> $artifact
     *
     * @return array<string, mixed>
     */
    public function start(
        array $artifact
    ): array {
        $this->validateArtifact(
            $artifact
        );

        $existing =
            $this->stateStore->loadLatest();

        if (
            is_array($existing)
            && $this->stateStore->isResumable(
                $existing
            )
        ) {
            throw new \RuntimeException(
                'A Step 5B ownership commit is already in progress.'
            );
        }

        return $this->stateStore->create(
            (string) $artifact['artifact_id'],
            (string) $artifact['mapping_hash'],
            self::EXPECTED_PARENTS,
            self::EXPECTED_VARIANTS
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Batch processing.
    |--------------------------------------------------------------------------
    */

    /**
     * Process exactly one bounded batch.
     *
     * @param array<string, mixed> $artifact
     * @param array<string, mixed> $state
     *
     * @return array<string, mixed>
     */
    public function processBatch(
        array $artifact,
        array $state
    ): array {
        $this->validateArtifact(
            $artifact
        );

        $this->validateStateAgainstArtifact(
            $state,
            $artifact
        );

        if (
            ($state['status'] ?? '')
            === WooCommerceOwnershipCommitState::STATUS_COMPLETE
        ) {
            return $state;
        }

        /*
         * A failed state is intentionally resumable.
         *
         * The previous request may have written some records before
         * the failure/timeout. The ownership committer is idempotent,
         * so retrying the same batch safely resolves those records as
         * already managed.
         */
        if (
            ($state['status'] ?? '')
            === WooCommerceOwnershipCommitState::STATUS_FAILED
        ) {
            $state['status'] =
                WooCommerceOwnershipCommitState::STATUS_IN_PROGRESS;

            $state =
                $this->stateStore->save(
                    $state
                );
        }

        if (
            ($state['phase'] ?? '')
            === WooCommerceOwnershipCommitState::PHASE_PARENT
        ) {
            return $this->processParentBatch(
                $artifact,
                $state
            );
        }

        if (
            ($state['phase'] ?? '')
            === WooCommerceOwnershipCommitState::PHASE_VARIANT
        ) {
            return $this->processVariantBatch(
                $artifact,
                $state
            );
        }

        if (
            ($state['phase'] ?? '')
            === WooCommerceOwnershipCommitState::PHASE_COMPLETE
        ) {
            return $this->stateStore->complete(
                $state
            );
        }

        throw new \RuntimeException(
            'Invalid Step 5B commit phase.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Parent batch.
    |--------------------------------------------------------------------------
    */

    /**
     * Process one parent batch.
     *
     * @param array<string, mixed> $artifact
     * @param array<string, mixed> $state
     *
     * @return array<string, mixed>
     */
    private function processParentBatch(
        array $artifact,
        array $state
    ): array {
        $offset =
            (int) (
                $state['parent_processed']
                ?? 0
            );

        $mappings =
            $this->getParentBatchMappings(
                $artifact,
                $offset
            );

        if ($mappings === []) {
            if (
                $offset !== self::EXPECTED_PARENTS
            ) {
                throw new \RuntimeException(
                    'Parent batch exhausted before all expected mappings were processed.'
                );
            }

            $state['phase'] =
                WooCommerceOwnershipCommitState::PHASE_VARIANT;

            return $this->stateStore->save(
                $state
            );
        }

        $state =
            $this->stateStore->beginBatch(
                $state
            );

        $written = 0;
        $alreadyManaged = 0;

        try {
            foreach ($mappings as $mapping) {
                $result =
                    $this->ownershipCommitter
                        ->commitParentMapping(
                            $mapping
                        );

                if (
                    $result === 'written'
                ) {
                    $written++;
                } elseif (
                    $result === 'already_managed'
                ) {
                    $alreadyManaged++;
                } else {
                    throw new \RuntimeException(
                        sprintf(
                            'Unexpected parent ownership result: %s.',
                            $result
                        )
                    );
                }
            }
        } catch (\Throwable $exception) {
            $state['parent_errors'] =
                ((int) (
                    $state['parent_errors']
                    ?? 0
                )) + 1;

            $state =
                $this->stateStore->fail(
                    $state,
                    $exception->getMessage()
                );

            throw $exception;
        }

        $processed =
            count($mappings);

        $state['parent_processed'] =
            ((int) $state['parent_processed'])
            + $processed;

        $state['parent_correct'] =
            ((int) $state['parent_correct'])
            + $processed;

        $state['parent_written'] =
            ((int) $state['parent_written'])
            + $written;

        $state['parent_already_managed'] =
            ((int) $state['parent_already_managed'])
            + $alreadyManaged;

        $state =
            $this->stateStore->completeBatch(
                $state
            );

        if (
            (int) $state['parent_processed']
            >= self::EXPECTED_PARENTS
        ) {
            $state['phase'] =
                WooCommerceOwnershipCommitState::PHASE_VARIANT;

            $state =
                $this->stateStore->save(
                    $state
                );
        }

        return $state;
    }

    /*
    |--------------------------------------------------------------------------
    | Variant batch.
    |--------------------------------------------------------------------------
    */

    /**
     * Process one variation batch.
     *
     * @param array<string, mixed> $artifact
     * @param array<string, mixed> $state
     *
     * @return array<string, mixed>
     */
    private function processVariantBatch(
        array $artifact,
        array $state
    ): array {
        $offset =
            (int) (
                $state['variant_processed']
                ?? 0
            );

        $mappings =
            $this->getVariantBatchMappings(
                $artifact,
                $offset
            );

        if ($mappings === []) {
            if (
                $offset !== self::EXPECTED_VARIANTS
            ) {
                throw new \RuntimeException(
                    'Variant batch exhausted before all expected mappings were processed.'
                );
            }

            return $this->stateStore->complete(
                $state
            );
        }

        $state =
            $this->stateStore->beginBatch(
                $state
            );

        $written = 0;
        $alreadyManaged = 0;

        try {
            foreach ($mappings as $mapping) {
                $result =
                    $this->ownershipCommitter
                        ->commitVariantMapping(
                            $mapping
                        );

                if (
                    $result === 'written'
                ) {
                    $written++;
                } elseif (
                    $result === 'already_managed'
                ) {
                    $alreadyManaged++;
                } else {
                    throw new \RuntimeException(
                        sprintf(
                            'Unexpected variation ownership result: %s.',
                            $result
                        )
                    );
                }
            }
        } catch (\Throwable $exception) {
            $state['variant_errors'] =
                ((int) (
                    $state['variant_errors']
                    ?? 0
                )) + 1;

            $state =
                $this->stateStore->fail(
                    $state,
                    $exception->getMessage()
                );

            throw $exception;
        }

        $processed =
            count($mappings);

        $state['variant_processed'] =
            ((int) $state['variant_processed'])
            + $processed;

        $state['variant_correct'] =
            ((int) $state['variant_correct'])
            + $processed;

        $state['variant_written'] =
            ((int) $state['variant_written'])
            + $written;

        $state['variant_already_managed'] =
            ((int) $state['variant_already_managed'])
            + $alreadyManaged;

        $state =
            $this->stateStore->completeBatch(
                $state
            );

        if (
            (int) $state['variant_processed']
            >= self::EXPECTED_VARIANTS
        ) {
            $state =
                $this->stateStore->complete(
                    $state
                );
        }

        return $state;
    }

    /*
    |--------------------------------------------------------------------------
    | Mapping extraction.
    |--------------------------------------------------------------------------
    */

    /**
     * Retrieve one bounded parent batch.
     *
     * @param array<string, mixed> $artifact
     *
     * @return array<int, array<string, mixed>>
     */
    private function getParentBatchMappings(
        array $artifact,
        int $offset
    ): array {
        $mappings =
            $artifact['adoption_mappings']
            ?? null;

        if (
            ! is_array($mappings)
        ) {
            throw new \RuntimeException(
                'Verified artifact does not contain adoption mappings.'
            );
        }

        $parents = [];

        foreach ($mappings as $mapping) {
            if (
                ! is_array($mapping)
            ) {
                continue;
            }

            $productId =
                (int) (
                    $mapping['woocommerce_product_id']
                    ?? 0
                );

            if (
                $productId <= 0
            ) {
                continue;
            }

            $parents[] =
                $mapping;
        }

        return array_slice(
            $parents,
            $offset,
            self::PARENT_BATCH_SIZE
        );
    }

    /**
     * Retrieve one bounded variation batch.
     *
     * @param array<string, mixed> $artifact
     *
     * @return array<int, array<string, mixed>>
     */
    private function getVariantBatchMappings(
        array $artifact,
        int $offset
    ): array {
        $mappings =
            $artifact['adoption_mappings']
            ?? null;

        if (
            ! is_array($mappings)
        ) {
            throw new \RuntimeException(
                'Verified artifact does not contain adoption mappings.'
            );
        }

        $variants = [];

        foreach ($mappings as $mapping) {
            if (
                ! is_array($mapping)
            ) {
                continue;
            }

            $variantMappings =
                $mapping['variants']
                ?? [];

            if (
                ! is_array($variantMappings)
            ) {
                continue;
            }

            foreach (
                $variantMappings as $variantMapping
            ) {
                if (
                    ! is_array($variantMapping)
                ) {
                    continue;
                }

                $variationId =
                    (int) (
                        $variantMapping[
                            'woocommerce_variation_id'
                        ]
                        ?? 0
                    );

                if (
                    $variationId <= 0
                ) {
                    continue;
                }

                $variants[] = [
                    'mapping' => $mapping,
                    'variant' => $variantMapping,
                ];
            }
        }

        return array_slice(
            $variants,
            $offset,
            self::VARIANT_BATCH_SIZE
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Artifact validation.
    |--------------------------------------------------------------------------
    */

    /**
     * Validate the verified artifact.
     *
     * @param array<string, mixed> $artifact
     */
    private function validateArtifact(
        array $artifact
    ): void {
        if (
            empty($artifact['artifact_id'])
            || empty($artifact['mapping_hash'])
        ) {
            throw new \RuntimeException(
                'Invalid verified Step 5B artifact.'
            );
        }

        if (
            ! isset(
                $artifact['verification']['pass'],
                $artifact['ownership_dry_run']['pass']
            )
            || $artifact['verification']['pass'] !== true
            || $artifact['ownership_dry_run']['pass'] !== true
        ) {
            throw new \RuntimeException(
                'Step 5B artifact is not verified for commit.'
            );
        }

        if (
            (int) (
                $artifact['approved_mapping_count']
                ?? 0
            ) !== self::EXPECTED_PARENTS
        ) {
            throw new \RuntimeException(
                'Step 5B artifact does not contain exactly 3,710 approved mappings.'
            );
        }

        if (
            (int) (
                $artifact['explicit_variant_ownership_count']
                ?? 0
            ) !== self::EXPECTED_VARIANTS
        ) {
            throw new \RuntimeException(
                'Step 5B artifact does not contain exactly 20,265 explicit variation mappings.'
            );
        }

        if (
            ! isset(
                $artifact['adoption_mappings']
            )
            || ! is_array(
                $artifact['adoption_mappings']
            )
        ) {
            throw new \RuntimeException(
                'Verified Step 5B artifact does not contain adoption mappings.'
            );
        }

        if (
            count(
                $artifact['adoption_mappings']
            ) !== self::EXPECTED_PARENTS
        ) {
            throw new \RuntimeException(
                'Verified Step 5B artifact contains an unexpected adoption mapping set.'
            );
        }
    }

    /**
     * Ensure the state is bound to the exact artifact.
     *
     * @param array<string, mixed> $state
     * @param array<string, mixed> $artifact
     */
    private function validateStateAgainstArtifact(
        array $state,
        array $artifact
    ): void {
        if (
            empty($state['state_id'])
            || empty($state['artifact_id'])
            || empty($state['mapping_hash'])
        ) {
            throw new \RuntimeException(
                'Invalid Step 5B commit state.'
            );
        }

        if (
            (string) $state['artifact_id']
            !== (string) $artifact['artifact_id']
        ) {
            throw new \RuntimeException(
                'Step 5B commit state is bound to a different artifact.'
            );
        }

        if (
            (string) $state['mapping_hash']
            !== (string) $artifact['mapping_hash']
        ) {
            throw new \RuntimeException(
                'Step 5B mapping hash does not match the commit state.'
            );
        }

        if (
            (int) (
                $state['parent_total']
                ?? 0
            ) !== self::EXPECTED_PARENTS
        ) {
            throw new \RuntimeException(
                'Step 5B commit state has an invalid parent total.'
            );
        }

        if (
            (int) (
                $state['variant_total']
                ?? 0
            ) !== self::EXPECTED_VARIANTS
        ) {
            throw new \RuntimeException(
                'Step 5B commit state has an invalid variation total.'
            );
        }
    }
}