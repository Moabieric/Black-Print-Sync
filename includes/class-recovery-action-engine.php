<?php

namespace BlackPrint\Commerce;

defined('ABSPATH') || exit;

/**
 * Recovery Action Engine
 *
 * Converts Category Intelligence data into
 * safe, actionable recovery recommendations.
 *
 * Version 1.0
 *
 * IMPORTANT:
 * This class is READ-ONLY.
 * It does not modify categories, products,
 * images, descriptions, or taxonomy data.
 */
class Recovery_Action_Engine
{
    /**
     * Generate recovery actions for one category.
     *
     * @param array $category Category Intelligence record.
     *
     * @return array
     */
    public static function analyse(array $category): array
    {
        $actions = [];

        /*
        |--------------------------------------------------------------------------
        | Category Identity
        |--------------------------------------------------------------------------
        */

        $category_id = (int) ($category['id'] ?? 0);

        $category_name = $category['name']
            ?? 'Unknown Category';

        /*
        |--------------------------------------------------------------------------
        | Missing Category Image
        |--------------------------------------------------------------------------
        */

        if (empty($category['has_image'])) {

            $actions[] = [

                'type' => 'missing_image',

                'priority' => 'medium',

                'category_id' => $category_id,

                'category' => $category_name,

                'message' =>
                    'Category is missing a category image.',

                'action' =>
                    'Add category image',

            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Missing Category Description
        |--------------------------------------------------------------------------
        */

        if (empty($category['has_description'])) {

            $actions[] = [

                'type' => 'missing_description',

                'priority' => 'medium',

                'category_id' => $category_id,

                'category' => $category_name,

                'message' =>
                    'Category is missing a description.',

                'action' =>
                    'Add category description',

            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Empty Category
        |--------------------------------------------------------------------------
        |
        | A category with no direct products and no
        | descendant products has no products anywhere
        | in its branch.
        |
        */

        $direct_products =
            (int) ($category['direct_products'] ?? 0);

        $descendant_products =
            (int) ($category['descendant_products'] ?? 0);

        $branch_products =
            (int) ($category['branch_products'] ?? 0);

        if ($branch_products === 0) {

            $actions[] = [

                'type' => 'empty_category',

                'priority' => 'high',

                'category_id' => $category_id,

                'category' => $category_name,

                'message' =>
                    'Category contains no products in its branch.',

                'action' =>
                    'Review category for removal, consolidation, or future use',

            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Parent Category With Products Only In Descendants
        |--------------------------------------------------------------------------
        |
        | This is not automatically an error.
        |
        | It may be a perfectly valid navigation category,
        | such as:
        |
        | Clothing
        | ├── Shirts
        | ├── Jackets
        | └── Workwear
        |
        | Therefore we flag it as informational rather
        | than treating it as a problem.
        |
        */

        $direct_children =
            (int) ($category['direct_children'] ?? 0);

        if (
            $direct_children > 0 &&
            $direct_products === 0 &&
            $descendant_products > 0
        ) {

            $actions[] = [

                'type' => 'parent_category',

                'priority' => 'info',

                'category_id' => $category_id,

                'category' => $category_name,

                'message' =>
                    'Category contains products only in child categories.',

                'action' =>
                    'Review navigation structure and category landing page',

            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Return Actions
        |--------------------------------------------------------------------------
        */

        return $actions;
    }


    /**
     * Analyse the entire category intelligence index.
     *
     * @return array
     */
    public static function analyse_all(): array
    {
        $intelligence =
            Category_Intelligence::all();

        $actions = [];

        foreach (
            $intelligence
            as $category
        ) {

            $category_actions =
                self::analyse($category);

            if (!empty($category_actions)) {

                $actions = array_merge(
                    $actions,
                    $category_actions
                );
            }
        }

        return $actions;
    }


    /**
     * Get actions for one category ID.
     *
     * @param int $category_id
     *
     * @return array
     */
    public static function for_category(
        int $category_id
    ): array {

        $category =
            Category_Intelligence::get(
                $category_id
            );

        if (!$category) {
            return [];
        }

        return self::analyse(
            $category
        );
    }


    /**
     * Get actions filtered by priority.
     *
     * @param string $priority
     *
     * @return array
     */
    public static function by_priority(
        string $priority
    ): array {

        $actions =
            self::analyse_all();

        return array_values(
            array_filter(
                $actions,
                function ($action) use ($priority) {

                    return (
                        ($action['priority'] ?? '')
                        === $priority
                    );
                }
            )
        );
    }


    /**
     * Get actions filtered by type.
     *
     * @param string $type
     *
     * @return array
     */
    public static function by_type(
        string $type
    ): array {

        $actions =
            self::analyse_all();

        return array_values(
            array_filter(
                $actions,
                function ($action) use ($type) {

                    return (
                        ($action['type'] ?? '')
                        === $type
                    );
                }
            )
        );
    }
}