<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Sync\Kernel;

use BlackPrint\Commerce\Sync\Repositories\SyncJobRepository;
use Throwable;

defined('ABSPATH') || exit;

final class JobRunner
{
    public function __construct(
        private readonly JobDispatcher $dispatcher,
        private readonly SyncJobRepository $jobs
    ) {
    }

    /**
     * Execute a synchronization job.
     */
    public function run(
        JobContext $context
    ): SyncResult {

        $lockKey = sprintf(
            '%s:%s:%s',
            $context->supplier(),
            $context->resource(),
            $context->jobType()
        );

        /*
        |--------------------------------------------------------------------------
        | Acquire Execution Lock
        |--------------------------------------------------------------------------
        */

        if (! LockManager::acquire($lockKey)) {

            $message = sprintf(
                'Job [%s] is already running.',
                $lockKey
            );

            $this->jobs->markCancelled(
                $context->jobId()
            );

            return new SyncResult(
                success: false,
                failed: 1,
                errors: [
                    $message
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Mark Job Running
        |--------------------------------------------------------------------------
        */

        $this->jobs->markRunning(
            $context->jobId()
        );

        try {

            /*
            |--------------------------------------------------------------------------
            | Resolve Job
            |--------------------------------------------------------------------------
            */

            $job = $this->dispatcher->resolve(
                $context->supplier(),
                $context->resource()
            );

            /*
            |--------------------------------------------------------------------------
            | Execute Job
            |--------------------------------------------------------------------------
            */

            $result = $job->execute(
                $context
            );

            /*
            |--------------------------------------------------------------------------
            | Mark Job Completed
            |--------------------------------------------------------------------------
            */

            if ($result->success()) {

                $this->jobs->markCompleted(
                    $context->jobId(),
                    $result->processed()
                );

            } else {

                $message = implode(
                    '; ',
                    $result->errors()
                );

                $this->jobs->markFailed(
                    $context->jobId(),
                    $message
                );
            }

            return $result;

        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Mark Job Failed
            |--------------------------------------------------------------------------
            */

            $this->jobs->markFailed(
                $context->jobId(),
                $e->getMessage()
            );

            return new SyncResult(
                success: false,
                failed: 1,
                errors: [
                    $e->getMessage()
                ]
            );

        } finally {

            /*
            |--------------------------------------------------------------------------
            | Release Execution Lock
            |--------------------------------------------------------------------------
            */

            LockManager::release(
                $lockKey
            );
        }
    }
}
