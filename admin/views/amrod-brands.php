<?php

namespace BlackPrint\Commerce;

defined('ABSPATH') || exit;

/**
 * Amrod Brands Administration View.
 *
 * Displays the brands currently returned by the
 * Amrod Vendor API.
 *
 * This view is read-only.
 */

?>

<div class="wrap">

    <h1>
        Amrod Brands
    </h1>


    <p>
        Read-only brand data retrieved from the Amrod Vendor API.
        These records are currently displayed for inspection only.
        No WooCommerce brands or products are created or modified.
    </p>


    <hr>


    <?php if ($error !== '') : ?>

        <div class="notice notice-error">

            <p>

                <strong>
                    Amrod Brands Request Failed
                </strong>

            </p>

            <p>
                <?php echo esc_html($error); ?>
            </p>

        </div>

    <?php endif; ?>


    <div
        style="
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1100px;
            margin: 20px 0;
        "
    >

        <div>

            <h2 style="margin: 0;">
                Brand Catalogue
            </h2>

            <p>
                <?php echo esc_html(
                    count($brands)
                ); ?>
                brand record(s) available.
            </p>

        </div>


        <div>

            <a
                href="<?php echo esc_url(
                    wp_nonce_url(
                        admin_url(
                            'admin.php?page=blackprint-amrod-brands&bp_amrod_refresh=1'
                        ),
                        'bp_amrod_refresh_brands'
                    )
                ); ?>"
                class="button button-primary"
            >
                Refresh Brands
            </a>

        </div>

    </div>


    <?php if (! empty($brands)) : ?>

        <table
            class="widefat striped"
            style="max-width: 1100px;"
        >

            <thead>

                <tr>

                    <th style="width: 80px;">
                        #
                    </th>

                    <th>
                        Brand
                    </th>

                    <th style="width: 120px;">
                        Code
                    </th>

                    <th style="width: 120px;">
                        Sort Order
                    </th>

                    <th style="width: 220px;">
                        Image
                    </th>

                </tr>

            </thead>


            <tbody>

                <?php foreach (
                    $brands as $index => $brand
                ) : ?>

                    <tr>

                        <td>
                            <?php echo esc_html(
                                $index + 1
                            ); ?>
                        </td>


                        <td>

                            <strong>
                                <?php echo esc_html(
                                    $brand['name']
                                ); ?>
                            </strong>

                        </td>


                        <td>

                            <code>
                                <?php echo esc_html(
                                    $brand['code']
                                ); ?>
                            </code>

                        </td>


                        <td>

                            <?php echo esc_html(
                                $brand['order']
                            ); ?>

                        </td>


                        <td>

                            <?php if (
                                ! empty($brand['image'])
                            ) : ?>

                                <img
                                    src="<?php echo esc_url(
                                        $brand['image']
                                    ); ?>"
                                    alt="<?php echo esc_attr(
                                        $brand['name']
                                    ); ?>"
                                    style="
                                        max-width: 167px;
                                        max-height: 50px;
                                        object-fit: contain;
                                    "
                                >

                            <?php else : ?>

                                <span>
                                    No image
                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    <?php else : ?>

        <div class="notice notice-warning inline">

            <p>
                No Amrod brands are currently available.
            </p>

        </div>

    <?php endif; ?>


    <br>


    <div class="notice notice-info inline">

        <p>

            <strong>
                Read-Only Mode:
            </strong>

            Brand records are retrieved directly from the
            Amrod Vendor API.

            This module does not create, update, delete,
            or synchronise WooCommerce brands, products,
            categories, media, or other commerce data.

        </p>

    </div>

</div>