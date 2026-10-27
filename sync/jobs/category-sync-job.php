<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Sync\Jobs;

use BlackPrint\Commerce\Sync\Contracts\SyncJobInterface;
use BlackPrint\Commerce\Sync\Entities\Snapshot;
use BlackPrint\Commerce\Sync\Entities\SnapshotType;
use BlackPrint\Commerce\Sync\Kernel\JobContext;
use BlackPrint\Commerce\Sync\Kernel\SyncResult;
use BlackPrint\Commerce\Sync\Repositories\SnapshotPayloadRepository;
use BlackPrint\Commerce\Sync\Repositories\SnapshotRepository;
use BlackPrint\Commerce\Suppliers\Amrod\Amrod_Api_Client;

defined('ABSPATH') || exit;

/**
 * Capture Amrod's complete category tree as an immutable raw snapshot.
 *
 * This job does not create, update, assign, or delete WooCommerce terms.
 */
final class CategorySyncJob implements SyncJobInterface
{
    private const ENDPOINT = '/api/v1/Categories/';

    public function __construct(
        private readonly Amrod_Api_Client $apiClient,
        private readonly SnapshotRepository $snapshots,
        private readonly SnapshotPayloadRepository $payloads,
        private readonly \wpdb $db
    ) {
    }

    public function supplier(): string
    {
        return 'amrod';
    }

    public function resource(): string
    {
        return 'categories';
    }

