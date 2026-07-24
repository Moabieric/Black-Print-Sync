<?php

namespace BlackPrint\Commerce;

defined('ABSPATH') || exit;

/**
 * Amrod Connector Administration View.
 *
 * Displays the Amrod connector status, configuration,
 * credential status, and optional live health check.
 *
 * This view does not expose secret credential values.
 *
 * @package BlackPrint\Commerce
 */

$connector = new \BlackPrint\Commerce\Suppliers\Amrod\Amrod_Connector();

$config = $connector->get_config();

$status = $connector->get_status();


/*
|--------------------------------------------------------------------------
| Credential Status
|--------------------------------------------------------------------------
*/

$has_username = defined('BP_AMROD_USERNAME')
    && BP_AMROD_USERNAME !== '';

$has_password = defined('BP_AMROD_PASSWORD')
    && BP_AMROD_PASSWORD !== '';

$has_customer_code = defined('BP_AMROD_CUSTOMER_CODE')
    && BP_AMROD_CUSTOMER_CODE !== '';


/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
|
| The health check only runs when the admin submits the button.
|
*/

$health_result = null;

if (
    isset($_POST['bp_amrod_run_health_check'])
) {
    check_admin_referer(
        'bp_amrod_health_check',
        'bp_amrod_health_check_nonce'
    );


    $health_check =
        new \BlackPrint\Commerce\Suppliers\Amrod\Amrod_Health_Check(
            $connector
        );


    $health_result =
        $health_check->run();
}

?>

