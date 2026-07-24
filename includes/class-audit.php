<?php

namespace BlackPrint\Commerce;

defined('ABSPATH') || exit;

class Audit
{
    public static function report(): array
{
    return [
        'missing_categories' => self::products_without_categories(),
        'empty_categories'   => self::empty_categories(),
    ];
}

    private static function products_without_categories(): int
    {
        global $wpdb;

        return (int) $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'product'
              AND p.post_status = 'publish'
              AND NOT EXISTS (
                  SELECT 1
                  FROM {$wpdb->term_relationships} tr
                  INNER JOIN {$wpdb->term_taxonomy} tt
                    ON tr.term_taxonomy_id = tt.term_taxonomy_id
                  WHERE tr.object_id = p.ID
                    AND tt.taxonomy = 'product_cat'
              )
        ");
        
        
    }
    
    private static function empty_categories(): int
{
    $count = wp_count_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
    ]);

    $total = Store::category_count();

    return max(0, $total - (int) $count);
    return [
    'missing_categories' => self::products_without_categories(),
    'empty_categories'   => self::empty_categories(),
];
}
}