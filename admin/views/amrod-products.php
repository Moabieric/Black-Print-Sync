<?php

defined('ABSPATH') || exit;

/**
 * Amrod Products & Stock Explorer.
 *
 * Read-only diagnostic interface for inspecting raw
 * product and stock data returned by the Amrod Vendor API.
 *
 * IMPORTANT:
 * This view does not create or modify WooCommerce products.
 *
 * Available data:
 * - Full products
 * - Updated products
 * - Products with branding
 * - Updated products with branding
 * - Full stock
 * - Updated stock
 *
 * @package BlackPrint\Commerce
 */

?>

<div class="wrap">

    <h1>Amrod Products & Stock</h1>

    <p>
        Read-only access to raw product and stock data
        returned by the Amrod Vendor API.
    </p>


    <?php if (!empty($error)) : ?>

        <div class="notice notice-error is-dismissible">

            <p>
                <strong>Amrod API Error:</strong>
                <?php echo esc_html($error); ?>
            </p>

        </div>

    <?php endif; ?>


    <div class="card" style="max-width: 900px;">

        <h2>Product Data</h2>

        <p>
            Test the Amrod product endpoints below.
            These actions only retrieve data and do not modify WooCommerce.
        </p>


        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px;">

            <?php
            $products_url = wp_nonce_url(
                admin_url(
                    'admin.php?page=blackprint-amrod-products'
                    . '&bp_amrod_products_test=1'
                    . '&bp_amrod_product_action=products'
                ),
                'bp_amrod_products_test'
            );
            ?>

            <a
                href="<?php echo esc_url($products_url); ?>"
                class="button button-primary"
            >
                Get All Products
            </a>


            <?php
            $updated_products_url = wp_nonce_url(
                admin_url(
                    'admin.php?page=blackprint-amrod-products'
                    . '&bp_amrod_products_test=1'
                    . '&bp_amrod_product_action=updated_products'
                ),
                'bp_amrod_products_test'
            );
            ?>

            <a
                href="<?php echo esc_url($updated_products_url); ?>"
                class="button"
            >
                Get Updated Products
            </a>


            <?php
            $products_branding_url = wp_nonce_url(
                admin_url(
                    'admin.php?page=blackprint-amrod-products'
                    . '&bp_amrod_products_test=1'
                    . '&bp_amrod_product_action=products_with_branding'
                ),
                'bp_amrod_products_test'
            );
            ?>

            <a
                href="<?php echo esc_url($products_branding_url); ?>"
                class="button"
            >
                Get Products + Branding
            </a>


            <?php
            $updated_products_branding_url = wp_nonce_url(
                admin_url(
                    'admin.php?page=blackprint-amrod-products'
                    . '&bp_amrod_products_test=1'
                    . '&bp_amrod_product_action=updated_products_with_branding'
                ),
                'bp_amrod_products_test'
            );
            ?>

            <a
                href="<?php echo esc_url($updated_products_branding_url); ?>"
                class="button"
            >
                Get Updated Products + Branding
            </a>

        </div>

    </div>


    <div class="card" style="max-width: 900px; margin-top: 20px;">

        <h2>Stock Data</h2>

        <p>
            Test the Amrod stock endpoints below.
            These actions only retrieve stock data and do not modify WooCommerce.
        </p>


        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px;">

            <?php
            $stock_url = wp_nonce_url(
                admin_url(
                    'admin.php?page=blackprint-amrod-products'
                    . '&bp_amrod_products_test=1'
                    . '&bp_amrod_product_action=stock'
                ),
                'bp_amrod_products_test'
            );
            ?>

            <a
                href="<?php echo esc_url($stock_url); ?>"
                class="button button-primary"
            >
                Get All Stock
            </a>


            <?php
            $updated_stock_url = wp_nonce_url(
                admin_url(
                    'admin.php?page=blackprint-amrod-products'
                    . '&bp_amrod_products_test=1'
                    . '&bp_amrod_product_action=updated_stock'
                ),
                'bp_amrod_products_test'
            );
            ?>

            <a
                href="<?php echo esc_url($updated_stock_url); ?>"
                class="button"
            >
                Get Updated Stock
            </a>

        </div>

    </div>


    <?php if (!empty($result)) : ?>

        <div class="card" style="max-width: 1200px; margin-top: 20px;">

            <h2>API Response</h2>

            <p>
                The following data was returned directly from the
                Amrod Vendor API.
            </p>


            <textarea
                readonly
                style="
                    width: 100%;
                    min-height: 500px;
                    font-family: monospace;
                    font-size: 13px;
                    line-height: 1.5;
                "
            ><?php

            echo esc_textarea(
                wp_json_encode(
                    $result,
                    JSON_PRETTY_PRINT |
                    JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE
                )
            );

            ?></textarea>

        </div>

    <?php endif; ?>

</div>