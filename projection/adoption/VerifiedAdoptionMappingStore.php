<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Projection\Adoption;

defined('ABSPATH') || exit;

/**
 * Stores a verified Step 3/4/5A adoption mapping hand-off.
 *
 * This class deliberately does not write to WooCommerce.
 *
 * The store creates a server-side hand-off containing the exact
 * adoption mappings that passed the verification gates. Step 5B
 * consumes this hand-off rather than accepting mappings supplied
 * directly by a browser request.
 *
 * WordPress options are used instead of a public uploads file so
 * that the mapping cannot be exposed through a predictable URL.
 */
final class VerifiedAdoptionMappingStore
{
    private const OPTION_PREFIX = 'blackprint_verified_adoption_';

    private const VERSION = 1;

    private const TTL_SECONDS = DAY_IN_SECONDS;

    private const EXPECTED_APPROVED_MAPPINGS = 3710;

    private const EXPECTED_VARIANT_OWNERSHIP = 20265;

    /**
     * Create a verified adoption hand-off.
     *
     * @param array<int|string, array<string, mixed>> $adoptionMappings
     * @param array<string, mixed> $verification
     * @param array<string, mixed> $ownershipDryRun
     *
     * @return array<string, mixed>
     */
    public function create(
        array $adoptionMappings,
        string $snapshotUuid,
        array $verification,
        array $ownershipDryRun
    ): array {
        if ($snapshotUuid === '') {
            return [
                'success' => false,
                'message' => 'Cannot create adoption hand-off: snapshot UUID is empty.',
            ];
        }

        if (count($adoptionMappings) !== self::EXPECTED_APPROVED_MAPPINGS) {
            return [
                'success' => false,
                'message' => sprintf(
                    'Cannot create adoption hand-off: expected %d mappings, received %d.',
                    self::EXPECTED_APPROVED_MAPPINGS,
                    count($adoptionMappings)
                ),
            ];
        }

        $variantOwnershipCount = $this->countExplicitVariantOwnership(
            $adoptionMappings
        );

        if ($variantOwnershipCount !== self::EXPECTED_VARIANT_OWNERSHIP) {
            return [
                'success' => false,
                'message' => sprintf(
                    'Cannot create adoption hand-off: expected %d explicit variant ownership records, received %d.',
                    self::EXPECTED_VARIANT_OWNERSHIP,
                    $variantOwnershipCount
                ),
            ];
        }

        $verificationPass = (bool) (
            $verification['pass']
            ?? $verification['status']
            ?? false
        );

        if (!$verificationPass) {
            return [
                'success' => false,
                'message' => 'Cannot create adoption hand-off: Step 4 verification is not PASS.',
            ];
        }

        $ownershipDryRunPass = (bool) (
            $ownershipDryRun['pass']
            ?? $ownershipDryRun['status']
            ?? false
        );

        if (!$ownershipDryRunPass) {
            return [
                'success' => false,
                'message' => 'Cannot create adoption hand-off: Step 5A ownership dry-run is not PASS.',
            ];
        }

        $artifactId = $this->generateArtifactId();

        $createdAt = time();

        $mappingHash = $this->hashMappings(
            $adoptionMappings
        );

        $payload = [
            'version' => self::VERSION,

            'artifact_id' => $artifactId,

            'created_at' => $createdAt,

            'expires_at' => $createdAt + self::TTL_SECONDS,

            'created_by' => get_current_user_id(),

            'snapshot_uuid' => $snapshotUuid,

            'mapping_hash' => $mappingHash,

            'approved_mapping_count' => count($adoptionMappings),

            'explicit_variant_ownership_count' => $variantOwnershipCount,

            'verification' => $verification,

            'ownership_dry_run' => $ownershipDryRun,

            'adoption_mappings' => $adoptionMappings,
        ];

        $optionName = $this->optionName(
            $artifactId
        );

        $stored = add_option(
            $optionName,
            $payload,
            '',
            false
        );

        if (!$stored) {
            return [
                'success' => false,
                'message' => 'Failed to create verified adoption hand-off in WordPress options.',
            ];
        }

        return [
            'success' => true,
            'artifact_id' => $artifactId,
            'snapshot_uuid' => $snapshotUuid,
            'mapping_hash' => $mappingHash,
            'approved_mapping_count' => count($adoptionMappings),
            'explicit_variant_ownership_count' => $variantOwnershipCount,
            'expires_at' => $createdAt + self::TTL_SECONDS,
        ];
    }

    /**
     * Load and validate a verified adoption hand-off.
     *
     * @return array<string, mixed>|null
     */
    public function load(
        string $artifactId
    ): ?array {
        if (!$this->isValidArtifactId($artifactId)) {
            return null;
        }

        $payload = get_option(
            $this->optionName($artifactId),
            null
        );

        if (!is_array($payload)) {
            return null;
        }

        if (!$this->validatePayload(
            $payload,
            $artifactId
        )) {
            return null;
        }

        return $payload;
    }

/**
 * Load the newest valid verified adoption hand-off.
 *
 * Only artifacts that pass all Step 5B hand-off gates are returned.
 *
 * @return array<string, mixed>|null
 */
public function loadLatestVerified(): ?array
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

        $candidateId = substr(
            (string) $optionName,
            strlen(self::OPTION_PREFIX)
        );


        if (
            ! $this->isValidArtifactId(
                $candidateId
            )
        ) {
            continue;
        }


        $candidate = $this->load(
            $candidateId
        );


        if (
            ! is_array($candidate)
            || empty($candidate['artifact_id'])
        ) {
            continue;
        }


        if (
            ! isset($candidate['verification']['pass'])
            || $candidate['verification']['pass'] !== true
        ) {
            continue;
        }


