<?php

declare(strict_types=1);

namespace BlackPrint\Core;

use BlackPrint\Sync\SyncPipeline;
use BlackPrint\Sync\SyncServiceProvider;

final class Plugin
{
    private static ?self $instance = null;

    private SyncPipeline $syncPipeline;

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
        $this->bootSync();
    }

    private function bootSync(): void
    {
        $provider = new SyncServiceProvider();

        $this->syncPipeline = $provider->register();
    }

    public function syncPipeline(): SyncPipeline
    {
        return $this->syncPipeline;
    }
}