<div class="wrap">

    <h1>
        Amrod Connector
    </h1>


    <p>
        Native BlackPrint OS supplier connector for Amrod.
        The connector currently operates in read-only mode
        and does not write products or other commerce data
        to WooCommerce.
    </p>


    <hr>


    <h2>
        Connector Status
    </h2>


    <table class="widefat striped" style="max-width: 800px;">

        <tbody>

            <tr>
                <td>
                    <strong>Supplier</strong>
                </td>

                <td>
                    <?php echo esc_html(
                        $status['supplier_name']
                    ); ?>
                </td>
            </tr>


            <tr>
                <td>
                    <strong>Supplier ID</strong>
                </td>

                <td>
                    <?php echo esc_html(
                        $status['supplier_id']
                    ); ?>
                </td>
            </tr>


            <tr>
                <td>
                    <strong>Connector</strong>
                </td>

                <td>
                    <?php echo esc_html(
                        $status['connector']
                    ); ?>
                </td>
            </tr>


            <tr>
                <td>
                    <strong>Version</strong>
                </td>

                <td>
                    <?php echo esc_html(
                        $status['version']
                    ); ?>
                </td>
            </tr>


            <tr>
                <td>
                    <strong>Status</strong>
                </td>

                <td>

                    <span style="color: #008a00; font-weight: 600;">
                        Loaded
                    </span>

                </td>
            </tr>

        </tbody>

    </table>


    <br>


    <h2>
        Amrod API Configuration
    </h2>


    <table class="widefat striped" style="max-width: 800px;">

        <tbody>

            <tr>
                <td>
                    <strong>Authentication URL</strong>
                </td>

                <td>
                    <code>
                        <?php echo esc_html(
                            $config->get_auth_url()
                        ); ?>
                    </code>
                </td>
            </tr>


            <tr>
                <td>
                    <strong>Vendor API URL</strong>
                </td>

                <td>
                    <code>
                        <?php echo esc_html(
                            $config->get_vendor_api_url()
                        ); ?>
                    </code>
                </td>
            </tr>

        </tbody>

    </table>


    <br>


    <h2>
        Credential Status
    </h2>


    <table class="widefat striped" style="max-width: 800px;">

        <tbody>

            <tr>
                <td>
                    <strong>Username</strong>
                </td>

                <td>

                    <?php if ($has_username) : ?>

                        <span style="color: #008a00; font-weight: 600;">
                            Configured
                        </span>

                    <?php else : ?>

                        <span style="color: #b32d2e; font-weight: 600;">
                            Not Configured
                        </span>

                    <?php endif; ?>

                </td>
            </tr>


            <tr>
                <td>
                    <strong>Password</strong>
                </td>

                <td>

                    <?php if ($has_password) : ?>

                        <span style="color: #008a00; font-weight: 600;">
                            Configured
                        </span>

                    <?php else : ?>

                        <span style="color: #b32d2e; font-weight: 600;">
                            Not Configured
                        </span>

                    <?php endif; ?>

                </td>
            </tr>


            <tr>
                <td>
                    <strong>Customer Code</strong>
                </td>

                <td>

                    <?php if ($has_customer_code) : ?>

                        <span style="color: #008a00; font-weight: 600;">
                            Configured
                        </span>

                    <?php else : ?>

                        <span style="color: #b32d2e; font-weight: 600;">
                            Not Configured
                        </span>

                    <?php endif; ?>

                </td>
            </tr>

        </tbody>

    </table>


    <br>


    <h2>
        Amrod Connection Health
    </h2>


    <p>
        Run a live, read-only connection test against the Amrod
        Vendor API. This test authenticates with Amrod if required,
        obtains or reuses the cached Bearer token, and requests
        the Brands endpoint.
    </p>


    <form method="post">

        <?php
        wp_nonce_field(
            'bp_amrod_health_check',
            'bp_amrod_health_check_nonce'
        );
        ?>

        <input
            type="hidden"
            name="bp_amrod_run_health_check"
            value="1"
        >


        <?php
        submit_button(
            'Run Amrod Health Check',
            'primary',
            'submit',
            false
        );
        ?>

    </form>


    <?php if (is_array($health_result)) : ?>

        <br>


        <?php if (
            isset($health_result['status'])
            && $health_result['status'] === 'healthy'
        ) : ?>

            <div class="notice notice-success inline">

                <p>

                    <strong>
                        Amrod Connector Healthy
                    </strong>

                </p>

            </div>

        <?php else : ?>

            <div class="notice notice-error inline">

                <p>

                    <strong>
                        Amrod Connector Health Check Failed
                    </strong>

                </p>

            </div>

        <?php endif; ?>


        <table class="widefat striped" style="max-width: 800px;">

            <tbody>

                <tr>
                    <td>
                        <strong>Overall Status</strong>
                    </td>

                    <td>

                        <?php echo esc_html(
                            ucfirst(
                                $health_result['status']
                            )
                        ); ?>

                    </td>
                </tr>


                <tr>
                    <td>
                        <strong>Connector</strong>
                    </td>

                    <td>

                        <?php echo esc_html(
                            $health_result['checks']['connector']['message']
                        ); ?>

                    </td>
                </tr>


                <tr>
                    <td>
                        <strong>Authentication</strong>
                    </td>

                    <td>

                        <?php echo esc_html(
                            $health_result['checks']['authentication']['message']
                        ); ?>

                    </td>
                </tr>


                <tr>
                    <td>
                        <strong>API Endpoint</strong>
                    </td>

                    <td>

                        <code>
                            <?php echo esc_html(
                                $health_result['endpoint']
                            ); ?>
                        </code>

                    </td>
                </tr>


                <tr>
                    <td>
                        <strong>Vendor API</strong>
                    </td>

                    <td>

                        <?php echo esc_html(
                            $health_result['checks']['api']['message']
                        ); ?>

                    </td>
                </tr>

            </tbody>

        </table>

    <?php endif; ?>


    <br>


    <div class="notice notice-info inline">

        <p>

            <strong>Read-Only Connector Mode:</strong>

            The Amrod Connector currently retrieves data
            from the Amrod API only.

            No WooCommerce products, categories, stock,
            pricing, images, branding data, or other store
            data are created or modified by this connector.

        </p>

    </div>

</div>