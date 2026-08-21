<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Normalization\Services;

use BlackPrint\Commerce\Normalization\DTO\CanonicalProductCollection;
use BlackPrint\Commerce\Normalization\DTO\NormalizationResult;
use BlackPrint\Commerce\Normalization\Registry\CanonicalNormalizerRegistry;
use BlackPrint\Commerce\Sync\Repositories\SnapshotPayloadRepository;
use BlackPrint\Commerce\Sync\Repositories\SnapshotRepository;

defined('ABSPATH') || exit;

/**
 * Snapshot Normalization Service.
 *
 * Restores raw supplier records from an existing immutable
 * snapshot and transforms them into supplier-agnostic
 * BlackPrint Canonical Products.
 *
 * Flow:
 *
 * Immutable Snapshot UUID
 *      ↓
 * SnapshotRepository
 *      ↓
 * SnapshotPayloadRepository
 *      ↓
 * Raw Supplier Records
 *      ↓
 * CanonicalNormalizerRegistry
 *      ↓
 * Supplier Canonical Normalizer
 *      ↓
 * CanonicalProductCollection
 *      ↓
 * NormalizationResult
 *
 * This service does not:
 *
 * - Call supplier APIs.
 * - Modify snapshots.
 * - Modify snapshot payloads.
 * - Persist canonical products.
 * - Apply BlackPrint business rules.
 * - Write to WooCommerce.
 */
final class SnapshotNormalizationService
{
    public function __construct(
        private readonly SnapshotRepository $snapshots,
        private readonly SnapshotPayloadRepository $payloads,
        private readonly CanonicalNormalizerRegistry $normalizers
    ) {
    }

    /**
     * Normalize an immutable snapshot.
     */
    public function normalize(
        string $snapshotUuid
    ): NormalizationResult {

        /*
        |--------------------------------------------------------------------------
        | Load Immutable Snapshot Metadata
        |--------------------------------------------------------------------------
        */

        $snapshot = $this->snapshots->find(
            $snapshotUuid
        );

        if ($snapshot === null) {

            return new NormalizationResult(

                success: false,

                sourceRecords: 0,

                normalized: 0,

                failed: 1,

                errors: [
                    sprintf(
                        'Snapshot [%s] was not found.',
                        $snapshotUuid
                    )
                ],

                metadata: [
                    'snapshot_uuid' =>
                        $snapshotUuid,
                ]

            );
        }


        /*
        |--------------------------------------------------------------------------
        | Restore Immutable Raw Payload
        |--------------------------------------------------------------------------
        */

        $records = $this->payloads->find(
            $snapshot->id()
        );

        if ($records === null) {

            return new NormalizationResult(

                success: false,

                sourceRecords:
                    $snapshot->recordCount(),

                normalized: 0,

                failed: 1,

                errors: [
                    sprintf(
                        'Payload for snapshot [%s] was not found.',
                        $snapshot->id()
                    )
                ],

                metadata: [
                    'snapshot_uuid' =>
                        $snapshot->id(),

                    'supplier' =>
                        $snapshot->supplier(),

                    'resource' =>
                        $snapshot->resource(),
                ]

            );
        }


        /*
        |--------------------------------------------------------------------------
        | Resolve Supplier Normalizer
        |--------------------------------------------------------------------------
        */

        $normalizer = $this->normalizers->resolve(

            $snapshot->supplier(),

            $snapshot->resource()

        );


        /*
        |--------------------------------------------------------------------------
        | Normalize Records
        |--------------------------------------------------------------------------
        */

        $products = [];

        $errors = [];

        $skipped = 0;

        $failed = 0;


        foreach ($records as $index => $record) {

            /*
            |--------------------------------------------------------------------------
            | Guard Invalid Source Records
            |--------------------------------------------------------------------------
            */

            if (! is_array($record)) {

                $skipped++;

                $errors[] = sprintf(
                    'Record [%d] is not a valid array and was skipped.',
                    $index
                );

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Normalize Individual Record
            |--------------------------------------------------------------------------
            */

            try {

                $products[] = $normalizer->normalize(
                    $record
                );

            } catch (\Throwable $exception) {

                $failed++;

                $errors[] = sprintf(

                    'Record [%d] failed normalization: %s',

                    $index,

                    $exception->getMessage()

                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Build Immutable Collection
        |--------------------------------------------------------------------------
        */

        $collection =
            new CanonicalProductCollection(
                $products
            );


        /*
        |--------------------------------------------------------------------------
        | Determine Result
        |--------------------------------------------------------------------------
        */

        $success = $failed === 0;


        /*
        |--------------------------------------------------------------------------
        | Return Result
        |--------------------------------------------------------------------------
        */

        return new NormalizationResult(

            success: $success,

            sourceRecords: count(
                $records
            ),

            normalized: $collection->count(),

            skipped: $skipped,

            failed: $failed,

            products: $collection,

            errors: $errors,

            metadata: [

                'snapshot_uuid' =>
                    $snapshot->id(),

                'job_uuid' =>
                    $snapshot->jobId(),

                'supplier' =>
                    $snapshot->supplier(),

                'resource' =>
                    $snapshot->resource(),

                'snapshot_type' =>
                    $snapshot->type(),

                'snapshot_checksum' =>
                    $snapshot->checksum(),

                'snapshot_records_count' =>
                    $snapshot->recordCount(),

            ]

        );
    }
}
