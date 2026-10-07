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