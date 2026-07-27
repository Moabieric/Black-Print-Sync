<?php

defined('ABSPATH') || exit;

/**
 * Amrod Prices Explorer.
 *
 * Read-only interface for inspecting raw price data
 * returned by the Amrod Vendor API.
 *
 * No WooCommerce data is created or modified.
 *
 * @package BlackPrint\Commerce
 */

?>

<div class="wrap">

    <h1>Amrod Prices</h1>

    <p>
        Read-only access to raw price data returned by the
        Amrod Vendor API.
    </p>

    <hr>

    <h2>Price API Explorer</h2>

    <p>
        Use the controls below to retrieve raw supplier pricing
        directly from Amrod.
    </p>

    <?php if (! empty($error)) : ?>

        <div class="notice notice-error">
            <p>
                <strong>Amrod Price Error:</strong>
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
                    <code>/api/v1/Prices/</code>
                </td>

                <td>
                    Retrieve the full Amrod price catalogue.
                </td>

                <td>

                    <a
                        href="<?php
                        echo esc_url(
                            wp_nonce_url(
                                add_query_arg(
                                    [
                                        'page' =>
                                            'blackprint-amrod-prices',

                                        'bp_amrod_prices_test' =>
                                            '1',

                                        'bp_amrod_prices_action' =>
                                            'prices',
                                    ],
                                    admin_url('admin.php')
                                ),
                                'bp_amrod_prices_test'
                            )
                        );
                        ?>"
                        class="button button-primary"
                    >
                        Load Full Prices
                    </a>

                </td>

            </tr>

            <tr>

                <td>
                    <code>/api/v1/Prices/GetUpdated</code>
                </td>

                <td>
                    Retrieve updated Amrod price data.
                </td>

                <td>

                    <a
                        href="<?php
                        echo esc_url(
                            wp_nonce_url(
                                add_query_arg(
                                    [
                                        'page' =>
                                            'blackprint-amrod-prices',

                                        'bp_amrod_prices_test' =>
                                            '1',

                                        'bp_amrod_prices_action' =>
                                            'updated_prices',
                                    ],
                                    admin_url('admin.php')
                                ),
                                'bp_amrod_prices_test'
                            )
                        );
                        ?>"
                        class="button"
                    >
                        Load Updated Prices
                    </a>

                </td>

            </tr>

        </tbody>

    </table>

    <?php if (! empty($result)) : ?>

        <hr>

        <h2>Raw Amrod Price Response</h2>

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