<?php

namespace BlackPrint\Commerce;

defined('ABSPATH') || exit;

class Category_Intelligence
{
    /**
     * Cached intelligence index.
     *
     * @var array|null
     */
    private static ?array $index = null;

    /**
     * Build the complete category intelligence index.
     *
     * The index is calculated in memory so the dashboard
     * does not repeatedly query products for every category.
     */
    public static function build(): array
    {
        if (self::$index !== null) {
            return self::$index;
        }

        /*
        |--------------------------------------------------------------------------
        | Load Categories
        |--------------------------------------------------------------------------
        */

        $terms = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ]);

        if (is_wp_error($terms)) {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | Initialise Category Index
        |--------------------------------------------------------------------------
        */

        $index = [];

        foreach ($terms as $term) {

            $image_id = get_term_meta(
                $term->term_id,
                'thumbnail_id',
                true
            );

            $index[$term->term_id] = [

                'id' => (int) $term->term_id,

                'name' => $term->name,

                'slug' => $term->slug,

                'parent' => (int) $term->parent,

                'direct_children' => 0,

                'descendants' => 0,

                'direct_products' => 0,

                'descendant_products' => 0,

                'branch_products' => 0,

                'has_image' => !empty($image_id),

                'has_description' =>
                    !empty(
                        trim($term->description)
                    ),

                'children' => [],

                'descendant_ids' => [],

            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Build Parent → Child Relationships
        |--------------------------------------------------------------------------
        */

        foreach ($index as $term_id => &$category) {

            $parent_id = $category['parent'];

            if (
                $parent_id > 0 &&
                isset($index[$parent_id])
            ) {

                $index[$parent_id]['children'][] =
                    $term_id;

                $index[$parent_id]['direct_children']++;
            }
        }

        unset($category);

        /*
        |--------------------------------------------------------------------------
        | Build Descendant Relationships
        |--------------------------------------------------------------------------
        */

        foreach ($index as $term_id => &$category) {

            $category['descendant_ids'] =
                self::collect_descendants(
                    $term_id,
                    $index
                );

            $category['descendants'] =
                count(
                    $category['descendant_ids']
                );
        }

        unset($category);

        /*
        |--------------------------------------------------------------------------
        | Load Product → Category Relationships
        |--------------------------------------------------------------------------
        */

        $product_ids = get_posts([

            'post_type'      => 'product',

            'post_status'    => 'publish',

            'posts_per_page' => -1,

            'fields'         => 'ids',

        ]);

        /*
        |--------------------------------------------------------------------------
        | Assign Products To Categories
        |--------------------------------------------------------------------------
        */

        foreach ($product_ids as $product_id) {

            $product_categories = wp_get_post_terms(

                $product_id,

                'product_cat',

                [
                    'fields' => 'ids',
                ]

            );

            if (
                is_wp_error(
                    $product_categories
                )
            ) {
                continue;
            }

            foreach ($product_categories as $category_id) {

                $category_id = (int) $category_id;

                if (
                    !isset(
                        $index[$category_id]
                    )
                ) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Direct Product
                |--------------------------------------------------------------------------
                */

                $index[$category_id]
                    ['direct_products']++;

            }
        }

        /*
        |--------------------------------------------------------------------------
        | Calculate Descendant & Branch Products
        |--------------------------------------------------------------------------
        */

        foreach ($index as $term_id => &$category) {

            $descendant_product_count = 0;

            foreach (
                $category['descendant_ids']
                as $descendant_id
            ) {

                if (
                    isset(
                        $index[$descendant_id]
                    )
                ) {

                    $descendant_product_count +=
                        $index[$descendant_id]
                            ['direct_products'];

                }
            }

            $category['descendant_products'] =
                $descendant_product_count;

            $category['branch_products'] =
                $category['direct_products']
                +
                $category['descendant_products'];
        }

        unset($category);

        self::$index = $index;

        return self::$index;
    }


    /**
     * Recursively collect all descendants.
     */
    private static function collect_descendants(
        int $parent_id,
        array &$index
    ): array {

        $descendants = [];

        if (
            !isset(
                $index[$parent_id]
            )
        ) {
            return [];
        }

        foreach (
            $index[$parent_id]['children']
            as $child_id
        ) {

            $descendants[] =
                $child_id;

            $descendants = array_merge(

                $descendants,

                self::collect_descendants(
                    $child_id,
                    $index
                )

            );
        }

        return array_unique(
            $descendants
        );
    }


    /**
     * Get intelligence for one category.
     */
    public static function get(
        int $term_id
    ): ?array {

        $index = self::build();

        return $index[$term_id]
            ?? null;
    }


    /**
     * Get complete intelligence index.
     */
    public static function all(): array
    {
        return self::build();
    }


    /**
 * Get store-wide category intelligence summary.
 */
public static function summary(): array
{
    $index = self::build();

    $summary = [

        'total' =>
            count($index),

        'healthy' =>
            0,

        'attention' =>
            0,

        'critical' =>
            0,

        'empty_branches' =>
            0,

        'total_branch_products' =>
            0,

    ];

    foreach ($index as $category) {

        /*
        |--------------------------------------------------------------------------
        | Build Health Data
        |--------------------------------------------------------------------------
        */

        $health =
            Category_Health::analyse(
                $category
            );

        /*
        |--------------------------------------------------------------------------
        | Count Status
        |--------------------------------------------------------------------------
        */

        switch ($health['status']) {

            case 'healthy':

                $summary['healthy']++;

                break;

            case 'attention':

                $summary['attention']++;

                break;

            case 'critical':

                $summary['critical']++;

                break;
        }

        /*
        |--------------------------------------------------------------------------
        | Empty Branches
        |--------------------------------------------------------------------------
        */

        if (
            (int) $category['branch_products']
            === 0
        ) {

            $summary['empty_branches']++;
        }

        /*
        |--------------------------------------------------------------------------
        | Branch Product Total
        |--------------------------------------------------------------------------
        */

        $summary['total_branch_products'] +=
            (int) $category['branch_products'];
    }

    return $summary;
}

    /**
     * Clear in-memory index.
     */
    public static function clear(): void
    {
        self::$index = null;
    }
    

}