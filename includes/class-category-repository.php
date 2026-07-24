<?php

namespace BlackPrint\Commerce;

defined('ABSPATH') || exit;

class Category_Repository
{
    /**
     * Cached category terms.
     *
     * @var array|null
     */
    private static ?array $categories = null;

    /**
     * Load all product categories once.
     */
    public static function all(): array
    {
        if (self::$categories !== null) {
            return self::$categories;
        }

        $terms = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        if (is_wp_error($terms)) {
            return [];
        }

        self::$categories = [];

        foreach ($terms as $term) {
            self::$categories[$term->term_id] = $term;
        }

        return self::$categories;
    }

    /**
     * Get category by ID.
     */
    public static function get(int $term_id): ?\WP_Term
    {
        $categories = self::all();

        return $categories[$term_id] ?? null;
    }

    /**
     * Find category by slug.
     */
    public static function find_by_slug(string $slug): ?\WP_Term
    {
        $slug = sanitize_title($slug);

        foreach (self::all() as $term) {

            if ($term->slug === $slug) {
                return $term;
            }

        }

        return null;
    }

    /**
     * Get direct children.
     */
    public static function children(int $parent_id): array
    {
        $children = [];

        foreach (self::all() as $term) {

            if ((int) $term->parent === $parent_id) {
                $children[] = $term;
            }

        }

        return $children;
    }

    /**
     * Get all descendant IDs recursively.
     */
    public static function descendants(int $parent_id): array
    {
        $ids = [];

        foreach (self::children($parent_id) as $child) {

            $ids[] = $child->term_id;

            $ids = array_merge(
                $ids,
                self::descendants($child->term_id)
            );

        }

        return $ids;
    }

    /**
     * Count descendants.
     */
    public static function descendant_count(int $parent_id): int
    {
        return count(
            self::descendants($parent_id)
        );
    }

    /**
     * Count all categories.
     */
    public static function count(): int
    {
        return count(
            self::all()
        );
    }
    
    /**
 * Count products assigned to a category and all descendants.
 */
public static function descendant_product_count(int $parent_id): int
{
    $category_ids = self::descendants($parent_id);

    if (empty($category_ids)) {
        return 0;
    }

    $product_ids = get_posts([
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'tax_query'      => [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $category_ids,
            ],
        ],
    ]);

    return count($product_ids);
}

/**
 * Count all products in a category branch.
 *
 * Includes products directly assigned to the
 * category and products assigned to descendants.
 */
public static function branch_product_count(int $category_id): int
{
    $category_ids = self::descendants($category_id);

    $category_ids[] = $category_id;

    $category_ids = array_unique(
        array_map('intval', $category_ids)
    );

    $product_ids = get_posts([
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'tax_query'      => [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $category_ids,
            ],
        ],
    ]);

    return count($product_ids);
}
}