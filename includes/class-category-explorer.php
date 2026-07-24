<?php

namespace BlackPrint\Commerce;

defined('ABSPATH') || exit;

class Category_Explorer
{
    /**
     * Inspect a WooCommerce category by slug.
     */
    public static function inspect(string $slug): ?array
    {
        /*
        |--------------------------------------------------------------------------
        | Find Category
        |--------------------------------------------------------------------------
        */

        $term = Category_Repository::find_by_slug($slug);

        if (!$term) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Get Category Intelligence
        |--------------------------------------------------------------------------
        */

        $intelligence = Category_Intelligence::get(
            (int) $term->term_id
        );

        /*
        |--------------------------------------------------------------------------
        | Parent Category
        |--------------------------------------------------------------------------
        */

        $parent_name = 'None';

        if ($term->parent) {

            $parent = Category_Repository::get(
                (int) $term->parent
            );

            if ($parent) {
                $parent_name = $parent->name;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Direct Child Categories
        |--------------------------------------------------------------------------
        */

        $children = Category_Repository::children(
            (int) $term->term_id
        );

        $child_count = count($children);

        /*
        |--------------------------------------------------------------------------
        | Category Image
        |--------------------------------------------------------------------------
        */

        $image_id = get_term_meta(
            $term->term_id,
            'thumbnail_id',
            true
        );

        /*
        |--------------------------------------------------------------------------
        | Category URL
        |--------------------------------------------------------------------------
        */

        $link = get_term_link($term);

        if (is_wp_error($link)) {
            $link = '';
        }

        /*
        |--------------------------------------------------------------------------
        | Return Category Intelligence
        |--------------------------------------------------------------------------
        */

        return [

            /*
            |--------------------------------------------------------------------------
            | Identity
            |--------------------------------------------------------------------------
            */

            'id' =>
                (int) $term->term_id,

            'name' =>
                $term->name,

            'slug' =>
                $term->slug,

            /*
            |--------------------------------------------------------------------------
            | Structure
            |--------------------------------------------------------------------------
            */

            'parent' =>
                $parent_name,

            'children' =>
                $child_count,

            'descendants' =>
                $intelligence['descendants'] ?? 0,

            /*
            |--------------------------------------------------------------------------
            | Commerce
            |--------------------------------------------------------------------------
            */

            'count' =>
                (int) $term->count,

            'direct_products' =>
                $intelligence['direct_products'] ?? 0,

            'descendant_products' =>
                $intelligence['descendant_products'] ?? 0,

            'branch_products' =>
                $intelligence['branch_products'] ?? 0,

            /*
            |--------------------------------------------------------------------------
            | Content
            |--------------------------------------------------------------------------
            */

            'image' =>
                $image_id,

            'description' =>
                $term->description,

            /*
            |--------------------------------------------------------------------------
            | WordPress
            |--------------------------------------------------------------------------
            */

            'link' =>
                $link,

            'taxonomy' =>
                $term->taxonomy,

        ];
    }


    /**
     * Return all categories.
     */
    public static function all_categories(): array
    {
        return Category_Repository::all();
    }
}