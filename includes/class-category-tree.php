<?php

namespace BlackPrint\Commerce;

defined('ABSPATH') || exit;

class Category_Tree
{
    /**
     * Build category tree recursively.
     *
     * Uses Category_Repository as the single
     * source of category data.
     */
    public static function build(int $parent = 0): array
    {
        $terms = Category_Repository::children($parent);

        $tree = [];

        foreach ($terms as $term) {

            $tree[] = [
                'id'       => $term->term_id,
                'name'     => $term->name,
                'slug'     => $term->slug,
                'count'    => $term->count,
                'children' => self::build($term->term_id),
            ];

        }

        return $tree;
    }

    /**
     * Get direct children.
     *
     * Kept as a convenience method so other
     * modules can work with Category_Tree.
     */
    public static function children(int $parent_id): array
    {
        return Category_Repository::children($parent_id);
    }

    /**
     * Get all descendant IDs.
     */
    public static function descendants(int $parent_id): array
    {
        return Category_Repository::descendants($parent_id);
    }

    /**
     * Count descendants.
     */
    public static function descendant_count(int $parent_id): int
    {
        return Category_Repository::descendant_count($parent_id);
    }
}