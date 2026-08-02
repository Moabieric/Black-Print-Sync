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
    string $resource,
    string $jobClass
): void {

    $this->jobs[$supplier][$resource] = $jobClass;
}

    /**
     * Resolve a job instance.
     */
    public function resolve(
    string $supplier,
    string $resource
): SyncJobInterface {

        if (! isset($this->jobs[$supplier][$resource])) {
            throw new InvalidArgumentException(
                sprintf(
    'Resource [%s:%s] is not registered.',
    $supplier,
    $resource
)
            );
        }

        $class = $this->jobs[$supplier][$resource];

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
    string $resource
): bool {

    return isset(
        $this->jobs[$supplier][$resource]
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