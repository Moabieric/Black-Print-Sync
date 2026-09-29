<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

/*
|--------------------------------------------------------------------------
| Post-Ownership Verification
|--------------------------------------------------------------------------
|
| Independent, read-only audit of the ownership committed by Step 5B.
|
| This view:
|
| - Displays the verifier result.
| - Displays authoritative artifact metadata.
| - Displays expected versus verified ownership counts.
| - Displays verification errors.
| - Displays missing ownership records.
| - Displays ownership mismatches.
|
| This view performs NO writes.
|
*/

/*
|--------------------------------------------------------------------------
| Request State
|--------------------------------------------------------------------------
*/

$result =
    is_array($result ?? null)
        ? $result
        : null;

$error =
    isset($error)
        ? (string) $error
        : '';

$pass =
    $result !== null
    && ($result['pass'] ?? false) === true;

$status =
    $result !== null
        ? (string) (
            $result['status']
            ?? 'UNKNOWN'
        )
        : 'ERROR';

$message =
    $result !== null
        ? (string) (
            $result['message']
            ?? ''
        )
        : '';

$phase =
    $result !== null
        ? (string) (
            $result['phase']
            ?? 'POST_OWNERSHIP_VERIFICATION'
        )
        : 'POST_OWNERSHIP_VERIFICATION';

$artifact =
    is_array($result['artifact'] ?? null)
        ? $result['artifact']
        : [];

$expected =
    is_array($result['expected'] ?? null)
        ? $result['expected']
        : [];

$verified =
    is_array($result['verified'] ?? null)
        ? $result['verified']
        : [];

$audit =
    is_array($result['audit'] ?? null)
        ? $result['audit']
        : [];

$missing =
    is_array($result['missing'] ?? null)
        ? $result['missing']
        : [];

$mismatches =
    is_array($result['mismatches'] ?? null)
        ? $result['mismatches']
        : [];

$errors =
    is_array($result['errors'] ?? null)
        ? $result['errors']
        : [];

$missingParents =
    is_array($missing['parents'] ?? null)
        ? $missing['parents']
        : [];

$missingVariants =
    is_array($missing['variants'] ?? null)
        ? $missing['variants']
        : [];

$mismatchParents =
    is_array($mismatches['parents'] ?? null)
        ? $mismatches['parents']
        : [];

$mismatchVariants =
    is_array($mismatches['variants'] ?? null)
        ? $mismatches['variants']
        : [];

/*
|--------------------------------------------------------------------------
| Safe Display Values
|--------------------------------------------------------------------------
*/

$artifactId =
    (string) (
        $artifact['artifact_id']
        ?? ''
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

$expectedApproved =
    (int) (
        $expected['approved_mappings']
        ?? 0
    );

$expectedParents =
    (int) (
        $expected['parent_ownership']
        ?? 0
    );

$expectedVariants =
    (int) (
        $expected['variant_ownership']
        ?? 0
    );

$verifiedApproved =
    (int) (
        $verified['approved_mappings']
        ?? 0
    );

$verifiedParents =
    (int) (
        $verified['parents']
        ?? 0
    );

$verifiedVariants =
    (int) (
        $verified['variants']
        ?? 0
    );

$parentMissingCount =
    (int) (
        $audit['parent_missing_count']
        ?? 0
    );

$parentMismatchCount =
    (int) (
        $audit['parent_mismatch_count']
        ?? 0
    );

$variantMissingCount =
    (int) (
        $audit['variant_missing_count']
        ?? 0
    );

$variantMismatchCount =
    (int) (
        $audit['variant_mismatch_count']
        ?? 0
    );

/*
|--------------------------------------------------------------------------
| Admin Notices
|--------------------------------------------------------------------------
*/

if ($error !== '') {

    echo '<div class="notice notice-error"><p>';

    echo esc_html($error);

    echo '</p></div>';

} elseif ($result !== null && !$pass) {

    echo '<div class="notice notice-error"><p>';

    echo esc_html($message);

    echo '</p></div>';

} elseif ($result !== null && $pass) {

    echo '<div class="notice notice-success"><p>';

    echo esc_html($message);

    echo '</p></div>';
}

?>

<div class="wrap">

    <h1>
        <?php
        echo esc_html(
            'Post-Ownership Verification'
        );
        ?>
    </h1>

    <p>
        <?php
        echo esc_html(
            'Independent read-only verification of the WooCommerce ownership committed by Step 5B.'
        );
        ?>
    </p>

    <?php if ($result !== null) : ?>

        <hr>

        <h2>
            <?php
            echo esc_html(
                'Verification Result'
            );
            ?>
        </h2>

        <table class="widefat striped" style="max-width: 1000px;">

            <tbody>

                <tr>
                    <td style="width: 260px;">
                        <strong>Status</strong>
                    </td>

                    <td>
                        <?php
                        if ($pass) {

                            echo '<span style="color:#008a20;font-weight:700;">PASS</span>';

                        } else {

                            echo '<span style="color:#b32d2e;font-weight:700;">';

                            echo esc_html($status);

                            echo '</span>';
                        }
                        ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        <strong>Phase</strong>
                    </td>

                    <td>
                        <?php
                        echo esc_html($phase);
                        ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        <strong>Message</strong>
                    </td>

                    <td>
                        <?php
                        echo esc_html($message);
                        ?>
                    </td>
                </tr>

            </tbody>

        </table>

        <hr>

        <h2>
            <?php
            echo esc_html(
                'Authoritative Step 5B Artifact'
            );
            ?>
        </h2>

        <table class="widefat striped" style="max-width: 1000px;">

            <tbody>

                <tr>
                    <td style="width: 260px;">
                        <strong>Artifact ID</strong>
                    </td>

                    <td>
                        <code>
                            <?php
                            echo esc_html($artifactId);
                            ?>
                        </code>
                    </td>
                </tr>

                <tr>
                    <td>
                        <strong>Snapshot UUID</strong>
                    </td>

                    <td>
                        <code>
                            <?php
                            echo esc_html($snapshotUuid);
                            ?>
                        </code>
                    </td>
                </tr>

                <tr>
                    <td>
                        <strong>Mapping Hash</strong>
                    </td>

                    <td>
                        <code>
                            <?php
                            echo esc_html($mappingHash);
                            ?>
                        </code>
                    </td>
                </tr>

                <tr>
                    <td>
                        <strong>Approved Mappings</strong>
                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                $verifiedApproved
                            )
                        );
                        ?>

                        /

                        <?php
                        echo esc_html(
                            number_format_i18n(
                                $expectedApproved
                            )
                        );
                        ?>
                    </td>
                </tr>

            </tbody>

        </table>

        <hr>

        <h2>
            <?php
            echo esc_html(
                'Ownership Verification'
            );
            ?>
        </h2>

        <table class="widefat striped" style="max-width: 1000px;">

            <thead>

                <tr>
                    <th>
                        Record Type
                    </th>

                    <th>
                        Expected
                    </th>

                    <th>
                        Verified
                    </th>