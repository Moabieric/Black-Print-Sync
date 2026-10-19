<?php

defined('ABSPATH') || exit;

$result =
    is_array($result ?? null)
        ? $result
        : [];

$snapshotUuid =
    (string) (
        $result['snapshot_uuid']
        ?? ''
    );

$artifactId =
    (string) (
        $result['step_7a_artifact_id']
        ?? ''
    );

$success =
    !empty(
        $result['success']
    );

$readOnly =
    !empty(
        $result['read_only']
    );

$auditCompleted =
    !empty(
        $result['audit_completed']
    );

$artifactLoaded =
    !empty(
        $result['step_7a_artifact_loaded']
    );

$rejectionCounts =
    isset(
        $result['rejection_counts']
    )
    && is_array(
        $result['rejection_counts']
    )
        ? $result['rejection_counts']
        : [];

$auditSummary =
    isset(
        $result['audit_summary']
    )
    && is_array(
        $result['audit_summary']
    )
        ? $result['audit_summary']
        : [];

?>

<?php if (empty($batchActionView)) : ?>
<div class="wrap">

    <h1>
        <?php
        echo esc_html(
            'BlackPrint — WooCommerce Image Repair Preflight'
        );
        ?>
    </h1>

    <p>
        <strong>
            Step 7B — Read-only repair preflight
        </strong>
    </p>

    <div class="notice notice-warning">

        <p>
            <strong>
                READ-ONLY — NO REPAIRS ARE PERFORMED
            </strong>
        </p>

        <p>
            This preflight validates Step 6 repair candidates against
            the exact Step 7A canonical primary-image artifact.
            It does not download images, create attachments, modify
            products, assign featured images, or perform any repair.
        </p>

    </div>

    <h2>
        Step 7A Artifact
    </h2>

    <table
        class="widefat striped"
        style="max-width: 900px;"
    >

        <tbody>

            <tr>

                <td style="width: 280px;">
                    <strong>
                        Snapshot UUID
                    </strong>
                </td>

                <td>
                    <code>
                        <?php
                        echo esc_html(
                            $snapshotUuid
                        );
                        ?>
                    </code>
                </td>

            </tr>

            <tr>

                <td>
                    <strong>
                        Step 7A Artifact ID
                    </strong>
                </td>

                <td>

                    <?php if ($artifactId !== '') : ?>

                        <code>
                            <?php
                            echo esc_html(
                                $artifactId
                            );
                            ?>
                        </code>

                    <?php else : ?>

                        <em>
                            No artifact selected.
                        </em>

                    <?php endif; ?>

                </td>

            </tr>

            <?php if ($artifactId !== '') : ?>

                <tr>

                    <td>
                        <strong>
                            Artifact loaded
                        </strong>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            $artifactLoaded
                                ? 'Yes'
                                : 'No'
                        );
                        ?>
                    </td>

                </tr>

            <?php endif; ?>

        </tbody>

    </table>

    <?php if (!$success && $artifactId === '') : ?>

        <h2 style="margin-top: 30px;">
            Run Preflight
        </h2>

        <p>
            Enter the exact Step 7A Artifact ID produced by the
            Canonical Primary Image Resolver.
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
                value="bp_woocommerce_image_repair_preflight"
            >

            <?php
            wp_nonce_field(
                'bp_woocommerce_image_repair_preflight'
            );
            ?>

            <table
                class="form-table"
                style="max-width: 900px;"
            >

                <tbody>

                    <tr>

                        <th scope="row">
                            <label for="step_7a_artifact_id">
                                Step 7A Artifact ID
                            </label>
                        </th>

                        <td>

                            <input
                                type="text"
                                id="step_7a_artifact_id"
                                name="step_7a_artifact_id"
                                class="regular-text"
                                required
                            >

                            <p class="description">
                                The artifact must belong to the locked
                                snapshot shown above.
                            </p>

                        </td>

                    </tr>

                </tbody>

            </table>

            <?php
            submit_button(
                'Run Step 7B Read-Only Preflight'
            );
            ?>

        </form>

    <?php endif; ?>

    <?php if (!empty($result['error'])) : ?>

        <div class="notice notice-error">

            <p>
                <strong>
                    Step 7B failed:
                </strong>

                <?php
                echo esc_html(
                    $result['error']
                );
                ?>
            </p>

        </div>

    <?php elseif ($success) : ?>

        <div class="notice notice-success">

            <p>
                <strong>
                    Step 7B preflight completed successfully.
                </strong>
            </p>

        </div>

        <h2 style="margin-top: 30px;">
            Preflight Summary
        </h2>

        <table
            class="widefat striped"
            style="max-width: 900px;"
        >

            <tbody>

                <tr>

                    <td style="width: 320px;">
                        <strong>
                            Read-only
                        </strong>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            $readOnly
                                ? 'Yes'
                                : 'NO — UNEXPECTED'
                        );
                        ?>
                    </td>

                </tr>

                <tr>

                    <td>
                        <strong>
                            Step 7A artifact loaded
                        </strong>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            $artifactLoaded
                                ? 'Yes'
                                : 'No'
                        );
                        ?>
                    </td>

                </tr>

                <tr>

                    <td>
                        <strong>
                            Step 6 audit completed
                        </strong>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            $auditCompleted
                                ? 'Yes'
                                : 'No'
                        );
                        ?>
                    </td>

                </tr>

                <tr>

                    <td>
                        <strong>
                            Step 6 repair candidates
                        </strong>
                    </td>

                    <td>
                        <strong>
                            <?php
                            echo esc_html(
                                number_format_i18n(
                                    (int) (
                                        $result[
                                            'total_candidates'
                                        ]
                                        ?? 0
                                    )
                                )
                            );
                            ?>
                        </strong>
                    </td>

                </tr>

                <tr>

                    <td>
                        <strong>
                            Step 7B eligible candidates
                        </strong>
                    </td>

                    <td>
                        <strong>
                            <?php
                            echo esc_html(
                                number_format_i18n(
                                    (int) (
                                        $result[
                                            'eligible_candidates'
                                        ]
                                        ?? 0
                                    )
                                )
                            );
                            ?>
                        </strong>
                    </td>

                </tr>

                <tr>

                    <td>
                        <strong>
                            Rejected candidates
                        </strong>
                    </td>

                    <td>
                        <strong>
                            <?php
                            echo esc_html(
                                number_format_i18n(
                                    (int) (
                                        $result[
                                            'rejected_candidates'
                                        ]
                                        ?? 0
                                    )
                                )
                            );
                            ?>
                        </strong>
                    </td>

                </tr>

            </tbody>

        </table>


        <?php
        $eligibleCount = (int) ($result['eligible_candidates'] ?? 0);
        $rejectedCount = (int) ($result['rejected_candidates'] ?? -1);
        ?>

        <?php if (
            $success
            && $readOnly
            && $artifactLoaded
            && $auditCompleted
            && $eligibleCount > 0
            && $rejectedCount === 0
        ) : ?>

            <h2 style="margin-top: 30px;">Create Controlled Repair Run</h2>

            <p>
                This creates a persisted manifest after repeating the read-only
                preflight against this exact snapshot and Step 7A artifact.
                It does not repair images.
            </p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="bp_woocommerce_image_repair_create_batch_run">
                <input type="hidden" name="step_7a_artifact_id" value="<?php echo esc_attr($artifactId); ?>">
                <?php wp_nonce_field('bp_woocommerce_image_repair_create_batch_run'); ?>
                <?php submit_button('Create Batch Run (maximum 10 products per batch)'); ?>
            </form>

        <?php endif; ?>

        <h2 style="margin-top: 30px;">
            Rejection Reasons
        </h2>

        <table
            class="widefat striped"
            style="max-width: 900px;"
        >

            <thead>

                <tr>
                    <th>
                        Status
                    </th>

                    <th>
                        Count
                    </th>
                </tr>

            </thead>

            <tbody>

                <?php foreach ($rejectionCounts as $status => $count) : ?>

                    <tr>

                        <td>
                            <code>
                                <?php
                                echo esc_html(
                                    (string) $status
                                );
                                ?>
                            </code>
                        </td>

                        <td>
                            <?php
                            echo esc_html(
                                number_format_i18n(
                                    (int) $count
                                )
                            );
                            ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

        <h2 style="margin-top: 30px;">
            Step 6 Audit Reference
        </h2>

        <table
            class="widefat striped"
            style="max-width: 900px;"
        >

            <tbody>

                <tr>

                    <td style="width: 320px;">
                        <strong>
                            Canonical products
                        </strong>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                (int) (
                                    $auditSummary[
                                        'canonical_products'
                                    ]
                                    ?? 0
                                )
                            )
                        );
                        ?>
                    </td>

                </tr>

                <tr>

                    <td>
                        <strong>
                            Adopted canonical products
                        </strong>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                (int) (
                                    $auditSummary[
                                        'adopted_canonical_products'
                                    ]
                                    ?? 0
                                )
                            )
                        );
                        ?>
                    </td>

                </tr>

                <tr>

                    <td>
                        <strong>
                            Healthy
                        </strong>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                (int) (
                                    $auditSummary[
                                        'healthy'
                                    ]
                                    ?? 0
                                )
                            )
                        );
                        ?>
                    </td>

                </tr>

                <tr>

                    <td>
                        <strong>
                            Repairable
                        </strong>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                (int) (
                                    $auditSummary[
                                        'repairable'
                                    ]
                                    ?? 0
                                )
                            )
                        );
                        ?>
                    </td>

                </tr>

            </tbody>

        </table>

        <div class="notice notice-info" style="margin-top: 30px;">

            <p>
                <strong>
                    No repair has been executed.
                </strong>
            </p>

            <p>
                The eligible candidates shown by this preflight are only
                candidates for the later controlled repair phase.
            </p>

        </div>

    <?php endif; ?>

