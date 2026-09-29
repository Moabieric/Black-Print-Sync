<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Projection\Adoption;

defined('ABSPATH') || exit;

/**
 * Persistent state for the resumable Step 5B WooCommerce ownership commit.
 *
 * This class stores ONLY commit progress.
 *
 * It does not perform WooCommerce writes.
 *
 * The state is permanently bound to:
 *
 * - one verified adoption artifact
 * - one mapping hash
 *
 * This prevents a commit from accidentally resuming against a different
 * adoption mapping.
 */
final class WooCommerceOwnershipCommitState
{
    private const OPTION_PREFIX = 'blackprint_woocommerce_ownership_commit_';

    private const VERSION = 1;

    public const PHASE_PARENT = 'parent';

    public const PHASE_VARIANT = 'variant';

    public const PHASE_COMPLETE = 'complete';

    public const STATUS_READY = 'ready';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_FAILED = 'failed';

    public const STATUS_COMPLETE = 'complete';

    /**
     * Create a new commit state.
     *
     * @param string $artifactId
     * @param string $mappingHash
     * @param int    $parentTotal
     * @param int    $variantTotal
     *
     * @return array<string, mixed>
     */
    public function create(
        string $artifactId,
        string $mappingHash,
        int $parentTotal,
        int $variantTotal
    ): array {
        if (
            ! preg_match(
                '/^[a-f0-9]{64}$/',
                $artifactId
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid Step 5B artifact ID.'
            );
        }

        if (
            ! preg_match(
                '/^[a-f0-9]{64}$/',
                $mappingHash
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid Step 5B mapping hash.'
            );
        }

        if ($parentTotal < 0) {
            throw new \InvalidArgumentException(
                'Parent total cannot be negative.'
            );
        }

        if ($variantTotal < 0) {
            throw new \InvalidArgumentException(
                'Variant total cannot be negative.'
            );
        }

        $stateId = wp_generate_uuid4();

        $now = time();

        $state = [
            'version' => self::VERSION,

            'state_id' => $stateId,

            'artifact_id' => $artifactId,

            'mapping_hash' => $mappingHash,

            'created_at' => $now,

            'updated_at' => $now,

            'created_by' => get_current_user_id(),

            'status' => self::STATUS_READY,

            'phase' => self::PHASE_PARENT,

            'parent_total' => $parentTotal,

            'parent_processed' => 0,

            'parent_correct' => 0,

            'parent_written' => 0,

            'parent_already_managed' => 0,

            'parent_errors' => 0,

            'variant_total' => $variantTotal,

            'variant_processed' => 0,

            'variant_correct' => 0,

            'variant_written' => 0,

            'variant_already_managed' => 0,

            'variant_errors' => 0,

            'last_batch_number' => 0,

            'last_batch_started_at' => null,

            'last_batch_completed_at' => null,

            'last_error' => null,

            'completed_at' => null,
        ];

        $optionName =
            $this->optionName($stateId);

        $saved = add_option(
            $optionName,
            $state,
            '',
            false
        );

        if (! $saved) {
            throw new \RuntimeException(
                'Unable to create Step 5B commit state.'
            );
        }

        return $state;
    }

    /**
     * Load a commit state by ID.
     *
     * @return array<string, mixed>|null
     */
    public function load(
        string $stateId
    ): ?array {
        if (
            ! $this->isValidStateId($stateId)
        ) {
            return null;
        }

        $state = get_option(
            $this->optionName($stateId),
            null
        );

        if (! is_array($state)) {
            return null;
        }

        if (
            ! $this->validateState($state)
        ) {
            return null;
        }

        return $state;
    }

    /**
     * Load the newest valid state.
     *
     * @return array<string, mixed>|null
     */
    public function loadLatest(): ?array
    {
        global $wpdb;

        $optionNames = $wpdb->get_col(
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
            ! is_array($optionNames)
            || $optionNames === []
        ) {
            return null;
        }

        foreach ($optionNames as $optionName) {
            $stateId = substr(
                (string) $optionName,
                strlen(self::OPTION_PREFIX)
            );

            if (
                ! $this->isValidStateId($stateId)
            ) {
                continue;
            }

            $state = $this->load($stateId);

            if (
                ! is_array($state)
            ) {
                continue;
            }

            return $state;
        }

        return null;
    }

