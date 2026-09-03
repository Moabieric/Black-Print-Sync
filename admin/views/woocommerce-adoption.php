<?php

declare(strict_types=1);

defined('ABSPATH') || exit;


/*
|--------------------------------------------------------------------------
| Request State
|--------------------------------------------------------------------------
*/

$status = isset($_GET['bp_adoption'])
    ? sanitize_key(
        wp_unslash(
            $_GET['bp_adoption']
        )
    )
    : '';

$error = isset($_GET['errors'])
    ? sanitize_text_field(
        wp_unslash(
            $_GET['errors']
        )
    )
    : '';

$artifactId = isset($artifactId)
    ? (string) $artifactId
    : '';

$artifact = is_array($artifact ?? null)
    ? $artifact
    : null;

$artifactError = isset($artifactError)
    ? (string) $artifactError
    : '';


$ready = false;

$approvedMappingCount = 0;

$variantOwnershipCount = 0;

$snapshotUuid = '';

$mappingHash = '';

$expiresAt = '';


if ($artifact !== null) {

    $approvedMappingCount =
        (int) (
            $artifact['approved_mapping_count']
            ?? 0
        );

    $variantOwnershipCount =
        (int) (
            $artifact['explicit_variant_ownership_count']
            ?? 0
        );

    $snapshotUuid =
        (string) (
            $artifact['snapshot_uuid']
            ?? ''
        );

    $mappingHash =
        (string) (
            $artifact['mapping_hash']
            ?? ''
        );

    $expiresTimestamp =
        (int) (
            $artifact['expires_at']
            ?? 0
        );


    if ($expiresTimestamp > 0) {

        $expiresAt =
            wp_date(
                'Y-m-d H:i:s',
                $expiresTimestamp
            );
    }


    $ready =
        isset(
            $artifact['verification']['pass']
        )
        && $artifact['verification']['pass'] === true
        && isset(
            $artifact['ownership_dry_run']['pass']
        )
        && $artifact['ownership_dry_run']['pass'] === true
        && $approvedMappingCount === 3710
        && $variantOwnershipCount === 20265
        && $artifactId !== '';
}

?>

<div class="wrap">

    <h1>BlackPrint WooCommerce Adoption</h1>


    <p>
        Controlled Step 5B ownership hand-off for existing
        WooCommerce products and variations.
    </p>


    <p>
        This process adopts only the WooCommerce products and
        variations contained in the verified Step 3 adoption
        mapping artifact.
    </p>


    <p>
        <strong>
            No products are created by this step.
        </strong>
    </p>


    <p>
        <strong>
            No REVIEW or DO NOT ADOPT products are modified.
        </strong>
    </p>


    <?php if ($status === 'success') : ?>

        <div class="notice notice-success">

            <p>
                <strong>
                    Step 5B ownership commit completed successfully.
                </strong>
            </p>


            <p>
                The committed ownership records passed independent
                post-write verification.
            </p>

        </div>

    <?php elseif ($status === 'failed') : ?>

        <div class="notice notice-error">

            <p>
                <strong>
                    Step 5B ownership commit failed or did not pass
                    post-write verification.
                </strong>
            </p>


            <?php if ($error !== '') : ?>

                <p>
                    <?php
                    echo esc_html(
                        $error
                    );
                    ?>
                </p>

            <?php endif; ?>

        </div>

    <?php elseif ($status === 'exception') : ?>

        <div class="notice notice-error">

            <p>
                <strong>
                    The Step 5B ownership commit raised an exception.
                </strong>
            </p>


            <?php if ($error !== '') : ?>

                <p>
                    <?php
                    echo esc_html(
                        $error
                    );
                    ?>
                </p>

            <?php endif; ?>

        </div>

    <?php endif; ?>


    <hr>


    <h2>Verified Adoption Artifact</h2>


    <?php if ($artifactError !== '') : ?>

        <div class="notice notice-error">

            <p>
                <strong>
                    Step 5B is not ready.
                </strong>
            </p>


            <p>
                <?php
                echo esc_html(
                    $artifactError
                );
                ?>
            </p>

        </div>

    <?php elseif ($artifact !== null) : ?>

        <table class="widefat striped" style="max-width: 1000px;">

            <tbody>

                <tr>

                    <td>
                        <strong>Artifact ID</strong>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            $artifactId
                        );
                        ?>
                    </td>

                </tr>


                <tr>

                    <td>
                        <strong>Snapshot UUID</strong>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            $snapshotUuid
                        );
                        ?>
                    </td>

                </tr>


                <tr>

                    <td>
                        <strong>Approved mappings</strong>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                $approvedMappingCount
                            )
                        );
                        ?>

                        / 3,710
                    </td>

                </tr>


                <tr>

                    <td>
                        <strong>Explicit variant ownership mappings</strong>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                $variantOwnershipCount
                            )
                        );
                        ?>

                        / 20,265
                    </td>

                </tr>


                <tr>

                    <td>
                        <strong>Mapping verification</strong>
                    </td>

                    <td>
                        PASS
                    </td>

                </tr>


                <tr>

                    <td>
                        <strong>Ownership dry-run</strong>
                    </td>

                    <td>
                        PASS
                    </td>

                </tr>


                <tr>

                    <td>
                        <strong>Mapping hash</strong>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            $mappingHash
                        );
                        ?>
                    </td>

                </tr>


                <?php if ($expiresAt !== '') : ?>

                    <tr>

                        <td>
                            <strong>Artifact expires</strong>
                        </td>

                        <td>
                            <?php
                            echo esc_html(
                                $expiresAt
                            );
                            ?>
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>


        <?php if ($ready) : ?>

            <div class="notice notice-warning inline">

                <p>
                    <strong>
                        READY FOR STEP 5B
                    </strong>
                </p>

                <p>
                    Submitting the button below will write BlackPrint
                    ownership metadata to the approved existing
                    WooCommerce products and their explicitly mapped
                    variations.
                </p>

                <p>
                    Existing conflicting BlackPrint ownership will
                    cause the commit to stop before writes are performed.
                </p>

            </div>


            <h2>Commit WooCommerce Ownership</h2>


            <form
                method="post"
                action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
            >

                <input
                    type="hidden"
                    name="action"
                    value="bp_commit_woocommerce_ownership"
                >


                <input
                    type="hidden"
                    name="artifact_id"
                    value="<?php echo esc_attr($artifactId); ?>"
                >


                <?php

                wp_nonce_field(
                    'bp_commit_woocommerce_ownership'
                );

                submit_button(
                    'Commit Step 5B WooCommerce Ownership',
                    'primary',
                    'submit',
                    true,
                    [
                        'onclick' =>
                            "return confirm('This will commit BlackPrint ownership metadata to the 3,710 approved WooCommerce products and 20,265 explicitly mapped variations. Continue?');",
                    ]
                );

                ?>

            </form>


        <?php else : ?>

            <div class="notice notice-error inline">

                <p>
                    <strong>
                        Step 5B is not ready for commitment.
                    </strong>
                </p>

                <p>
                    The verified artifact does not satisfy all
                    required Step 5B safety conditions.
                </p>

            </div>

        <?php endif; ?>


    <?php endif; ?>


</div>