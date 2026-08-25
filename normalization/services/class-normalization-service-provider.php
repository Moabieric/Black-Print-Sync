<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Normalization\Services;

use BlackPrint\Commerce\Normalization\Suppliers\Amrod\AmrodProductsNormalizer;
use BlackPrint\Commerce\Normalization\Registry\CanonicalNormalizerRegistry;
use BlackPrint\Commerce\Sync\Repositories\SnapshotPayloadRepository;
use BlackPrint\Commerce\Sync\Repositories\SnapshotRepository;



defined('ABSPATH') || exit;

/**
 * Normalization Service Provider.
 *
 * Builds the runtime required to transform immutable
 * supplier snapshots into supplier-agnostic canonical
 * BlackPrint products.
 *
 * This provider does not:
 *
 * - Call supplier APIs.
 * - Modify snapshots.
 * - Persist canonical products.
 * - Apply business rules.
 * - Write to WooCommerce.
 */
final class NormalizationServiceProvider
{
    /**
     * Register the normalization runtime.
     */
    public function register(): SnapshotNormalizationService
    {
        /*
        |--------------------------------------------------------------------------
        | Persistence
        |--------------------------------------------------------------------------
        |
        | Normalization reads immutable snapshots through the
        | existing snapshot repositories.
        |
        */

        global $wpdb;

        $snapshots = new SnapshotRepository(
            $wpdb
        );

        $payloads = new SnapshotPayloadRepository(
            $wpdb
        );


        /*
        |--------------------------------------------------------------------------
        | Canonical Normalizer Registry
        |--------------------------------------------------------------------------
        |
        | Supplier-specific normalizers will be registered here.
        |
        | At this stage the registry may intentionally be empty
        | until the Amrod Products Normalizer is implemented.
        |
        */
        $normalizers->register(
            new \BlackPrint\Commerce\Normalization\Suppliers\Amrod\AmrodProductsNormalizer()
        );


        /*
        |--------------------------------------------------------------------------
        | Supplier Normalizers
        |--------------------------------------------------------------------------
        */

        $normalizers->register(
            new AmrodProductsNormalizer()
        );

        /*
        |--------------------------------------------------------------------------
        | Snapshot Normalization Service
        |--------------------------------------------------------------------------
        */

        return new SnapshotNormalizationService(

            snapshots: $snapshots,

            payloads: $payloads,

            normalizers: $normalizers

        );
    }
}