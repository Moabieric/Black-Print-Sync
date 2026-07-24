<?php

namespace BlackPrint\Commerce;

defined('ABSPATH') || exit;

class Category_Recommendations
{
    public static function analyse(array $category): array
    {
        $recommendations = [];

        if ($category['count'] == 0 && $category['children'] > 0) {
            $recommendations[] =
                'Acts as a parent category. This is normal if products are assigned to child categories.';
        }

        if ($category['count'] == 0 && $category['children'] == 0) {
            $recommendations[] =
                'Empty category. Consider removing or assigning products.';
        }

        if (!$category['image']) {
            $recommendations[] =
                'Upload a category image to improve navigation.';
        }

        if (empty($category['description'])) {
            $recommendations[] =
                'Add a description for SEO and customer guidance.';
        }

        return $recommendations;
    }
}