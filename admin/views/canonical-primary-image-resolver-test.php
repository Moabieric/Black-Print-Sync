<?php

defined('ABSPATH') || exit;

$result =
    is_array($result ?? null)
        ? $result
        : null;

?>

<div class="wrap">

    <h1>
        BlackPrint — Canonical Primary Image Resolver
    </h1>

    <p>
        <strong>Step 7A — Real Snapshot Diagnostic</strong>
    </p>

    <?php if ($result === null) : ?>

        <div class="notice notice-info">
            <p>
                This is a read-only diagnostic against the immutable
                BlackPrint canonical snapshot.
            </p>
        </div>

        <p>
            The diagnostic will:
        </p>

        <ul>
            <li>
                Load the existing immutable snapshot.
            </li>
            <li>
                Normalize the canonical products.
            </li>
            <li>
                Evaluate every canonical product through the
                <code>CanonicalPrimaryImageResolver</code>.
            </li>
            <li>
                Report deterministic primary-image resolution results.
            </li>
        </ul>

        <p>
            <strong>
                No WooCommerce products, attachments, ownership metadata,
                featured images, or other store data will be modified.
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
                value="bp_test_canonical_primary_image_resolver"
            >

            <?php
            wp_nonce_field(
                'bp_test_canonical_primary_image_resolver'
            );
            ?>

            <?php
            submit_button(
                'Run Real Snapshot Resolver Diagnostic'
            );
            ?>

        </form>

    <?php else : ?>

        <p>
            <strong>Mode:</strong>
            Read-only
        </p>

        <p>
            <strong>Snapshot UUID:</strong>

            <code>
                <?php
                echo esc_html(
                    $result['snapshot_uuid']
                    ?? ''
                );
                ?>
            </code>
        </p>

        <?php if (! empty($result['error'])) : ?>

            <div class="notice notice-error">
                <p>
                    <strong>
                        Diagnostic failed:
                    </strong>

                    <?php
                    echo esc_html(
                        $result['error']
                    );
                    ?>
                </p>
            </div>

        <?php elseif (! empty($result['success'])) : ?>

            <div class="notice notice-success">
                <p>
                    <strong>
                        Diagnostic completed successfully.
                    </strong>
                </p>
            </div>

        <?php endif; ?>

        <h2>
            Normalization
        </h2>

        <table
            class="widefat striped"
            style="max-width: 700px;"
        >

            <tbody>

                <tr>
                    <td>
                        Normalized canonical products
                    </td>

                    <td>
                        <strong>
                            <?php
                            echo esc_html(
                                (string) (
                                    $result['normalized']
                                    ?? 0
                                )
                            );
                            ?>
                        </strong>
                    </td>
                </tr>

                <tr>
                    <td>
                        Normalization errors
                    </td>

                    <td>
                        <strong>
                            <?php
                            echo esc_html(
                                (string) (
                                    $result[
                                        'normalization_error_count'
                                    ]
                                    ?? 0
                                )
                            );
                            ?>
                        </strong>
                    </td>
                </tr>

            </tbody>

        </table>

        <h2>
            Resolver Results
        </h2>

        <table
            class="widefat striped"
            style="max-width: 700px;"
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

                <tr>
                    <td>
                        <code>RESOLVED</code>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            (string) (
                                $result[
                                    'resolver_counts'
                                ]['resolved']
                                ?? 0
                            )
                        );
                        ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        <code>NO_DEFAULT_IMAGE</code>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            (string) (
                                $result[
                                    'resolver_counts'
                                ]['no_default_image']
                                ?? 0
                            )
                        );
                        ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        <code>AMBIGUOUS_PRIMARY_IMAGE</code>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            (string) (
                                $result[
                                    'resolver_counts'
                                ]['ambiguous_primary_image']
                                ?? 0
                            )
                        );
                        ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        <code>
                            PRIMARY_IMAGE_URL_UNAVAILABLE
                        </code>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            (string) (
                                $result[
                                    'resolver_counts'
                                ]['primary_image_url_unavailable']
                                ?? 0
                            )
                        );
                        ?>
                    </td>
                </tr>

            </tbody>

        </table>

        <?php
        $resolvedSamples =
            $result['resolved_samples']
            ?? [];
        ?>

        <?php if (! empty($resolvedSamples)) : ?>

            <h2>
                Resolved Samples
            </h2>

            <table class="widefat striped">

                <thead>

                    <tr>
                        <th>
                            Index
                        </th>

                        <th>
                            Canonical Code
                        </th>

                        <th>
                            Resolved Primary Image URL
                        </th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach (
                        $resolvedSamples
                        as $sample
                    ) : ?>

                        <tr>

                            <td>
                                <?php
                                echo esc_html(
                                    (string) (
                                        $sample['index']
                                        ?? ''
                                    )
                                );
                                ?>
                            </td>

                            <td>
                                <code>
                                    <?php
                                    echo esc_html(
                                        (string) (
                                            $sample[
                                                'canonical_code'
                                            ]
                                            ?? ''
                                        )
                                    );
                                    ?>
                                </code>
                            </td>

                            <td>
                                <code
                                    style="
                                        word-break: break-all;
                                    "
                                >
                                    <?php
                                    echo esc_html(
                                        (string) (
                                            $sample['url']
                                            ?? ''
                                        )
                                    );
                                    ?>
                                </code>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        <?php endif; ?>

        <?php
        $failureSamples =
            $result['failure_samples']
            ?? [];
        ?>

        <?php if (! empty($failureSamples)) : ?>

            <h2>
                Failure Samples
            </h2>

            <table class="widefat striped">

                <thead>

                    <tr>
                        <th>
                            Index
                        </th>

                        <th>
                            Canonical Code
                        </th>

                        <th>
                            Status
                        </th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach (
                        $failureSamples
                        as $sample
                    ) : ?>

                        <tr>

                            <td>
                                <?php
                                echo esc_html(
                                    (string) (
                                        $sample['index']
                                        ?? ''
                                    )
                                );
                                ?>
                            </td>

                            <td>
                                <code>
                                    <?php
                                    echo esc_html(
                                        (string) (
                                            $sample[
                                                'canonical_code'
                                            ]
                                            ?? ''
                                        )
                                    );
                                    ?>
                                </code>
                            </td>

                            <td>
                                <code>
                                    <?php
                                    echo esc_html(
                                        (string) (
                                            $sample['status']
                                            ?? ''
                                        )
                                    );
                                    ?>
                                </code>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        <?php endif; ?>

    <?php endif; ?>

</div>