<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Sync\Kernel;

use BlackPrint\Commerce\Sync\Contracts\SyncJobInterface;
use InvalidArgumentException;

final class JobDispatcher
{
    /**
     * Registered sync jobs.
     *
     * @var array<string, array<string, SyncJobInterface>>
     */
    private array $jobs = [];

    /**
     * Register a job instance.
     */
    public function register(
        SyncJobInterface $job
    ): void {

        $this->jobs[
            $job->supplier()
        ][
            $job->resource()
        ] = $job;
    }

    /**
     * Resolve a registered job.
     */
    public function resolve(
        string $supplier,
        string $resource
    ): SyncJobInterface {

        if (! isset(
            $this->jobs[$supplier][$resource]
        )) {

            throw new InvalidArgumentException(
                sprintf(
                    'Resource [%s:%s] is not registered.',
                    $supplier,
                    $resource
                )
            );
        }

        return $this->jobs[$supplier][$resource];
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
     * Return all registered jobs.
     *
     * @return array<string, array<string, SyncJobInterface>>
     */
    public function all(): array
    {
        return $this->jobs;
    }
}