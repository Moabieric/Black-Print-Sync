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

    $integrityStatus = isset($_GET['bp_integrity'])
    ? sanitize_key(
        wp_unslash(
            $_GET['bp_integrity']
        )
    )
    : '';

$integrityErrors = isset($_GET['integrity_errors'])
    ? sanitize_text_field(
        wp_unslash(
            $_GET['integrity_errors']
        )
    )
    : '';

$snapshotFound = isset($_GET['snapshot_found'])
    && $_GET['snapshot_found'] === '1';

$payloadFound = isset($_GET['payload_found'])
    && $_GET['payload_found'] === '1';

$recordsExpected = isset($_GET['records_expected'])
    ? sanitize_text_field(
        wp_unslash(
            $_GET['records_expected']
        )
    )
    : '';

$recordsActual = isset($_GET['records_actual'])
    ? sanitize_text_field(
        wp_unslash(
            $_GET['records_actual']
        )
    )
    : '';

$recordsValid = isset($_GET['records_valid'])
    && $_GET['records_valid'] === '1';

$checksumExpected = isset($_GET['checksum_expected'])
    ? sanitize_text_field(
        wp_unslash(
            $_GET['checksum_expected']
        )
    )
    : '';

$checksumActual = isset($_GET['checksum_actual'])
    ? sanitize_text_field(
        wp_unslash(
            $_GET['checksum_actual']
        )
    )
    : '';

$checksumValid = isset($_GET['checksum_valid'])
    && $_GET['checksum_valid'] === '1';

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

<h2>Verify Snapshot Integrity</h2>

<p>
    Verify that an existing immutable snapshot can be restored and
    independently validated against its stored metadata.
</p>

<p>
    This verification is read-only. It does not call Amrod, modify
    the database, or modify WooCommerce.
</p>


<?php if ($integrityStatus === 'success') : ?>

    <div class="notice notice-success">

        <p>
            <strong>
                Snapshot integrity verified successfully.
            </strong>
        </p>

        <p>
            <strong>Snapshot UUID:</strong>
            <?php echo esc_html($snapshotUuid); ?>
        </p>

        <p>
            <strong>Snapshot found:</strong>
            <?php echo $snapshotFound ? 'Yes' : 'No'; ?>
        </p>

        <p>
            <strong>Payload found:</strong>
            <?php echo $payloadFound ? 'Yes' : 'No'; ?>
        </p>

        <p>
            <strong>Expected records:</strong>
            <?php echo esc_html($recordsExpected); ?>
        </p>

        <p>
            <strong>Actual records:</strong>
            <?php echo esc_html($recordsActual); ?>
        </p>

        <p>
            <strong>Record count valid:</strong>
            <?php echo $recordsValid ? 'Yes' : 'No'; ?>
        </p>

        <p>
            <strong>Expected checksum:</strong><br>
            <code><?php echo esc_html($checksumExpected); ?></code>
        </p>

        <p>
            <strong>Actual checksum:</strong><br>
            <code><?php echo esc_html($checksumActual); ?></code>
        </p>

        <p>
            <strong>Checksum valid:</strong>
            <?php echo $checksumValid ? 'Yes' : 'No'; ?>
        </p>

    </div>

<?php elseif (
    $integrityStatus === 'failed'
    || $integrityStatus === 'invalid'
    || $integrityStatus === 'exception'
) : ?>

    <div class="notice notice-error">

        <p>
            <strong>
                Snapshot integrity verification failed.
            </strong>
        </p>

        <?php if ($integrityErrors !== '') : ?>

            <p>
                <?php
                echo esc_html(
                    $integrityErrors
                );
                ?>
            </p>

        <?php endif; ?>

    </div>

<?php endif; ?>


<form
    method="post"
    action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
>

    <input
        type="hidden"
        name="action"
        value="bp_verify_snapshot_integrity"
    >

    <p>

        <label for="bp-snapshot-uuid">

            <strong>Snapshot UUID</strong>

        </label>

        <br>

        <input
            type="text"
            id="bp-snapshot-uuid"
            name="snapshot_uuid"
            class="regular-text"
            value="<?php echo esc_attr($snapshotUuid); ?>"
            required
        >

    </p>


    <?php

    wp_nonce_field(
        'bp_verify_snapshot_integrity'
    );

    submit_button(
        'Verify Snapshot Integrity',
        'secondary'
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

<form
    method="post"
    action="<?php echo esc_url(
        admin_url('admin-post.php')
    ); ?>"
>
    <input
        type="hidden"
        name="action"
        value="bp_test_snapshot_normalization"
    >

    <?php
    wp_nonce_field(
        'bp_test_snapshot_normalization'
    );
    ?>

    <button
        type="submit"
        class="button button-secondary"
    >
        Run Snapshot Normalization Test
    </button>
</form>

<hr>

<h2>WooCommerce Projection Verification</h2>

<p>
    Run the read-only WooCommerce projection verification test.
    This will normalize the existing verified snapshot and generate
    WooCommerce projection plans for inspection.
</p>

<p>
    <strong>No WooCommerce products or variations will be created,
    updated, or deleted.</strong>
</p>

<form
    method="post"
    action="<?php echo esc_url(
        admin_url('admin-post.php')
    ); ?>"
>
    <input
        type="hidden"
        name="action"
        value="bp_test_woocommerce_projection"
    >

    <?php
    wp_nonce_field(
        'bp_test_woocommerce_projection'
    );
    ?>

    <button
        type="submit"
        class="button button-secondary"
    >
        Run WooCommerce Projection Verification
    </button>
</form>

<hr>

<h2>WooCommerce Execution Decision Verification</h2>

<p>
    Run the WooCommerce executor in decision-only mode against the
    verified snapshot.
</p>

<p>
    This test determines whether each projected product would be
    created or updated using BlackPrint ownership identity.
</p>

<p>
    <strong>
        No WooCommerce products or variations will be created,
        updated, or deleted.
    </strong>
</p>

<form
    method="post"
    action="<?php echo esc_url(
        admin_url('admin-post.php')
    ); ?>"
>
    <input
        type="hidden"
        name="action"
        value="bp_test_woocommerce_execution_decisions"
    >

    <?php
    wp_nonce_field(
        'bp_test_woocommerce_execution_decisions'
    );
    ?>

    <button
        type="submit"
        class="button button-secondary"
    >
        Run WooCommerce Execution Decision Verification
    </button>
</form>

<hr>

<h2>12.1 — Controlled WooCommerce Parent Creation</h2>

<p>
    Run a controlled single-product WooCommerce parent creation test
    against the verified snapshot.
</p>

<p>
    This test selects exactly one canonical product, projects it into
    the WooCommerce channel representation, and permits the executor
    to create the WooCommerce parent product when no existing
    BlackPrint-managed parent exists.
</p>

<p>
    <strong>
        Only one WooCommerce parent product may be created by this test.
        Variations are not created or modified.
    </strong>
</p>

<form
    method="post"
    action="<?php echo esc_url(
        admin_url('admin-post.php')
    ); ?>"
>
    <input
        type="hidden"
        name="action"
        value="bp_test_woocommerce_parent_creation"
    >

    <?php
    wp_nonce_field(
        'bp_test_woocommerce_parent_creation'
    );
    ?>

    <button
        type="submit"
        class="button button-primary"
    >
        Run 12.1 Controlled Parent Creation
    </button>
</form>