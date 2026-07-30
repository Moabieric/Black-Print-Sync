<?php

namespace BlackPrint\Commerce\Sync\Kernel;

class LockManager
{
    /**
     * Lock prefix.
     */
    private const PREFIX = 'bp_sync_lock_';

    /**
     * Default lock lifetime (30 minutes).
     */
    private const TTL = 1800;

    /**
     * Acquire a lock.
     */
    public static function acquire(
        string $key,
        int $ttl = self::TTL
    ): bool {

        $lockKey = self::PREFIX . $key;

        if (get_transient($lockKey)) {
            return false;
        }

        set_transient(
            $lockKey,
            [
                'acquired_at' => gmdate('Y-m-d H:i:s'),
                'expires_at'  => gmdate(
                    'Y-m-d H:i:s',
                    time() + $ttl
                ),
            ],
            $ttl
        );

        return true;
    }

    /**
     * Release lock.
     */
    public static function release(string $key): void
    {
        delete_transient(
            self::PREFIX . $key
        );
    }

    /**
     * Check if locked.
     */
    public static function isLocked(
        string $key
    ): bool {
        return get_transient(
            self::PREFIX . $key
        ) !== false;
    }

    /**
     * Get lock information.
     */
    public static function info(
        string $key
    ): ?array {
        $lock = get_transient(
            self::PREFIX . $key
        );

        return $lock ?: null;
    }
}