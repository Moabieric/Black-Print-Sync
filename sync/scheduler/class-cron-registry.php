<?php

namespace BlackPrint\Commerce\Sync\Scheduler;

class CronRegistry
{
    public function register(): void
    {
        add_filter(
            'cron_schedules',
            [$this, 'schedules']
        );
    }

    public function schedules(
        array $schedules
    ): array {

        $schedules['every_15_minutes'] = [

            'interval' => 15 * MINUTE_IN_SECONDS,

            'display' => 'Every 15 Minutes',

        ];

        $schedules['daily_sync'] = [

            'interval' => DAY_IN_SECONDS,

            'display' => 'Daily',

        ];

        return $schedules;
    }
}