<?php

namespace BlackPrint\Commerce\Sync\Kernel;

use BlackPrint\Commerce\Sync\Contracts\SyncJobInterface;
use InvalidArgumentException;

class JobDispatcher
{
    /**
     * Registered jobs.
     *
     * @var array<string, array<string, string>>
     */
    private array $jobs = [];

    /**
     * Register a job.
     */
    public function register(
        string $supplier,
        string $jobName,
        string $jobClass
    ): void {

        $this->jobs[$supplier][$jobName] = $jobClass;
    }

    /**
     * Resolve a job instance.
     */
    public function resolve(
        string $supplier,
        string $jobName
    ): SyncJobInterface {

        if (! isset($this->jobs[$supplier][$jobName])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Job [%s:%s] is not registered.',
                    $supplier,
                    $jobName
                )
            );
        }

        $class = $this->jobs[$supplier][$jobName];

        $job = new $class();

        if (! $job instanceof SyncJobInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s must implement SyncJobInterface.',
                    $class
                )
            );
        }

        return $job;
    }

    /**
     * Check registration.
     */
    public function has(
        string $supplier,
        string $jobName
    ): bool {

        return isset(
            $this->jobs[$supplier][$jobName]
        );
    }

    /**
     * Return all jobs.
     */
    public function all(): array
    {
        return $this->jobs;
    }
}