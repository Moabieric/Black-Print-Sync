<?php

namespace BlackPrint\Commerce;

defined('ABSPATH') || exit;

class Category_Health
{
    /**
     * Analyse the health of a category.
     */
    public static function analyse(array $category): array
    {
        $score = 100;

        $issues = [];

        /*
        |--------------------------------------------------------------------------
        | Image Check
        |--------------------------------------------------------------------------
        */

        if (empty($category['image'])) {

            $score -= 15;

            $issues[] = 'Missing Image';
        }

        /*
        |--------------------------------------------------------------------------
        | Description Check
        |--------------------------------------------------------------------------
        */

        if (
            empty(
                trim(
                    $category['description'] ?? ''
                )
            )
        ) {

            $score -= 15;

            $issues[] = 'Missing Description';
        }

        /*
        |--------------------------------------------------------------------------
        | Product Check
        |--------------------------------------------------------------------------
        */

        $branch_products =
            (int) (
                $category['branch_products']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Empty Category / Branch
        |--------------------------------------------------------------------------
        */

        if ($branch_products === 0) {

            $score -= 40;

            $issues[] =
                'No Products In Category Branch';
        }

        /*
        |--------------------------------------------------------------------------
        | Structural Check
        |--------------------------------------------------------------------------
        */

        $children =
            (int) (
                $category['children']
                ?? 0
            );

        $descendants =
            (int) (
                $category['descendants']
                ?? 0
            );

        if (
            $children > 0 &&
            $descendants === 0
        ) {

            $score -= 10;

            $issues[] =
                'Category Structure Inconsistent';
        }

        /*
        |--------------------------------------------------------------------------
        | Clamp Score
        |--------------------------------------------------------------------------
        */

        $score = max(
            0,
            min(
                100,
                $score
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Determine Status
        |--------------------------------------------------------------------------
        */

        if ($branch_products === 0) {

            $status = 'critical';

            $status_label =
                '🔴 Critical';

        } elseif ($score >= 80) {

            $status = 'healthy';

            $status_label =
                '🟢 Healthy';

        } else {

            $status = 'attention';

            $status_label =
                '🟡 Needs Attention';
        }

        /*
        |--------------------------------------------------------------------------
        | Return Health Report
        |--------------------------------------------------------------------------
        */

        return [

            'score' =>
                $score,

            'status' =>
                $status,

            'status_label' =>
                $status_label,

            'healthy' =>
                $status === 'healthy',

            'issues' =>
                $issues,

        ];
    }
}