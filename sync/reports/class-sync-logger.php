<?php

namespace BlackPrint\Commerce\Sync\Reports;

class SyncLogger
{
    private const OPTION_KEY = 'bp_sync_logs';

    public function log(
        string $level,
        string $message,
        array $context = []
    ): void {

        $logs = get_option(self::OPTION_KEY, []);

        $logs[] = [

            'timestamp' => gmdate('Y-m-d H:i:s'),

            'level' => $level,

            'message' => $message,

            'context' => $context,

        ];

        update_option(self::OPTION_KEY, $logs, false);
    }

    public function debug(
        string $message,
        array $context = []
    ): void {

        $this->log('debug', $message, $context);
    }

    public function info(
        string $message,
        array $context = []
    ): void {

        $this->log('info', $message, $context);
    }

    public function warning(
        string $message,
        array $context = []
    ): void {

        $this->log('warning', $message, $context);
    }

    public function error(
        string $message,
        array $context = []
    ): void {

        $this->log('error', $message, $context);
    }

    public function critical(
        string $message,
        array $context = []
    ): void {

        $this->log('critical', $message, $context);
    }
}