<?php

namespace BlackPrint\Commerce;

defined('ABSPATH') || exit;

/**
 * Amrod Categories Administration View.
 *
 * Displays read-only category data retrieved
 * from the Amrod Vendor API.
 *
 * @package BlackPrint\Commerce
 */

if (
    ! isset($categories)
    || ! is_array($categories)
) {
    $categories = [];
}

if (
    ! isset($error)
) {
    $error = '';
}

?>

<div class="wrap">

    <h1>
        Amrod Categories
    </h1>

    <p>
        Read-only category data retrieved from the
        Amrod Vendor API.
    </p>

    <?php
    $category_sync_status = isset($_GET['bp_category_sync'])
        ? sanitize_key(wp_unslash($_GET['bp_category_sync']))
        : '';

    $category_sync_error = isset($_GET['bp_category_sync_error'])
        ? sanitize_text_field(
            wp_unslash($_GET['bp_category_sync_error'])
        )
        : '';

    $category_sync_fields = [
        'job_uuid' => 'Sync job UUID',
        'snapshot_uuid' => 'Snapshot UUID',
        'root_category_count' => 'Root categories',
        'total_category_count' => 'Total categories, including descendants',
        'maximum_depth' => 'Maximum tree depth',
    ];

    $category_sync_values = [];

    foreach ($category_sync_fields as $key => $label) {
        $query_key = 'bp_category_' . $key;

        if (isset($_GET[$query_key])) {
            $category_sync_values[$key] = sanitize_text_field(
                wp_unslash($_GET[$query_key])
            );
        }
    }
    ?>

    <?php if ($category_sync_status === 'success') : ?>

        <div class="notice notice-success inline">
            <p><strong>Amrod category snapshot captured successfully.</strong></p>

            <p>
                The validated supplier category tree was saved through
                the BlackPrint snapshot pipeline. WooCommerce categories
                and products were not modified.
            </p>

            <ul>
                <?php foreach ($category_sync_fields as $key => $label) : ?>
                    <?php if (isset($category_sync_values[$key])) : ?>
                        <li>
                            <strong><?php echo esc_html($label); ?>:</strong>
                            <code><?php echo esc_html($category_sync_values[$key]); ?></code>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>

    <?php elseif (
        $category_sync_status === 'failed'
        || $category_sync_status === 'exception'
    ) : ?>

        <div class="notice notice-error inline">
            <p>
                <strong>Amrod category snapshot failed.</strong>
                No successful snapshot should be assumed.
            </p>

            <?php if ($category_sync_error !== '') : ?>
                <p><?php echo esc_html($category_sync_error); ?></p>
            <?php endif; ?>
        </div>

    <?php endif; ?>

    <form
        method="post"
        action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
        style="margin: 16px 0;"
    >
        <input
            type="hidden"
            name="action"
            value="bp_run_amrod_category_ingestion"
        >

        <?php
        wp_nonce_field(
            'bp_run_amrod_category_ingestion'
        );
        ?>

        <button type="submit" class="button button-primary">
            Capture Category Snapshot
        </button>

        <p class="description">
            Fetches the Amrod category tree and stores an immutable raw
            snapshot. This does not create, assign, rename, or delete
            WooCommerce categories or products.
        </p>
    </form>



    <?php if ($error !== '') : ?>

        <div class="notice notice-error">

            <p>
                <strong>
                    Amrod Categories Error:
                </strong>

                <?php echo esc_html($error); ?>
            </p>

        </div>

    <?php endif; ?>


    <p>

        <a
            href="<?php echo esc_url(
                wp_nonce_url(
                    admin_url(
                        'admin.php?page=blackprint-amrod-categories&bp_amrod_refresh=1'
                    ),
                    'bp_amrod_refresh_categories'
                )
            ); ?>"
            class="button button-primary"
        >
            Refresh Categories
        </a>

    </p>


    <?php if (! empty($categories)) : ?>

        <div class="notice notice-success inline">

            <p>

                <strong>
                    <?php echo esc_html(
                        count($categories)
                    ); ?>
                </strong>

                category record(s) returned by the
                Amrod Vendor API.

            </p>

        </div>


        <table class="widefat striped">

            <thead>

                <tr>

                    <th>
                        #
                    </th>

                    <th>
                        Category Data
                    </th>

                </tr>

            </thead>


            <tbody>

                <?php foreach (
                    $categories as $index => $category
                ) : ?>

                    <tr>

                        <td>
                            <?php echo esc_html(
                                $index + 1
                            ); ?>
                        </td>

                        <td>

                            <pre style="
                                white-space: pre-wrap;
                                word-wrap: break-word;
                                margin: 0;
                            "><?php echo esc_html(
                                wp_json_encode(
                                    $category,
                                    JSON_PRETTY_PRINT
                                    | JSON_UNESCAPED_SLASHES
                                )
                            ); ?></pre>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>


    <?php elseif ($error === '') : ?>

        <div class="notice notice-warning inline">

            <p>
                No category records were returned by
                the Amrod Vendor API.
            </p>

        </div>

    <?php endif; ?>


    <br>


    <div class="notice notice-info inline">

        <p>

            <strong>
                Read-Only Supplier Data:
            </strong>

            These categories are displayed directly from
            the Amrod API.

            BlackPrint OS does not currently create,
            modify, or assign WooCommerce categories.

        </p>

    </div>

</div>