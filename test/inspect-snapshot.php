<?php

defined('ABSPATH') || exit;

/*
|--------------------------------------------------------------------------
| BlackPrint OS — Snapshot Normalization Smoke Test
|--------------------------------------------------------------------------
|
| This test:
|
| 1. Loads an existing immutable snapshot.
| 2. Restores its raw payload.
| 3. Resolves the Amrod products normalizer.
| 4. Normalizes all supplier records.
| 5. Reports the result without persisting canonical products.
|
*/

add_action(
    'admin_init',
    static function (): void {

        /*
        |--------------------------------------------------------------------------
        | Prevent accidental repeated execution
        |--------------------------------------------------------------------------
        */

        if (
            ! isset($_GET['bp_test_normalization'])
            || $_GET['bp_test_normalization'] !== '1'
        ) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Existing Verified Snapshot
        |--------------------------------------------------------------------------
        */

        $snapshotUuid =
            'e1feb722-4844-4561-bb22-a199a57522d9';


        /*
        |--------------------------------------------------------------------------
        | Execute Normalization
        |--------------------------------------------------------------------------
        */

        try {

            $result = bp_commerce()
                ->normalization()
                ->normalize(
                    $snapshotUuid
                );


            /*
            |--------------------------------------------------------------------------
            | Output Result
            |--------------------------------------------------------------------------
            */

            wp_die(
                '<pre>' .
                esc_html(
                    print_r(
                        $result->toArray(),
                        true
                    )
                ) .
                '</pre>'
            );

        } catch (\Throwable $exception) {

            wp_die(
                '<pre>' .
                esc_html(
                    'Normalization failed: ' .
                    $exception->getMessage()
                ) .
                '</pre>'
            );
        }
    }
);