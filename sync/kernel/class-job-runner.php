<?php

namespace BlackPrint\Commerce\Sync\Kernel;

use Exception;

class JobRunner
{
    public function __construct(
        private JobDispatcher $dispatcher
    ) {
    }

    /**
     * Execute a sync job.
     */
    public function run(
        JobContext $context
    ): SyncResult {

        $lockKey = sprintf(
            '%s:%s',
            $context->supplier(),
            $context->jobName()
        );

        if (! LockManager::acquire($lockKey)) {

            return new SyncResult(
                success: false,
                failed: 1,
                errors: [
                    sprintf(
                        'Job [%s] is already running.',
                        $lockKey
                    )
                ]
            );
        }

        try {

            $job = $this->dispatcher->resolve(
                $context->supplier(),
                $context->jobName()
            );

            return $job->execute($context);

        } catch (Exception $e) {

            return new SyncResult(
                success: false,
                failed: 1,
                errors: [
                    $e->getMessage()
                ]
            );

        } finally {

            LockManager::release($lockKey);

        }
    }
}