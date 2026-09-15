<?php
/**
 * BlackPrint Commerce
 *
 * WooCommerce Image Health Audit
 *
 * Step 6 — Read-only image health audit.
 *
 * This view must never perform WooCommerce writes.
 */

defined('ABSPATH') || exit;
?>

<div class="wrap">

    <h1>
        <?php echo esc_html('WooCommerce Image Health Audit'); ?>
    </h1>

    <p>
        <?php
        echo esc_html(
            'Step 6 — Read-only comparison of canonical BlackPrint media '
            . 'against the existing WooCommerce image state.'
        );
        ?>
    </p>

    <div class="notice notice-warning">
        <p>
            <strong>
                <?php echo esc_html('READ-ONLY AUDIT'); ?>
            </strong>
        </p>

        <p>
            <?php
            echo esc_html(
                'This audit does not modify products, variations, ownership '
                . 'metadata, SKUs, attachments, or images. No image downloads '
                . 'or repairs are performed.'
            );
            ?>
        </p>
    </div>

    <?php if (! empty($error)) : ?>

        <div class="notice notice-error">
            <p>
                <strong>
                    <?php echo esc_html('Audit failed:'); ?>
                </strong>

                <?php echo esc_html($error); ?>
            </p>
        </div>

    <?php elseif (is_array($auditResult)) : ?>

        <?php
        $summary =
            isset($auditResult['summary'])
            && is_array($auditResult['summary'])
                ? $auditResult['summary']
                : [];

        $normalization =
            isset($auditResult['normalization'])
            && is_array($auditResult['normalization'])
                ? $auditResult['normalization']
                : [];

        $products =
            isset($auditResult['products'])
            && is_array($auditResult['products'])
                ? $auditResult['products']
                : [];

        $repairCandidates =
            isset($auditResult['repair_candidates'])
            && is_array($auditResult['repair_candidates'])
                ? $auditResult['repair_candidates']
                : [];

        $warnings =
            isset($auditResult['warnings'])
            && is_array($auditResult['warnings'])
                ? $auditResult['warnings']
                : [];

        $snapshotUuid =
            isset($auditResult['snapshot_uuid'])
                ? (string) $auditResult['snapshot_uuid']
                : $snapshotUuid;

        $readOnly =
            ! empty($auditResult['read_only']);

        $normalizationErrors =
            isset($normalization['error_count'])
                ? (int) $normalization['error_count']
                : 0;
        ?>

        <table class="widefat striped" style="max-width: 900px; margin-top: 20px;">

            <tbody>

                <tr>
                    <td style="width: 260px;">
                        <strong>
                            <?php echo esc_html('Snapshot UUID'); ?>
                        </strong>
                    </td>

                    <td>
                        <code>
                            <?php echo esc_html($snapshotUuid); ?>
                        </code>
                    </td>
                </tr>

                <tr>
                    <td>
                        <strong>
                            <?php echo esc_html('Audit mode'); ?>
                        </strong>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            $readOnly
                                ? 'Read-only'
                                : 'Unexpected write-capable result'
                        );
                        ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        <strong>
                            <?php echo esc_html('Normalized canonical products'); ?>
                        </strong>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                (int) (
                                    $normalization['normalized']
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
                            <?php echo esc_html('Normalization errors'); ?>
                        </strong>
                    </td>

                    <td>
                        <?php echo esc_html($normalizationErrors); ?>
                    </td>
                </tr>

            </tbody>

        </table>

        <h2 style="margin-top: 30px;">
            <?php echo esc_html('Image Health Summary'); ?>
        </h2>

        <table class="widefat striped" style="max-width: 900px;">

            <thead>

                <tr>
                    <th>
                        <?php echo esc_html('Metric'); ?>
                    </th>

                    <th>
                        <?php echo esc_html('Count'); ?>
                    </th>
                </tr>

            </thead>

            <tbody>

                <tr>
                    <td>
                        <?php echo esc_html('Canonical products'); ?>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                (int) (
                                    $summary['canonical_products']
                                    ?? 0
                                )
                            )
                        );
                        ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        <?php echo esc_html('Owned WooCommerce parents'); ?>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                (int) (
                                    $summary['owned_woocommerce_parents']
                                    ?? 0
                                )
                            )
                        );
                        ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        <?php echo esc_html('Canonical products with images'); ?>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                (int) (
                                    $summary['canonical_products_with_images']
                                    ?? 0
                                )
                            )
                        );
                        ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        <?php echo esc_html('Canonical products without images'); ?>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                (int) (
                                    $summary['canonical_products_without_images']
                                    ?? 0
                                )
                            )
                        );
                        ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        <?php echo esc_html('WooCommerce products with usable images'); ?>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                (int) (
                                    $summary['woocommerce_products_with_usable_images']
                                    ?? 0
                                )
                            )
                        );
                        ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        <?php echo esc_html('WooCommerce products missing images'); ?>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                (int) (
                                    $summary['woocommerce_products_missing_images']
                                    ?? 0
                                )
                            )
                        );
                        ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        <?php echo esc_html('WooCommerce products with broken images'); ?>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                (int) (
                                    $summary['woocommerce_products_with_broken_images']
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
                            <?php echo esc_html('Healthy'); ?>
                        </strong>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                (int) (
                                    $summary['healthy']
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
                            <?php echo esc_html('Repairable'); ?>
                        </strong>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                (int) (
                                    $summary['repairable']
                                    ?? 0
                                )
                            )
                        );
                        ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        <?php echo esc_html('Canonical colour-image only'); ?>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                (int) (
                                    $summary['colour_image_only']
                                    ?? 0
                                )
                            )
                        );
                        ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        <?php echo esc_html('Ambiguous'); ?>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                (int) (
                                    $summary['ambiguous']
                                    ?? 0
                                )
                            )
                        );
                        ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        <?php echo esc_html('Not adopted'); ?>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                (int) (
                                    $summary['not_adopted']
                                    ?? 0
                                )
                            )
                        );
                        ?>
                    </td>
                </tr>

            </tbody>

        </table>

        <h2 style="margin-top: 30px;">
            <?php echo esc_html('Deterministic Repair Candidates'); ?>
        </h2>

        <?php if (empty($repairCandidates)) : ?>

            <div class="notice notice-success inline">
                <p>
                    <?php
                    echo esc_html(
                        'No deterministic image repair candidates were identified.'
                    );
                    ?>
                </p>
            </div>

        <?php else : ?>

            <p>
                <?php
                echo esc_html(
                    sprintf(
                        '%d deterministic repair candidate(s) were identified. '
                        . 'No repair has been performed.',
                        count($repairCandidates)
                    )
                );
                ?>
            </p>

            <table class="widefat striped">

                <thead>

                    <tr>
                        <th>
                            <?php echo esc_html('Woo Product ID'); ?>
                        </th>

                        <th>
                            <?php echo esc_html('Canonical Code'); ?>
                        </th>

                        <th>
                            <?php echo esc_html('Status'); ?>
                        </th>

                        <th>
                            <?php echo esc_html('Canonical Images'); ?>
                        </th>

                        <th>
                            <?php echo esc_html('Repair Authority'); ?>
                        </th>

                        <th>
                            <?php echo esc_html('Repair Allowed'); ?>
                        </th>

                        <th>
                            <?php echo esc_html('Step 7 Validation'); ?>
                        </th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($repairCandidates as $candidate) : ?>

                        <?php
                        $canonicalImages =
                            isset($candidate['canonical_images'])
                            && is_array($candidate['canonical_images'])
                                ? $candidate['canonical_images']
                                : [];
                        ?>

                        <tr>

                            <td>
                                <?php
                                echo esc_html(
                                    (string) (
                                        $candidate['woo_product_id']
                                        ?? '—'
                                    )
                                );
                                ?>
                            </td>

                            <td>
                                <code>
                                    <?php
                                    echo esc_html(
                                        (string) (
                                            $candidate['canonical_code']
                                            ?? '—'
                                        )
                                    );
                                    ?>
                                </code>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    (string) (
                                        $candidate['status']
                                        ?? '—'
                                    )
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    number_format_i18n(
                                        count($canonicalImages)
                                    )
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    (string) (
                                        $candidate['repair_authority']
                                        ?? '—'
                                    )
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    ! empty(
                                        $candidate['repair_allowed']
                                    )
                                        ? 'Yes'
                                        : 'No'
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    ! empty(
                                        $candidate[
                                            'requires_step_7_validation'
                                        ]
                                    )
                                        ? 'Required'
                                        : 'Not required'
                                );
                                ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        <?php endif; ?>

        <h2 style="margin-top: 30px;">
            <?php echo esc_html('Audit Details'); ?>
        </h2>

        <?php if (empty($products)) : ?>

            <div class="notice notice-info inline">
                <p>
                    <?php
                    echo esc_html(
                        'The audit returned no product rows.'
                    );
                    ?>
                </p>
            </div>

        <?php else : ?>

            <p>
                <?php
                echo esc_html(
                    sprintf(
                        '%d product(s) were evaluated by the image health auditor.',
                        count($products)
                    )
                );
                ?>
            </p>

            <table class="widefat striped">

                <thead>

                    <tr>
                        <th>
                            <?php echo esc_html('Canonical Code'); ?>
                        </th>

                        <th>
                            <?php echo esc_html('Woo Product ID'); ?>
                        </th>

                        <th>
                            <?php echo esc_html('Canonical Images'); ?>
                        </th>

                        <th>
                            <?php echo esc_html('Colour Images'); ?>
                        </th>

                        <th>
                            <?php echo esc_html('Woo Image'); ?>
                        </th>

                        <th>
                            <?php echo esc_html('Status'); ?>
                        </th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($products as $product) : ?>

                        <tr>

                            <td>
                                <code>
                                    <?php
                                    echo esc_html(
                                        (string) (
                                            $product['canonical_code']
                                            ?? '—'
                                        )
                                    );
                                    ?>
                                </code>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    (string) (
                                        $product['woo_product_id']
                                        ?? '—'
                                    )
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    number_format_i18n(
                                        (int) (
                                            $product[
                                                'canonical_image_count'
                                            ]
                                            ?? 0
                                        )
                                    )
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    number_format_i18n(
                                        (int) (
                                            $product[
                                                'canonical_colour_image_count'
                                            ]
                                            ?? 0
                                        )
                                    )
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                $wooImage =
                                    $product['woo_image']
                                    ?? '';

                                if (
                                    is_scalar($wooImage)
                                    && trim((string) $wooImage) !== ''
                                ) {
                                    echo esc_html(
                                        (string) $wooImage
                                    );
                                } else {
                                    echo esc_html('—');
                                }
                                ?>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    (string) (
                                        $product['status']
                                        ?? '—'
                                    )
                                );
                                ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        <?php endif; ?>

        <?php if (! empty($warnings)) : ?>

            <h2 style="margin-top: 30px;">
                <?php echo esc_html('Audit Warnings'); ?>
            </h2>

            <div class="notice notice-warning inline">

                <ul>

                    <?php foreach ($warnings as $warning) : ?>

                        <li>
                            <?php
                            if (is_scalar($warning)) {
                                echo esc_html(
                                    (string) $warning
                                );
                            } elseif (is_array($warning)) {
                                echo esc_html(
                                    wp_json_encode(
                                        $warning
                                    )
                                );
                            } else {
                                echo esc_html(
                                    'Non-scalar warning returned.'
                                );
                            }
                            ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>

    <?php elseif (is_object($auditResult)) : ?>

        <div class="notice notice-warning inline">
            <p>
                <?php
                echo esc_html(
                    'The image auditor returned an object result. '
                    . 'The result contract should be inspected before rendering '
                    . 'its detailed fields.'
                );
                ?>
            </p>
        </div>

    <?php else : ?>

        <div class="notice notice-warning inline">
            <p>
                <?php
                echo esc_html(
                    'No image health audit result is available.'
                );
                ?>
            </p>
        </div>

    <?php endif; ?>

</div>
