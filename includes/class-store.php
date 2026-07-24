<?php

namespace BlackPrint\Commerce;

defined('ABSPATH') || exit;

class Store
{
    /**
     * Published WooCommerce products
     */
    public static function product_count(): int
    {
        $counts = wp_count_posts('product');

        return (int) ($counts->publish ?? 0);
    }

    /**
     * Product Categories
     */
    public static function category_count(): int
    {
        $count = wp_count_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ]);

        return is_wp_error($count) ? 0 : (int) $count;
    }

    /**
     * WooCommerce Active?
     */
    public static function woocommerce_active(): bool
    {
        return class_exists('WooCommerce');
    }
}