<?php

defined('ABSPATH') || exit;

$result =
    is_array($result ?? null)
        ? $result
        : [];

$success =
    ! empty(
        $result['success']
    );

$counts =
    $result['resolver_counts']
    ?? [];

$resolvedSamples =
    $result['resolved_samples']
    ?? [];

$failureSamples =
    $result['failure_samples']
    ?? [];

?>

<div class="wrap">

    <h1>
        BlackPrint — Canonical Primary Image Resolver
    </h1>

    <p>
        Step 7A — Real Snapshot Diagnostic
    </p>

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
                <strong>Diagnostic failed:</strong>
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
                <strong>Diagnostic completed successfully.</strong>
            </p>
        </div>

    <?php endif; ?>

    <h2>Normalization</h2>

    <table class="widefat striped" style="max-width: 700px;">

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
                                $result['normalization_error_count']
                                ?? 0
                            )
                        );
                        ?>
                    </strong>
                </td>
            </tr>

        </tbody>

    </table>

    <h2>Resolver Results</h2>

    <table class="widefat striped" style="max-width: 700px;">

        <thead>
            <tr>
                <th>Status</th>
                <th>Count</th>
            </tr>
        </thead>

        <tbody>

            <tr>
                <td>
                    RESOLVED
                </td>
                <td>
                    <?php
                    echo esc_html(
                        (string) (
                            $counts['resolved']
                            ?? 0
                        )
                    );
                    ?>
                </td>
            </tr>

            <tr>
                <td>
                    NO_DEFAULT_IMAGE
                </td>
                <td>
                    <?php
                    echo esc_html(
                        (string) (
                            $counts['no_default_image']
                            ?? 0
                        )
                    );
                    ?>
                </td>
            </tr>

            <tr>
                <td>
                    AMBIGUOUS_PRIMARY_IMAGE
                </td>
                <td>
                    <?php
                    echo esc_html(
                        (string) (
                            $counts['ambiguous_primary_image']
                            ?? 0
                        )
                    );
                    ?>
                </td>
            </tr>

            <tr>
                <td>
                    PRIMARY_IMAGE_URL_UNAVAILABLE
                </td>
                <td>
                    <?php
                    echo esc_html(
                        (string) (
                            $counts['primary_image_url_unavailable']
                            ?? 0
                        )
                    );
                    ?>
                </td>
            </tr>

        </tbody>

    </table>

    <?php if (! empty($resolvedSamples)) : ?>

        <h2>
            Resolved Samples
        </h2>

        <table class="widefat striped">

            <thead>
                <tr>
                    <th>Index</th>
                    <th>Canonical Code</th>
                    <th>Resolved Primary Image URL</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($resolvedSamples as $sample) : ?>

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
                                        $sample['canonical_code']
                                        ?? ''
                                    )
                                );
                                ?>
                            </code>
                        </td>

                        <td>
                            <code style="word-break: break-all;">
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

    <?php if (! empty($failureSamples)) : ?>

        <h2>
            Failure Samples
        </h2>

        <table class="widefat striped">

            <thead>
                <tr>
                    <th>Index</th>
                    <th>Canonical Code</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($failureSamples as $sample) : ?>

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
                                        $sample['canonical_code']
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

</div>