    /**
     * Persist an updated state.
     *
     * @param array<string, mixed> $state
     *
     * @return array<string, mixed>
     */
    public function save(
        array $state
    ): array {
        if (
            ! $this->validateState($state)
        ) {
            throw new \InvalidArgumentException(
                'Invalid Step 5B commit state.'
            );
        }

        $state['updated_at'] = time();

        update_option(
            $this->optionName(
                (string) $state['state_id']
            ),
            $state,
            false
        );

        return $state;
    }

    /**
     * Mark a batch as started.
     *
     * @param array<string, mixed> $state
     *
     * @return array<string, mixed>
     */
    public function beginBatch(
        array $state
    ): array {
        $state['status'] =
            self::STATUS_IN_PROGRESS;

        $state['last_batch_number'] =
            ((int) $state['last_batch_number']) + 1;

        $state['last_batch_started_at'] =
            time();

        $state['last_batch_completed_at'] =
            null;

        $state['last_error'] =
            null;

        return $this->save($state);
    }

    /**
     * Mark a batch as completed.
     *
     * @param array<string, mixed> $state
     *
     * @return array<string, mixed>
     */
    public function completeBatch(
        array $state
    ): array {
        $state['last_batch_completed_at'] =
            time();

        return $this->save($state);
    }

    /**
     * Mark the entire commit as failed.
     *
     * @param array<string, mixed> $state
     * @param string               $message
     *
     * @return array<string, mixed>
     */
    public function fail(
        array $state,
        string $message
    ): array {
        $state['status'] =
            self::STATUS_FAILED;

        $state['last_error'] =
            $message;

        return $this->save($state);
    }

    /**
     * Mark the entire commit as complete.
     *
     * @param array<string, mixed> $state
     *
     * @return array<string, mixed>
     */
    public function complete(
        array $state
    ): array {
        $state['status'] =
            self::STATUS_COMPLETE;

        $state['phase'] =
            self::PHASE_COMPLETE;

        $state['completed_at'] =
            time();

        $state['last_error'] =
            null;

        return $this->save($state);
    }

    /**
     * Delete a state.
     */
    public function delete(
        string $stateId
    ): bool {
        if (
            ! $this->isValidStateId($stateId)
        ) {
            return false;
        }

        return delete_option(
            $this->optionName($stateId)
        );
    }

    /**
     * Determine whether a state is resumable.
     */
    public function isResumable(
        array $state
    ): bool {
        return
            isset($state['status'])
            && in_array(
                $state['status'],
                [
                    self::STATUS_READY,
                    self::STATUS_IN_PROGRESS,
                    self::STATUS_FAILED,
                ],
                true
            )
            && (
                (int) $state['parent_processed']
                < (int) $state['parent_total']
                ||
                (int) $state['variant_processed']
                < (int) $state['variant_total']
            );
    }

    /**
     * Return the WordPress option name.
     */
    private function optionName(
        string $stateId
    ): string {
        return
            self::OPTION_PREFIX
            . $stateId;
    }

    /**
     * Validate the state structure.
     *
     * @param array<string, mixed> $state
     */
    private function validateState(
        array $state
    ): bool {
        if (
            (int) ($state['version'] ?? 0)
            !== self::VERSION
        ) {
            return false;
        }

        if (
            ! isset(
                $state['state_id'],
                $state['artifact_id'],
                $state['mapping_hash'],
                $state['phase'],
                $state['status']
            )
        ) {
            return false;
        }

        if (
            ! $this->isValidStateId(
                (string) $state['state_id']
            )
        ) {
            return false;
        }

        if (
            ! preg_match(
                '/^[a-f0-9]{64}$/',
                (string) $state['artifact_id']
            )
        ) {
            return false;
        }

        if (
            ! preg_match(
                '/^[a-f0-9]{64}$/',
                (string) $state['mapping_hash']
            )
        ) {
            return false;
        }

        if (
            ! in_array(
                $state['phase'],
                [
                    self::PHASE_PARENT,
                    self::PHASE_VARIANT,
                    self::PHASE_COMPLETE,
                ],
                true
            )
        ) {
            return false;
        }

        if (
            ! in_array(
                $state['status'],
                [
                    self::STATUS_READY,
                    self::STATUS_IN_PROGRESS,
                    self::STATUS_FAILED,
                    self::STATUS_COMPLETE,
                ],
                true
            )
        ) {
            return false;
        }

        return true;
    }

    /**
     * Validate the state identifier.
     */
    private function isValidStateId(
        string $stateId
    ): bool {
        return
            preg_match(
                '/^[a-f0-9-]{36}$/',
                $stateId
            ) === 1;
    }
}