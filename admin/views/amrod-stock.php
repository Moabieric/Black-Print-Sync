<?php

defined('ABSPATH') || exit;

/**
 * Amrod Stock Explorer.
 *
 * Read-only interface for inspecting raw stock data
 * returned by the Amrod Vendor API.
 *
 * No WooCommerce data is created or modified.
 *
 * @package BlackPrint\Commerce
 */

?>

<div class="wrap">

    <h1>Amrod Stock</h1>

    <p>
        Read-only access to stock data returned by the
        Amrod Vendor API.
    </p>


    <hr>


    <h2>Stock API Explorer</h2>

    <p>
        Use the controls below to retrieve raw stock data
        directly from Amrod.
    </p>


    <?php if (! empty($error)) : ?>

        <div class="notice notice-error">
            <p>
                <strong>Amrod Stock Error:</strong>
                <?php echo esc_html($error); ?>
            </p>
        </div>

    <?php endif; ?>


    <table class="widefat striped" style="max-width: 900px;">

        <thead>

            <tr>

                <th>
                    Endpoint
                </th>

                <th>
                    Description
                </th>

                <th>
                    Action
                </th>

            </tr>

        </thead>


        <tbody>

            <tr>

                <td>
                    <code>/api/v1/Stock/</code>
                </td>

                <td>
                    Retrieve the full Amrod stock catalogue.
                </td>

                <td>

                    <a
                        href="<?php
                        echo esc_url(
                            wp_nonce_url(
                                add_query_arg(
                                    [
                                        'page' =>
                                            'blackprint-amrod-stock',

                                        'bp_amrod_stock_test' =>
                                            '1',

                                        'bp_amrod_stock_action' =>
                                            'stock',
                                    ],
                                    admin_url('admin.php')
                                ),
                                'bp_amrod_stock_test'
                            )
                        );
                        ?>"
                        class="button button-primary"
                    >
                        Load Full Stock
                    </a>

                </td>

            </tr>


            <tr>

                <td>
                    <code>/api/v1/Stock/GetUpdated</code>
                </td>

                <td>
                    Retrieve updated Amrod stock data.
                </td>

                <td>

                    <a
                        href="<?php
                        echo esc_url(
                            wp_nonce_url(
                                add_query_arg(
                                    [
                                        'page' =>
                                            'blackprint-amrod-stock',

                                        'bp_amrod_stock_test' =>
                                            '1',

                                        'bp_amrod_stock_action' =>
                                            'updated_stock',
                                    ],
                                    admin_url('admin.php')
                                ),
                                'bp_amrod_stock_test'
                            )
                        );
                        ?>"
                        class="button"
                    >
                        Load Updated Stock
                    </a>

                </td>

            </tr>

        </tbody>

    </table>


    <?php if (! empty($result)) : ?>

        <hr>

        <h2>Raw Amrod Stock Response</h2>

        <p>
            The following data was returned directly from
            the Amrod Vendor API.
        </p>


        <div
            style="
                background: #fff;
                border: 1px solid #ccd0d4;
                padding: 20px;
                max-width: 1200px;
                overflow: auto;
            "
        >

            <pre style="white-space: pre-wrap; word-break: break-word;"><?php
                echo esc_html(
                    wp_json_encode(
                        $result,
                        JSON_PRETTY_PRINT
                        | JSON_UNESCAPED_SLASHES
                        | JSON_UNESCAPED_UNICODE
                    )
                );
            ?></pre>

        </div>

    <?php endif; ?>

</div>