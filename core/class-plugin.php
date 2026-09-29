<?php

declare(strict_types=1);

namespace BlackPrint\Core;

use BlackPrint\Commerce\Sync\Kernel\SyncManager;
use BlackPrint\Commerce\Sync\Services\SyncServiceProvider;

final class Plugin
{
    private static ?self $instance = null;

    private SyncManager $syncManager;

    private function __construct()
    {
        $this->boot();
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function boot(): void
    {
        $provider = new SyncServiceProvider();

        $this->syncManager = $provider->register();
    }

    public function syncManager(): SyncManager
    {
        return $this->syncManager;
    }
}