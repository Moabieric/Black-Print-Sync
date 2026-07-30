<?php

namespace BlackPrint\Commerce\Sync\Scheduler;

use BlackPrint\Commerce\Sync\Kernel\SyncManager;

class Scheduler
{
    private array $jobs = [];

    public function __construct(
        private SyncManager $syncManager
    ) {
    }

    /**
     * Register a scheduled job.
     */
    public function register(
        string $supplier,
        string $jobName,
        string $schedule
    ): void {

        $hook = $this->hook(
            $supplier,
            $jobName
        );

        $this->jobs[] = [

            'supplier' => $supplier,

            'job' => $jobName,

            'schedule' => $schedule,

            'hook' => $hook,

        ];

        add_action(
            $hook,
            function () use (
                $supplier,
                $jobName
            ) {

                $this->syncManager->dispatch(

                    $supplier,

                    $jobName,

                    [

                        'triggered_by' => 'scheduler',

                    ]

                );

            }
        );
    }

    /**
     * Register cron events.
     */
    public function boot(): void
    {
        foreach ($this->jobs as $job) {

            if (! wp_next_scheduled(
                $job['hook']
            )) {

                wp_schedule_event(

                    time(),

                    $job['schedule'],

                    $job['hook']

                );

            }
        }
    }

    /**
     * Build hook name.
     */
    private function hook(
        string $supplier,
        string $jobName
    ): string {

        return sprintf(

            'bp_sync_%s_%s',

            $supplier,

            $jobName

        );
    }
}