    public function execute(JobContext $context): SyncResult
    {
        if ($context->jobType() === 'replay') {
            throw new \RuntimeException(
                'Category replay is not implemented in the supplier ingestion job.'
            );
        }

        $startedAt = microtime(true);
        $requestedAt = gmdate('c');

        /*
         * Fetch before opening the transaction. The existing API client
         * handles authentication, HTTP errors, and JSON decoding.
         */
        $payload = $this->apiClient->get(self::ENDPOINT);

        /*
         * Fail closed on empty or malformed responses. Never save an
         * empty or unexpectedly wrapped response as a valid snapshot.
         */
        $counts = $this->validateAndCountTree($payload);

        /*
         * Use the same JSON encoding for checksum calculation that the
         * payload repository and integrity verifier use.
         */
        $json = wp_json_encode($payload);

        if (! is_string($json) || $json === '') {
            throw new \RuntimeException(
                'Unable to encode the Amrod category response for snapshot metadata.'
            );
        }

        $checksum = hash('sha256', $json);
        $payloadSize = strlen($json);
        $durationMs = (int) round(
            (microtime(true) - $startedAt) * 1000
        );

        /*
         * This endpoint returns a complete category tree, not a delta.
         */
        $snapshotType = match ($context->jobType()) {
            'manual' => SnapshotType::MANUAL,
            'daily', 'scheduled' => SnapshotType::FULL,
            default => throw new \RuntimeException(
                sprintf(
                    'Unsupported category snapshot job type: %s',
                    $context->jobType()
                )
            ),
        };

        $snapshotUuid = wp_generate_uuid4();

        $snapshot = new Snapshot(
            id: $snapshotUuid,
            jobId: $context->jobId(),
            sequenceNumber: 1,
            supplier: 'amrod',
            resource: 'categories',
            type: $snapshotType,
            checksum: $checksum,
            recordCount: $counts['roots'],
            metadata: [
                'duration_ms' => $durationMs,
                'payload_size' => $payloadSize,
                'requested_at' => $requestedAt,
                'etag' => null,
                'cursor' => null,
                'extra' => [
                    'endpoint' => self::ENDPOINT,
                    'payload_mode' => 'full_category_tree',
                    'root_category_count' => $counts['roots'],
                    'total_category_count' => $counts['total'],
                    'maximum_depth' => $counts['maximum_depth'],
                ],
            ]
        );

        /*
         * The snapshot metadata and raw payload must be persisted together.
         * Do not report success if transaction start or commit fails.
         */
        $startResult = $this->db->query('START TRANSACTION');

        if ($startResult === false) {
            throw new \RuntimeException(
                sprintf(
                    'Unable to start the Amrod category snapshot transaction: %s',
                    $this->databaseError()
                )
            );
        }

        try {
            $this->snapshots->create($snapshot);

            $this->payloads->save(
                $snapshotUuid,
                $payload
            );

            $commitResult = $this->db->query('COMMIT');

            if ($commitResult === false) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to commit the Amrod category snapshot transaction: %s',
                        $this->databaseError()
                    )
                );
            }
        } catch (\Throwable $exception) {
            /*
             * Preserve the original failure. If rollback also fails,
             * report both failures so the database state can be checked.
             */
            $rollbackResult = $this->db->query('ROLLBACK');

            if ($rollbackResult === false) {
                throw new \RuntimeException(
                    sprintf(
                        'Category snapshot persistence failed: %s. '
                        . 'Rollback also failed: %s. '
                        . 'Verify database state before retrying.',
                        $exception->getMessage(),
                        $this->databaseError()
                    ),
                    0,
                    $exception
                );
            }

            throw $exception;
        }

        return new SyncResult(
            success: true,
            fetched: $counts['roots'],
            processed: $counts['roots'],
            snapshotId: $snapshotUuid,
            metadata: [
                'job_uuid' => $context->jobId(),
                'snapshot_uuid' => $snapshotUuid,
                'supplier' => 'amrod',
                'resource' => 'categories',
                'snapshot_type' => $snapshotType,
                'endpoint' => self::ENDPOINT,
                'root_category_count' => $counts['roots'],
                'total_category_count' => $counts['total'],
                'maximum_depth' => $counts['maximum_depth'],
                'checksum' => $checksum,
                'payload_size' => $payloadSize,
                'payload_mode' => 'full_category_tree',
                'woocommerce_modified' => false,
            ]
        );
    }

    /**
     * Return the latest database error, with a safe fallback.
     */
    private function databaseError(): string
    {
        return $this->db->last_error !== ''
            ? $this->db->last_error
            : 'Unknown database error.';
    }

    /**
     * Validate the nested category structure and calculate tree statistics.
     *
     * IDs and full category paths are required at every level.
     * Names alone are not treated as unique identifiers.
     *
     * @return array{roots:int,total:int,maximum_depth:int}
     */
    private function validateAndCountTree(array $payload): array
    {
        if ($payload === [] || ! array_is_list($payload)) {
            throw new \RuntimeException(
                'Amrod Categories returned an empty or unexpected top-level structure; no snapshot was saved.'
            );
        }

        $total = 0;
        $maximumDepth = 0;

        foreach ($payload as $category) {
            $this->validateNode(
                $category,
                1,
                $total,
                $maximumDepth
            );
        }

        if ($total === 0) {
            throw new \RuntimeException(
                'Amrod Categories contained no valid category nodes; no snapshot was saved.'
            );
        }

        return [
            'roots' => count($payload),
            'total' => $total,
            'maximum_depth' => $maximumDepth,
        ];
    }

    private function validateNode(
        mixed $category,
        int $depth,
        int &$total,
        int &$maximumDepth
    ): void {
        if (! is_array($category)) {
            throw new \RuntimeException(
                'Amrod Categories contains a non-object category node; no snapshot was saved.'
            );
        }

        if (
            ! array_key_exists('id', $category)
            || ! is_scalar($category['id'])
            || trim((string) $category['id']) === ''
        ) {
            throw new \RuntimeException(
                'An Amrod category is missing a valid id; no snapshot was saved.'
            );
        }

        if (
            ! isset($category['categoryPath'])
            || ! is_string($category['categoryPath'])
            || trim($category['categoryPath']) === ''
        ) {
            throw new \RuntimeException(
                'An Amrod category is missing its categoryPath; no snapshot was saved.'
            );
        }

        $total++;
        $maximumDepth = max($maximumDepth, $depth);

        if (! array_key_exists('children', $category)) {
            return;
        }

        if (! is_array($category['children'])) {
            throw new \RuntimeException(
                sprintf(
                    'The children field for Amrod category ID %s is not an array; no snapshot was saved.',
                    (string) $category['id']
                )
            );
        }

        foreach ($category['children'] as $child) {
            $this->validateNode(
                $child,
                $depth + 1,
                $total,
                $maximumDepth
            );
        }
    }
}
