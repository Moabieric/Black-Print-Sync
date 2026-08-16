<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Sync\Replay;

use BlackPrint\Commerce\Sync\Repositories\SnapshotPayloadRepository;
use BlackPrint\Commerce\Sync\Repositories\SnapshotRepository;

defined('ABSPATH') || exit;

/**
 * Snapshot Integrity Verifier.
 *
 * Verifies that an immutable snapshot payload can be
 * successfully recovered and that its recovered contents
 * match the immutable snapshot metadata.
 *
 * Verification includes:
 *
 * - Snapshot exists
 * - Payload exists
 * - Payload can be restored
 * - Record count matches
 * - SHA-256 checksum matches
 *
 * This verifier is read-only.
 *
 * It never modifies snapshots, payloads, supplier data,
 * or WooCommerce.
 */
final class SnapshotIntegrityVerifier
{
    public function __construct(
        private readonly SnapshotRepository $snapshots,
        private readonly SnapshotPayloadRepository $payloads
    ) {
    }

    /**
     * Verify a snapshot and its immutable payload.
     *
     * @return array{
     *     success: bool,
     *     snapshot_uuid: string,
     *     snapshot_found: bool,
     *     payload_found: bool,
     *     records_expected: int|null,
     *     records_actual: int|null,
     *     records_valid: bool,
     *     checksum_expected: string|null,
     *     checksum_actual: string|null,
     *     checksum_valid: bool,
     *     errors: array<int, string>
     * }
     */
    public function verify(
        string $snapshotUuid
    ): array {

        $errors = [];

        /*
        |--------------------------------------------------------------------------
        | Default Result
        |--------------------------------------------------------------------------
        */

        $result = [

            'success' => false,

            'snapshot_uuid' => $snapshotUuid,

            'snapshot_found' => false,

            'payload_found' => false,

            'records_expected' => null,

            'records_actual' => null,

            'records_valid' => false,

            'checksum_expected' => null,

            'checksum_actual' => null,

            'checksum_valid' => false,

            'errors' => [],

        ];


        /*
        |--------------------------------------------------------------------------
        | Retrieve Snapshot Metadata
        |--------------------------------------------------------------------------
        */

        $snapshot = $this->snapshots->find(
            $snapshotUuid
        );

        if ($snapshot === null) {

            $errors[] = sprintf(
                'Snapshot [%s] was not found.',
                $snapshotUuid
            );

            $result['errors'] = $errors;

            return $result;
        }

        $result['snapshot_found'] = true;

        $result['records_expected'] =
            $snapshot->recordCount();

        $result['checksum_expected'] =
            $snapshot->checksum();


        /*
        |--------------------------------------------------------------------------
        | Retrieve and Restore Immutable Payload
        |--------------------------------------------------------------------------
        */

        try {

            $payload = $this->payloads->find(
                $snapshotUuid
            );

        } catch (\Throwable $e) {

            $errors[] = sprintf(
                'Failed to restore snapshot payload: %s',
                $e->getMessage()
            );

            $result['errors'] = $errors;

            return $result;
        }

        if ($payload === null) {

            $errors[] = sprintf(
                'Snapshot payload [%s] was not found.',
                $snapshotUuid
            );

            $result['errors'] = $errors;

            return $result;
        }

        $result['payload_found'] = true;


        /*
        |--------------------------------------------------------------------------
        | Verify Record Count
        |--------------------------------------------------------------------------
        */

        $actualRecordCount = count(
            $payload
        );

        $result['records_actual'] =
            $actualRecordCount;

        $result['records_valid'] =
            $actualRecordCount ===
            $snapshot->recordCount();

        if (! $result['records_valid']) {

            $errors[] = sprintf(
                'Record count mismatch. Expected [%d], received [%d].',
                $snapshot->recordCount(),
                $actualRecordCount
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Recreate Canonical Payload JSON
        |--------------------------------------------------------------------------
        |
        | This MUST use wp_json_encode() because the original
        | AmrodConnector checksum was generated from the same
        | function and representation.
        |
        */

        $json = wp_json_encode(
            $payload
        );

        if ($json === false) {

            $errors[] =
                'Failed to encode restored payload for checksum verification.';

            $result['errors'] = $errors;

            return $result;
        }


        /*
        |--------------------------------------------------------------------------
        | Verify SHA-256 Checksum
        |--------------------------------------------------------------------------
        */

        $actualChecksum = hash(
            'sha256',
            $json
        );

        $result['checksum_actual'] =
            $actualChecksum;

        $result['checksum_valid'] =
            hash_equals(
                $snapshot->checksum(),
                $actualChecksum
            );

        if (! $result['checksum_valid']) {

            $errors[] = sprintf(
                'Checksum mismatch. Expected [%s], received [%s].',
                $snapshot->checksum(),
                $actualChecksum
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Final Integrity Result
        |--------------------------------------------------------------------------
        */

        $result['errors'] = $errors;

        $result['success'] =
            $result['snapshot_found']
            && $result['payload_found']
            && $result['records_valid']
            && $result['checksum_valid'];

        return $result;
    }
}
