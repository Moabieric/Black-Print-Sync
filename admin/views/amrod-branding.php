<?php

defined('ABSPATH') || exit;

?>

<div class="wrap">

    <h1>Amrod Branding Explorer</h1>

    <p>
        Read-only explorer for Amrod Branding API endpoints.
        No WooCommerce data is created or modified.
    </p>

    <form method="get">

        <input
            type="hidden"
            name="page"
            value="blackprint-amrod-branding"
        />

        <table class="form-table">

            <tr>

                <th scope="row">
                    Endpoint
                </th>

                <td>

                    <select
                        name="bp_amrod_branding_action"
                    >

                        <option value="branding_departments">
                            Branding Departments
                        </option>

                        <option value="updated_branding_departments">
                            Updated Branding Departments
                        </option>

                        <option value="inclusive_branding">
                            Inclusive Branding
                        </option>

                        <option value="updated_inclusive_branding">
                            Updated Inclusive Branding
                        </option>

                    </select>

                </td>

            </tr>

        </table>

        <?php

        wp_nonce_field(
            'bp_amrod_branding_test'
        );

        ?>

        <p>

            <input
                type="submit"
                class="button button-primary"
                name="bp_amrod_branding_test"
                value="Run API Test"
            />

        </p>

    </form>

    <?php if (! empty($error)) : ?>

        <div class="notice notice-error">

            <p>

                <strong>Error:</strong>

                <?php echo esc_html($error); ?>

            </p>

        </div>

    <?php endif; ?>


    <?php if (! empty($result)) : ?>

        <h2>API Response</h2>

        <textarea
            readonly
            style="
                width:100%;
                height:650px;
                font-family:monospace;
            "
        ><?php echo esc_textarea(
            wp_json_encode(
                $result,
                JSON_PRETTY_PRINT
            )
        ); ?></textarea>

    <?php endif; ?>

</div>