        if (
            ! isset($candidate['ownership_dry_run']['pass'])
            || $candidate['ownership_dry_run']['pass'] !== true
        ) {
            continue;
        }


        if (
            (int) (
                $candidate['approved_mapping_count']
                ?? 0
            ) !== self::EXPECTED_APPROVED_MAPPINGS
        ) {
            continue;
        }


        if (
            (int) (
                $candidate['explicit_variant_ownership_count']
                ?? 0
            ) !== self::EXPECTED_VARIANT_OWNERSHIP
        ) {
            continue;
        }


        return $candidate;
    }


    return null;
}

    /**
     * Delete a verified adoption hand-off.
     */
    public function delete(
        string $artifactId
    ): bool {
        if (!$this->isValidArtifactId($artifactId)) {
            return false;
        }

        return delete_option(
            $this->optionName($artifactId)
        );
    }

    /**
     * Return the number of explicit WooCommerce variation ownership
     * records represented by the mappings.
     *
     * Simple-product variant mappings intentionally do not count here
     * because they have no WooCommerce variation ID.
     *
     * @param array<int|string, array<string, mixed>> $adoptionMappings
     */
    public function countExplicitVariantOwnership(
        array $adoptionMappings
    ): int {
        $count = 0;

        foreach ($adoptionMappings as $mapping) {
            if (!is_array($mapping)) {
                continue;
            }

            $variants = $mapping['variants'] ?? [];

            if (!is_array($variants)) {
                continue;
            }

            foreach ($variants as $variant) {
                if (!is_array($variant)) {
                    continue;
                }

                $variationId = (int) (
                    $variant['woocommerce_variation_id']
                    ?? 0
                );

                if ($variationId > 0) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Validate the stored payload.
     *
     * @param array<string, mixed> $payload
     */
    private function validatePayload(
        array $payload,
        string $artifactId
    ): bool {
        if ((int) ($payload['version'] ?? 0) !== self::VERSION) {
            return false;
        }

        if (
            !isset($payload['artifact_id'])
            || !is_string($payload['artifact_id'])
            || $payload['artifact_id'] !== $artifactId
        ) {
            return false;
        }

        $createdBy = (int) (
            $payload['created_by']
            ?? 0
        );

        if ($createdBy <= 0) {
            return false;
        }

        if (
            !isset($payload['snapshot_uuid'])
            || !is_string($payload['snapshot_uuid'])
            || $payload['snapshot_uuid'] === ''
        ) {
            return false;
        }

        $createdAt = (int) (
            $payload['created_at']
            ?? 0
        );

        $expiresAt = (int) (
            $payload['expires_at']
            ?? 0
        );

        if ($createdAt <= 0 || $expiresAt <= $createdAt) {
            return false;
        }

        if (time() > $expiresAt) {
            return false;
        }

        $mappings = $payload['adoption_mappings']
            ?? null;

        if (!is_array($mappings)) {
            return false;
        }

        if (
            (int) (
                $payload['approved_mapping_count']
                ?? 0
            ) !== count($mappings)
        ) {
            return false;
        }

        if (
            (int) (
                $payload['approved_mapping_count']
                ?? 0
            ) !== self::EXPECTED_APPROVED_MAPPINGS
        ) {
            return false;
        }

        $variantOwnershipCount = $this->countExplicitVariantOwnership(
            $mappings
        );

        if (
            (int) (
                $payload['explicit_variant_ownership_count']
                ?? 0
            ) !== $variantOwnershipCount
        ) {
            return false;
        }

        if (
            $variantOwnershipCount
            !== self::EXPECTED_VARIANT_OWNERSHIP
        ) {
            return false;
        }

        $storedHash = (string) (
            $payload['mapping_hash']
            ?? ''
        );

        if ($storedHash === '') {
            return false;
        }

        $calculatedHash = $this->hashMappings(
            $mappings
        );

        if (!hash_equals(
            $storedHash,
            $calculatedHash
        )) {
            return false;
        }

        $verification = $payload['verification']
            ?? null;

        if (!is_array($verification)) {
            return false;
        }

        $verificationPass = (bool) (
            $verification['pass']
            ?? $verification['status']
            ?? false
        );

        if (!$verificationPass) {
            return false;
        }

        $ownershipDryRun = $payload['ownership_dry_run']
            ?? null;

        if (!is_array($ownershipDryRun)) {
            return false;
        }

        $ownershipDryRunPass = (bool) (
            $ownershipDryRun['pass']
            ?? $ownershipDryRun['status']
            ?? false
        );

        if (!$ownershipDryRunPass) {
            return false;
        }

        return true;
    }

    /**
     * Generate a cryptographically random artifact ID.
     */
    private function generateArtifactId(): string
    {
        return bin2hex(
            random_bytes(32)
        );
    }

    /**
     * Validate artifact ID format.
     */
    private function isValidArtifactId(
        string $artifactId
    ): bool {
        return preg_match(
            '/^[a-f0-9]{64}$/',
            $artifactId
        ) === 1;
    }

    /**
     * Build the WordPress option name.
     */
    private function optionName(
        string $artifactId
    ): string {
        return self::OPTION_PREFIX . $artifactId;
    }

    /**
     * Calculate an integrity hash for the exact mapping array.
     *
     * serialize() is intentional here. We are hashing the exact PHP
     * structure produced by the verified mapping phase rather than
     * reconstructing or normalizing it.
     *
     * @param array<int|string, array<string, mixed>> $adoptionMappings
     */
    private function hashMappings(
        array $adoptionMappings
    ): string {
        return hash(
            'sha256',
            serialize($adoptionMappings)
        );
    }
}