</div>


<?php else : ?>

<?php
$batchActionResult = is_array($batchActionResult ?? null)
    ? $batchActionResult
    : [];
$operation = is_array($batchActionResult['operation'] ?? null)
    ? $batchActionResult['operation']
    : [];
$run = is_array($batchActionResult['run'] ?? null)
    ? $batchActionResult['run']
    : [];
$runId = (string) ($batchActionResult['run_id'] ?? '');
$runStatus = strtoupper((string) ($run['status'] ?? ''));
$cursor = (int) ($run['next_index'] ?? 0);
$manifestCount = (int) ($run['manifest_count'] ?? 0);
$batchSize = (int) ($run['batch_size'] ?? 10);
$inProgress = false;

foreach (['in_progress', 'in_progress_index', 'in_progress_item'] as $key) {
    if (
        array_key_exists($key, $run)
        && $run[$key] !== null
        && $run[$key] !== false
        && $run[$key] !== ''
    ) {
        $inProgress = true;
    }
}

$outcomes = is_array($run['outcomes'] ?? null) ? $run['outcomes'] : [];

foreach ($outcomes as $outcome) {
    if (
        is_array($outcome)
        && (int) ($outcome['manifest_index'] ?? -1) === $cursor
        && strtoupper((string) ($outcome['status'] ?? '')) === 'IN_PROGRESS'
    ) {
        $inProgress = true;
    }
}

