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