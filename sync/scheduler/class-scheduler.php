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
    string $resource,
    string $schedule,
    string $jobType = 'scheduled'
): void {

        $hook = $this->hook(
            $supplier,
            $resource
        );

        $this->jobs[] = [

    'supplier' => $supplier,

    'resource' => $resource,

    'schedule' => $schedule,

    'job_type' => $jobType,

    'hook' => $hook,

];

        add_action(
            $hook,
            function () use (
    $supplier,
    $resource,
    $jobType
) {

                $this->syncManager->dispatch(

    $supplier,

    $resource,

    [

        'job_type' => $jobType,

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
        string $resource
    ): string {

        return sprintf(

    'bp_sync_%s_%s',

    $supplier,

    $resource

);
    }
}