$canExecute = $runId !== ''
    && in_array($runStatus, ['READY', 'RUNNING'], true)
    && $cursor < $manifestCount
    && !$inProgress;
?>

<div class="wrap">
    <h1>BlackPrint — Step 7B Batch Run</h1>

    <p>
        Batch execution is separate from preflight. Each execution request
        processes at most the batch size saved in the persisted run.
    </p>

    <?php if (!empty($operation['success'])) : ?>
        <div class="notice notice-success"><p>
            <?php echo esc_html((string) ($operation['message'] ?? 'The requested batch action completed.')); ?>
        </p></div>
    <?php else : ?>
        <div class="notice notice-error"><p>
            <strong><?php echo esc_html((string) ($operation['status'] ?? 'BATCH_ACTION_FAILED')); ?></strong>
            <?php echo esc_html((string) ($operation['message'] ?? 'The batch action did not complete successfully.')); ?>
        </p></div>
    <?php endif; ?>

    <?php if ($runId !== '') : ?>
        <h2>Persisted Run State</h2>
        <table class="widefat striped" style="max-width: 900px;">
            <tbody>
                <tr><td><strong>Run ID</strong></td><td><code><?php echo esc_html($runId); ?></code></td></tr>
                <tr><td><strong>Snapshot UUID</strong></td><td><code><?php echo esc_html((string) ($run['snapshot_uuid'] ?? '')); ?></code></td></tr>
                <tr><td><strong>Step 7A artifact</strong></td><td><code><?php echo esc_html((string) ($run['step_7a_artifact_id'] ?? '')); ?></code></td></tr>
                <tr><td><strong>Run status</strong></td><td><?php echo esc_html($runStatus !== '' ? $runStatus : 'UNAVAILABLE'); ?></td></tr>
                <tr><td><strong>Cursor / manifest count</strong></td><td><?php echo esc_html((string) $cursor . ' / ' . (string) $manifestCount); ?></td></tr>
                <tr><td><strong>Stored batch size</strong></td><td><?php echo esc_html((string) $batchSize); ?></td></tr>
                <?php if (array_key_exists('processed_this_batch', $operation)) : ?>
                    <tr><td><strong>Processed in last request</strong></td><td><?php echo esc_html((string) (int) $operation['processed_this_batch']); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if (!empty($operation['state_reload_error'])) : ?>
            <div class="notice notice-error"><p>
                Persisted state reload error: <?php echo esc_html((string) $operation['state_reload_error']); ?>
            </p></div>
        <?php endif; ?>

        <?php if ($canExecute) : ?>
            <h2 style="margin-top: 25px;">Execute Next Batch</h2>
            <p>
                This is a live operation. It may download canonical images,
                create media attachments, and assign featured images to
                validated existing owned products.
            </p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="bp_woocommerce_image_repair_execute_batch">
                <input type="hidden" name="run_id" value="<?php echo esc_attr($runId); ?>">
                <?php wp_nonce_field('bp_woocommerce_image_repair_execute_batch'); ?>
                <?php submit_button('Execute Next Batch (' . min($batchSize, $manifestCount - $cursor) . ' maximum)'); ?>
            </form>
        <?php elseif ($inProgress) : ?>
            <div class="notice notice-warning"><p>
                An in-progress marker exists at the current cursor. Automatic
                continuation is withheld; reconcile this run before retrying.
            </p></div>
        <?php elseif ($cursor >= $manifestCount && $manifestCount > 0) : ?>
            <div class="notice notice-success"><p>The persisted manifest cursor has reached the end.</p></div>
        <?php endif; ?>
    <?php else : ?>
        <p>No persisted run could be loaded for display. Do not execute a batch until its run ID and state can be verified.</p>
    <?php endif; ?>
</div>

<?php endif; ?>
