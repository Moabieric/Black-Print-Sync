<?php

defined('ABSPATH') || exit;

/**
 * Amrod Products Explorer.
 *
 * Read-only interface for testing and inspecting
 * product data returned by the Amrod Vendor API.
 *
 * No WooCommerce products are created or modified.
 *
 * @package BlackPrint\Commerce
 */

?>

<div class="wrap">

    <h1>Amrod Products</h1>

    <p>
        Read-only access to product data returned by the
        Amrod Vendor API.
    </p>

    <p>
        <strong>Important:</strong>
        This explorer does not create, update, or modify
        WooCommerce products.
    </p>


    <?php if (!empty($error)) : ?>

        <div class="notice notice-error">
            <p>
                <strong>Amrod API Error:</strong>
                <?php echo esc_html($error); ?>
            </p>
        </div>

    <?php endif; ?>


    <hr>


    <h2>Product API Tests</h2>

    <p>
        Select an endpoint below to perform a read-only
        request against the Amrod Vendor API.
    </p>


    <div style="display:flex; gap:10px; flex-wrap:wrap; margin:20px 0;">

        <?php

        $actions = [
            'products' => [
                'label' => 'Get All Products',
            ],
            'updated_products' => [
                'label' => 'Get Updated Products',
            ],
            'products_with_branding' => [
                'label' => 'Get Products + Branding',
            ],
            'updated_products_with_branding' => [
                'label' => 'Get Updated Products + Branding',
            ],
        ];

        foreach ($actions as $action_key => $action_data) :

            $url = wp_nonce_url(
                add_query_arg(
                    [
                        'page' => 'blackprint-amrod-products',
                        'bp_amrod_products_test' => '1',
                        'bp_amrod_product_action' => $action_key,
                    ],
                    admin_url('admin.php')
                ),
                'bp_amrod_products_test'
            );

            ?>

            <a
                href="<?php echo esc_url($url); ?>"
                class="button button-primary"
            >
                <?php echo esc_html($action_data['label']); ?>
            </a>

        <?php endforeach; ?>

    </div>


    <?php if (!empty($result)) : ?>

        <hr>

        <h2>API Response</h2>

        <p>
            The Amrod API returned a response successfully.
        </p>

        <textarea
            readonly
            style="
                width:100%;
                min-height:500px;
                font-family:monospace;
                font-size:13px;
            "
        ><?php

        echo esc_textarea(
            wp_json_encode(
                $result,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );

        ?></textarea>

    <?php endif; ?>

</div>