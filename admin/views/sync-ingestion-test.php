<?php

declare(strict_types=1);

defined('ABSPATH') || exit;


/*
|--------------------------------------------------------------------------
| Request State
|--------------------------------------------------------------------------
*/

$status = isset($_GET['bp_sync_test'])
    ? sanitize_key(
        wp_unslash(
            $_GET['bp_sync_test']
        )
    )
    : '';

$jobUuid = isset($_GET['job_uuid'])
    ? sanitize_text_field(
        wp_unslash(
            $_GET['job_uuid']
        )
    )
    : '';

$snapshotUuid = isset($_GET['snapshot_uuid'])
    ? sanitize_text_field(
        wp_unslash(
            $_GET['snapshot_uuid']
        )
    )
    : '';

$error = isset($_GET['errors'])
    ? sanitize_text_field(
        wp_unslash(
            $_GET['errors']
        )
    )
    : '';

?>

<div class="wrap">

    <h1>BlackPrint Sync Ingestion Test</h1>


    <p>
        Run one controlled Amrod product ingestion through the
        BlackPrint OS Sync Engine.
    </p>


    <p>
        This test creates a SyncJob, retrieves raw supplier data,
        creates an immutable Snapshot and stores the immutable raw
        payload.
    </p>


    <p>
        <strong>
            No WooCommerce products are created or modified.
        </strong>
    </p>


    <?php if ($status === 'success') : ?>

        <div class="notice notice-success">

            <p>
                <strong>
                    Amrod product ingestion completed successfully.
                </strong>
            </p>


            <?php if ($jobUuid !== '') : ?>

                <p>
                    <strong>Job UUID:</strong>

                    <?php
                    echo esc_html(
                        $jobUuid
                    );
                    ?>
                </p>

            <?php endif; ?>


            <?php if ($snapshotUuid !== '') : ?>

                <p>
                    <strong>Snapshot UUID:</strong>

                    <?php
                    echo esc_html(
                        $snapshotUuid
                    );
                    ?>
                </p>

            <?php endif; ?>

        </div>

    <?php elseif ($status === 'failed') : ?>

        <div class="notice notice-error">

            <p>
                <strong>
                    Amrod product ingestion failed.
                </strong>
            </p>


            <?php if ($error !== '') : ?>

                <p>
                    <?php
                    echo esc_html(
                        $error
                    );
                    ?>
                </p>

            <?php endif; ?>

        </div>

    <?php elseif ($status === 'exception') : ?>

        <div class="notice notice-error">

            <p>
                <strong>
                    The ingestion test raised an exception.
                </strong>
            </p>


            <?php if ($error !== '') : ?>

                <p>
                    <?php
                    echo esc_html(
                        $error
                    );
                    ?>
                </p>

            <?php endif; ?>

        </div>

    <?php endif; ?>


    <hr>


    <h2>Run Controlled Ingestion</h2>


    <form
        method="post"
        action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
    >

        <input
            type="hidden"
            name="action"
            value="bp_run_amrod_product_ingestion_test"
        >


        <?php

        wp_nonce_field(
            'bp_run_amrod_product_ingestion_test'
        );

        submit_button(
            'Run Amrod Product Ingestion Test',
            'primary'
        );

        ?>

    </form>


    <hr>


    <h2>Expected Persistence Chain</h2>


    <pre>
SyncJob
    ↓
wp_bp_sync_jobs.uuid

Snapshot
    ↓
wp_bp_snapshots.sync_job_uuid
    =
Job UUID

Snapshot Payload
    ↓
wp_bp_snapshot_payloads.snapshot_uuid
    =
Snapshot UUID
    </pre>


</div>
