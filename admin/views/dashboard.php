<?php

use BlackPrint\Commerce\Store;
use BlackPrint\Commerce\Audit;
use BlackPrint\Commerce\Category_Explorer;
use BlackPrint\Commerce\Category_Health;
use BlackPrint\Commerce\Category_Intelligence;
use BlackPrint\Commerce\Category_Tree;
use BlackPrint\Commerce\Category_Repository;
use BlackPrint\Commerce\Recovery_Action_Engine;

defined('ABSPATH') || exit;


/*
|--------------------------------------------------------------------------
| Store Overview
|--------------------------------------------------------------------------
*/

$products =
    Store::product_count();

$categories =
    Store::category_count();

$woo =
    Store::woocommerce_active();

$report =
    Audit::report();


/*
|--------------------------------------------------------------------------
| Category Intelligence Overview
|--------------------------------------------------------------------------
*/

$category_summary =
    Category_Intelligence::summary();
    
    /*
|--------------------------------------------------------------------------
| Recovery Action Engine
|--------------------------------------------------------------------------
*/

$recovery_actions =
    Recovery_Action_Engine::analyse_all();


/*
|--------------------------------------------------------------------------
| Category Tree
|--------------------------------------------------------------------------
*/

$category_tree =
    Category_Tree::build();


/*
|--------------------------------------------------------------------------
| Category Intelligence Table Filters
|--------------------------------------------------------------------------
*/

$category_filter =
    sanitize_text_field(
        $_GET['category_status'] ?? ''
    );

$coverage_filter =
    sanitize_text_field(
        $_GET['category_coverage'] ?? ''
    );

$content_filter =
    sanitize_text_field(
        $_GET['category_content'] ?? ''
    );

$structure_filter =
    sanitize_text_field(
        $_GET['category_structure'] ?? ''
    );

$category_search =
    sanitize_text_field(
        $_GET['category_search'] ?? ''
    );


/*
|--------------------------------------------------------------------------
| Category Intelligence Sorting
|--------------------------------------------------------------------------
*/

$category_sort =
    sanitize_text_field(
        $_GET['category_sort'] ?? 'name'
    );

$category_order =
    sanitize_text_field(
        $_GET['category_order'] ?? 'asc'
    );


/*
|--------------------------------------------------------------------------
| Allowed Sorting Fields
|--------------------------------------------------------------------------
*/

$allowed_sort_fields = [

    'name',

    'direct_products',

    'descendant_products',

    'branch_products',

    'descendants',

    'health_score',

];


if (
    !in_array(
        $category_sort,
        $allowed_sort_fields,
        true
    )
) {

    $category_sort = 'name';

}


if (
    !in_array(
        $category_order,
        [
            'asc',
            'desc',
        ],
        true
    )
) {

    $category_order = 'asc';

}


/*
|--------------------------------------------------------------------------
| Load Category Intelligence
|--------------------------------------------------------------------------
*/

$category_index =
    Category_Intelligence::all();


/*
|--------------------------------------------------------------------------
| Build Filtered Category Rows
|--------------------------------------------------------------------------
*/

$category_rows = [];


foreach (
    $category_index
    as $category_data
) {


    /*
    |--------------------------------------------------------------------------
    | Health Analysis
    |--------------------------------------------------------------------------
    */

    $category_health =
        Category_Health::analyse(
            $category_data
        );


    /*
    |--------------------------------------------------------------------------
    | Status Filter
    |--------------------------------------------------------------------------
    */

    if (
        !empty($category_filter) &&
        $category_health['status'] !==
        $category_filter
    ) {

        continue;

    }


    /*
    |--------------------------------------------------------------------------
    | Product Coverage Filter
    |--------------------------------------------------------------------------
    */

    $branch_products =
        (int) (
            $category_data[
                'branch_products'
            ]
            ?? 0
        );


    if (
        $coverage_filter === 'empty' &&
        $branch_products > 0
    ) {

        continue;

    }


    if (
        $coverage_filter === 'has_products' &&
        $branch_products === 0
    ) {

        continue;

    }


    /*
    |--------------------------------------------------------------------------
    | Content Filters
    |--------------------------------------------------------------------------
    */

    $has_image =
        !empty(
            $category_data[
                'has_image'
            ]
        );


    $has_description =
        !empty(
            $category_data[
                'has_description'
            ]
        );


    if (
        $content_filter === 'missing_image' &&
        $has_image
    ) {

        continue;

    }


    if (
        $content_filter === 'missing_description' &&
        $has_description
    ) {

        continue;

    }


    /*
    |--------------------------------------------------------------------------
    | Structure Filters
    |--------------------------------------------------------------------------
    */

    $direct_children =
        (int) (
            $category_data[
                'direct_children'
            ]
            ?? 0
        );


    if (
        $structure_filter === 'parent' &&
        $direct_children === 0
    ) {

        continue;

    }


    if (
        $structure_filter === 'leaf' &&
        $direct_children > 0
    ) {

        continue;

    }


    /*
    |--------------------------------------------------------------------------
    | Search Filter
    |--------------------------------------------------------------------------
    */

    if (
        !empty(
            $category_search
        )
    ) {

        $search =
            strtolower(
                $category_search
            );


        $name =
            strtolower(
                $category_data[
                    'name'
                ]
            );


        $slug_value =
            strtolower(
                $category_data[
                    'slug'
                ]
            );


        if (
            strpos(
                $name,
                $search
            ) === false
            &&
            strpos(
                $slug_value,
                $search
            ) === false
        ) {

            continue;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Add Health Data
    |--------------------------------------------------------------------------
    */

    $category_data[
        'health'
    ] =
        $category_health;


    $category_data[
        'health_score'
    ] =
        (int) (
            $category_health[
                'score'
            ]
            ?? 0
        );


    /*
    |--------------------------------------------------------------------------
    | Parent Category
    |--------------------------------------------------------------------------
    */

    $parent =
        Category_Repository::get(
            (int) (
                $category_data[
                    'parent'
                ]
                ?? 0
            )
        );


    $category_data[
        'parent_name'
    ] =
        $parent
        ? $parent->name
        : 'None';


    /*
    |--------------------------------------------------------------------------
    | Category URL
    |--------------------------------------------------------------------------
    */

    $category_data[
        'link'
    ] =
        get_term_link(
            (int) (
                $category_data[
                    'id'
                ]
            ),
            'product_cat'
        );


    if (
        is_wp_error(
            $category_data[
                'link'
            ]
        )
    ) {

        $category_data[
            'link'
        ] = '';

    }


    /*
    |--------------------------------------------------------------------------
    | Store Row
    |--------------------------------------------------------------------------
    */

    $category_rows[] =
        $category_data;

}


/*
|--------------------------------------------------------------------------
| Sort Category Rows
|--------------------------------------------------------------------------
*/

usort(

    $category_rows,

    function (
        $a,
        $b
    ) use (
        $category_sort,
        $category_order
    ) {


        /*
        |--------------------------------------------------------------------------
        | Sort By Name
        |--------------------------------------------------------------------------
        */

        if (
            $category_sort === 'name'
        ) {

            $result =
                strcasecmp(
                    $a['name'],
                    $b['name']
                );

        }


        /*
        |--------------------------------------------------------------------------
        | Sort Numeric Fields
        |--------------------------------------------------------------------------
        */

        else {

            $a_value =
                $a[
                    $category_sort
                ]
                ?? 0;


            $b_value =
                $b[
                    $category_sort
                ]
                ?? 0;


            $result =
                $a_value
                <=>
                $b_value;

        }


        /*
        |--------------------------------------------------------------------------
        | Sort Direction
        |--------------------------------------------------------------------------
        */

        return
            $category_order === 'desc'
            ? -$result
            : $result;

    }

);

?>


<div class="wrap">


<!-- ========================================================= -->
<!-- PAGE HEADER -->
<!-- ========================================================= -->

<h1>
    BlackPrint Store Recovery
</h1>

<p>
    Business Console v0.1
</p>


<!-- ========================================================= -->
<!-- STORE OVERVIEW -->
<!-- ========================================================= -->

<h2>
    Store Overview
</h2>


<table
    class="widefat striped"
    style="max-width:700px;margin-bottom:30px;"
>


<thead>

<tr>

<th>
    Item
</th>

<th>
    Value
</th>

</tr>

</thead>


<tbody>


<tr>

<td>
    <strong>
        Products
    </strong>
</td>

<td>

<?php

echo esc_html(
    $products
);

?>

</td>

</tr>


<tr>

<td>
    <strong>
        Categories
    </strong>
</td>

<td>

<?php

echo esc_html(
    $categories
);

?>

</td>

</tr>


<tr>

<td>
    <strong>
        Empty Categories
    </strong>
</td>

<td>

<?php

echo esc_html(
    $report[
        'empty_categories'
    ]
);

?>

</td>

</tr>


<tr>

<td>
    <strong>
        WooCommerce
    </strong>
</td>

<td>

<?php

echo $woo
    ? '✅ Connected'
    : '❌ Not Found';

?>

</td>

</tr>


<tr>

<td>
    <strong>
        Plugin Version
    </strong>
</td>

<td>

<?php

echo esc_html(
    BP_COMMERCE_VERSION
);

?>

</td>

</tr>


</tbody>

</table>


<!-- ========================================================= -->
<!-- STORE AUDIT -->
<!-- ========================================================= -->

<h2>
    Store Audit
</h2>


<table
    class="widefat striped"
    style="max-width:700px;"
>


<thead>

<tr>

<th>
    Check
</th>

<th>
    Result
</th>

</tr>

</thead>


<tbody>


<tr>

<td>

<strong>
    Products Without Categories
</strong>

</td>

<td>

<?php

echo esc_html(
    $report[
        'missing_categories'
    ]
);

?>

</td>

</tr>


</tbody>

</table>


<!-- ========================================================= -->
<!-- CATEGORY INTELLIGENCE OVERVIEW -->
<!-- ========================================================= -->

<hr
    style="margin:40px 0;"
>


<h2>
    Category Intelligence Overview
</h2>


<table
    class="widefat striped"
    style="max-width:900px;"
>


<thead>

<tr>

<th>
    Metric
</th>

<th>
    Value
</th>

</tr>

</thead>


<tbody>


<tr>

<td>
    <strong>
        Total Categories
    </strong>
</td>

<td>

<?php

echo esc_html(
    $category_summary[
        'total_categories'
    ]
);

?>

</td>

</tr>


<tr>

<td>
    <strong>
        🟢 Healthy
    </strong>
</td>

<td>

<?php

echo esc_html(
    $category_summary[
        'healthy'
    ]
);

?>

</td>

</tr>


<tr>

<td>
    <strong>
        🟡 Needs Attention
    </strong>
</td>

<td>

<?php

echo esc_html(
    $category_summary[
        'attention'
    ]
);

?>

</td>

</tr>


<tr>

<td>
    <strong>
        🔴 Critical
    </strong>
</td>

<td>

<?php

echo esc_html(
    $category_summary[
        'critical'
    ]
);

?>

</td>

</tr>


<tr>

<td>
    <strong>
        Empty Category Branches
    </strong>
</td>

<td>

<?php

echo esc_html(
    $category_summary[
        'empty_branches'
    ]
);

?>

</td>

</tr>


<tr>

<td>
    <strong>
        Total Branch Products
    </strong>
</td>

<td>

<?php

echo esc_html(
    $category_summary[
        'total_branch_products'
    ]
);

?>

</td>

</tr>


</tbody>

</table>

<hr style="margin:40px 0;">

<h2>Recovery Action Center</h2>

<p>
    Read-only recovery intelligence based on the current
    Category Intelligence data. No store changes are performed
    from this panel.
</p>

<?php

/*
|--------------------------------------------------------------------------
| Recovery Action Filters
|--------------------------------------------------------------------------
*/

$recovery_priority =
    isset($_GET['recovery_priority'])
        ? sanitize_text_field(
            wp_unslash($_GET['recovery_priority'])
        )
        : '';

$recovery_type =
    isset($_GET['recovery_type'])
        ? sanitize_text_field(
            wp_unslash($_GET['recovery_type'])
        )
        : '';

$filtered_recovery_actions =
    $recovery_actions;


/*
|--------------------------------------------------------------------------
| Filter By Priority
|--------------------------------------------------------------------------
*/

if (!empty($recovery_priority)) {

    $filtered_recovery_actions =
        array_filter(
            $filtered_recovery_actions,
            function ($action) use ($recovery_priority) {

                return (
                    ($action['priority'] ?? '')
                    === $recovery_priority
                );
            }
        );
}


/*
|--------------------------------------------------------------------------
| Filter By Action Type
|--------------------------------------------------------------------------
*/

if (!empty($recovery_type)) {

    $filtered_recovery_actions =
        array_filter(
            $filtered_recovery_actions,
            function ($action) use ($recovery_type) {

                return (
                    ($action['type'] ?? '')
                    === $recovery_type
                );
            }
        );
}


/*
|--------------------------------------------------------------------------
| Recovery Action Statistics
|--------------------------------------------------------------------------
*/

$high_count = 0;
$medium_count = 0;
$info_count = 0;

$recovery_types = [];

foreach ($recovery_actions as $action) {

    $priority =
        $action['priority'] ?? '';

    $type =
        $action['type'] ?? '';

    if ($priority === 'high') {
        $high_count++;
    }

    if ($priority === 'medium') {
        $medium_count++;
    }

    if ($priority === 'info') {
        $info_count++;
    }

    if (!empty($type)) {
        $recovery_types[$type] = true;
    }
}

ksort($recovery_types);

?>

<table class="widefat striped"
       style="max-width:900px;margin-bottom:20px;">

<thead>

<tr>

    <th>Total Recovery Actions</th>

    <th>🔴 High</th>

    <th>🟡 Medium</th>

    <th>🔵 Info</th>

</tr>

</thead>

<tbody>

<tr>

    <td>
        <strong>
            <?php
            echo esc_html(
                count($recovery_actions)
            );
            ?>
        </strong>
    </td>

    <td>
        <?php echo esc_html($high_count); ?>
    </td>

    <td>
        <?php echo esc_html($medium_count); ?>
    </td>

    <td>
        <?php echo esc_html($info_count); ?>
    </td>

</tr>

</tbody>

</table>


<form method="get"
      style="margin-bottom:20px;">

    <input
        type="hidden"
        name="page"
        value="blackprint-commerce"
    >

    <?php if (!empty($search)): ?>

        <input
            type="hidden"
            name="category_search"
            value="<?php
                echo esc_attr($search);
            ?>"
        >

    <?php endif; ?>


    <select name="recovery_priority">

        <option value="">
            All Priorities
        </option>

        <option
            value="high"
            <?php
            selected(
                $recovery_priority,
                'high'
            );
            ?>
        >
            High
        </option>

        <option
            value="medium"
            <?php
            selected(
                $recovery_priority,
                'medium'
            );
            ?>
        >
            Medium
        </option>

        <option
            value="info"
            <?php
            selected(
                $recovery_priority,
                'info'
            );
            ?>
        >
            Info
        </option>

    </select>


    <select name="recovery_type">

        <option value="">
            All Recovery Types
        </option>

        <?php foreach (
            array_keys($recovery_types)
            as $type
        ): ?>

            <option
                value="<?php
                    echo esc_attr($type);
                ?>"
                <?php
                selected(
                    $recovery_type,
                    $type
                );
                ?>
            >
                <?php
                echo esc_html(
                    ucwords(
                        str_replace(
                            '_',
                            ' ',
                            $type
                        )
                    )
                );
                ?>
            </option>

        <?php endforeach; ?>

    </select>


    <input
        type="submit"
        class="button button-primary"
        value="Filter Recovery Actions"
    >


    <a
        href="<?php
            echo esc_url(
                admin_url(
                    'admin.php?page=blackprint-commerce'
                )
            );
        ?>"
        class="button"
    >
        Reset
    </a>

</form>


<table class="widefat striped"
       style="max-width:1100px;">

<thead>

<tr>

    <th>Priority</th>

    <th>Category</th>

    <th>Issue</th>

    <th>Recommended Action</th>

    <th>Action</th>

</tr>

</thead>

<tbody>

<?php if (
    empty($filtered_recovery_actions)
): ?>

<tr>

    <td colspan="5">

        <?php if (
            empty($recovery_actions)
        ): ?>

            No recovery actions detected.

        <?php else: ?>

            No recovery actions match
            the selected filters.

        <?php endif; ?>

    </td>

</tr>

<?php else: ?>

    <?php foreach (
        $filtered_recovery_actions
        as $action
    ): ?>

        <?php

        $priority =
            $action['priority']
            ?? 'info';

        $category_id =
            (int) (
                $action['category_id']
                ?? 0
            );

        $category_name =
            $action['category']
            ?? 'Unknown';

        $message =
            $action['message']
            ?? '';

        $recommended_action =
            $action['action']
            ?? '';

        ?>

        <tr>

            <td>

                <?php

                if ($priority === 'high') {

                    echo '🔴 High';

                } elseif (
                    $priority === 'medium'
                ) {

                    echo '🟡 Medium';

                } else {

                    echo '🔵 Info';

                }

                ?>

            </td>


            <td>

                <strong>

                    <?php
                    echo esc_html(
                        $category_name
                    );
                    ?>

                </strong>

            </td>


            <td>

                <?php
                echo esc_html(
                    $message
                );
                ?>

            </td>


            <td>

                <?php
                echo esc_html(
                    $recommended_action
                );
                ?>

            </td>


            <td>

                <?php

                $category_term =
                    Category_Repository::get(
                        $category_id
                    );

                if ($category_term) {

                    $inspect_url =
                        add_query_arg(
                            [
                                'page' =>
                                    'blackprint-commerce',

                                'category' =>
                                    $category_term->slug,
                            ],
                            admin_url(
                                'admin.php'
                            )
                        );

                    ?>

                    <a
                        href="<?php
                            echo esc_url(
                                $inspect_url
                            );
                        ?>"
                        class="button button-small"
                    >
                        Inspect
                    </a>

                    <?php

                } else {

                    echo '—';

                }

                ?>

            </td>

        </tr>

    <?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>




<!-- ========================================================= -->
<!-- CATEGORY INTELLIGENCE TABLE -->
<!-- ========================================================= -->

<hr
    style="margin:40px 0;"
>


<h2>
    Category Intelligence Table
</h2>


<p>
    Filter and inspect categories that require recovery attention.
</p>


<!-- ========================================================= -->
<!-- FILTER FORM -->
<!-- ========================================================= -->

<form
    method="get"
    style="margin-bottom:20px;"
>


<input
    type="hidden"
    name="page"
    value="blackprint-commerce"
>


<!-- FILTER ROW -->


<div
    style="
        display:flex;
        flex-wrap:wrap;
        gap:10px;
        align-items:center;
        margin-bottom:10px;
    "
>


<!-- SEARCH -->


<input
    type="search"
    name="category_search"
    value="<?php

        echo esc_attr(
            $category_search
        );

    ?>"
    placeholder="Search name or slug"
    style="width:250px;"
>


<!-- STATUS -->


<select
    name="category_status"
>


<option value="">
    All Statuses
</option>


<option
    value="healthy"
    <?php

    selected(
        $category_filter,
        'healthy'
    );

    ?>
>

    🟢 Healthy

</option>


<option
    value="attention"
    <?php

    selected(
        $category_filter,
        'attention'
    );

    ?>
>

    🟡 Needs Attention

</option>


<option
    value="critical"
    <?php

    selected(
        $category_filter,
        'critical'
    );

    ?>
>

    🔴 Critical

</option>


</select>


<!-- PRODUCT COVERAGE -->


<select
    name="category_coverage"
>


<option value="">
    All Product Coverage
</option>


<option
    value="empty"
    <?php

    selected(
        $coverage_filter,
        'empty'
    );

    ?>
>

    Empty Branches

</option>


<option
    value="has_products"
    <?php

    selected(
        $coverage_filter,
        'has_products'
    );

    ?>
>

    Has Products

</option>


</select>


<!-- CONTENT -->


<select
    name="category_content"
>


<option value="">
    All Content
</option>


<option
    value="missing_image"
    <?php

    selected(
        $content_filter,
        'missing_image'
    );

    ?>
>

    Missing Images

</option>


<option
    value="missing_description"
    <?php

    selected(
        $content_filter,
        'missing_description'
    );

    ?>
>

    Missing Descriptions

</option>


</select>


<!-- STRUCTURE -->


<select
    name="category_structure"
>


<option value="">
    All Structures
</option>


<option
    value="parent"
    <?php

    selected(
        $structure_filter,
        'parent'
    );

    ?>
>

    Parent Categories

</option>


<option
    value="leaf"
    <?php

    selected(
        $structure_filter,
        'leaf'
    );

    ?>
>

    Leaf Categories

</option>


</select>


</div>


<!-- SORT ROW -->


<div
    style="
        display:flex;
        flex-wrap:wrap;
        gap:10px;
        align-items:center;
    "
>


<!-- SORT FIELD -->


<select
    name="category_sort"
>


<option
    value="name"
    <?php

    selected(
        $category_sort,
        'name'
    );

    ?>
>

    Sort: Name

</option>


<option
    value="direct_products"
    <?php

    selected(
        $category_sort,
        'direct_products'
    );

    ?>
>

    Sort: Direct Products

</option>


<option
    value="descendant_products"
    <?php

    selected(
        $category_sort,
        'descendant_products'
    );

    ?>
>

    Sort: Descendant Products

</option>


<option
    value="branch_products"
    <?php

    selected(
        $category_sort,
        'branch_products'
    );

    ?>
>

    Sort: Branch Products

</option>


<option
    value="descendants"
    <?php

    selected(
        $category_sort,
        'descendants'
    );

    ?>
>

    Sort: Descendant Count

</option>


<option
    value="health_score"
    <?php

    selected(
        $category_sort,
        'health_score'
    );

    ?>
>

    Sort: Health Score

</option>


</select>


<!-- SORT ORDER -->


<select
    name="category_order"
>


<option
    value="asc"
    <?php

    selected(
        $category_order,
        'asc'
    );

    ?>
>

    Ascending

</option>


<option
    value="desc"
    <?php

    selected(
        $category_order,
        'desc'
    );

    ?>
>

    Descending

</option>


</select>


<!-- APPLY -->


<input
    type="submit"
    class="button button-primary"
    value="Apply Filters"
>


<!-- RESET -->


<a
    href="<?php

        echo esc_url(
            admin_url(
                'admin.php?page=blackprint-commerce'
            )
        );

    ?>"
    class="button"
>

    Reset

</a>


</div>


</form>


<!-- ========================================================= -->
<!-- INTELLIGENCE TABLE -->
<!-- ========================================================= -->


<div
    style="overflow-x:auto;"
>


<table
    class="widefat striped"
>


<thead>


<tr>


<th>
    Category
</th>


<th>
    Parent
</th>


<th>
    Children
</th>


<th>
    Descendants
</th>


<th>
    Direct Products
</th>


<th>
    Descendant Products
</th>


<th>
    Branch Products
</th>


<th>
    Image
</th>


<th>
    Description
</th>


<th>
    Health
</th>


<th>
    Action
</th>


</tr>


</thead>


<tbody>


<?php if (
    !empty(
        $category_rows
    )
): ?>


<?php foreach (
    $category_rows
    as $row
): ?>


<tr>


<!-- CATEGORY -->


<td>


<strong>

<?php

echo esc_html(
    $row[
        'name'
    ]
);

?>

</strong>


<br>


<small>

<?php

echo esc_html(
    $row[
        'slug'
    ]
);

?>

</small>


</td>


<!-- PARENT -->


<td>

<?php

echo esc_html(
    $row[
        'parent_name'
    ]
);

?>

</td>


<!-- CHILDREN -->


<td>

<?php

echo esc_html(
    $row[
        'direct_children'
    ]
    ?? 0
);

?>

</td>


<!-- DESCENDANTS -->


<td>

<?php

echo esc_html(
    $row[
        'descendants'
    ]
    ?? 0
);

?>

</td>


<!-- DIRECT PRODUCTS -->


<td>

<?php

echo esc_html(
    $row[
        'direct_products'
    ]
    ?? 0
);

?>

</td>


<!-- DESCENDANT PRODUCTS -->


<td>

<?php

echo esc_html(
    $row[
        'descendant_products'
    ]
    ?? 0
);

?>

</td>


<!-- BRANCH PRODUCTS -->


<td>


<strong>

<?php

echo esc_html(
    $row[
        'branch_products'
    ]
    ?? 0
);

?>

</strong>


</td>


<!-- IMAGE -->


<td>

<?php

echo !empty(
    $row[
        'has_image'
    ]
)

    ? '✅'

    : '❌';

?>

</td>


<!-- DESCRIPTION -->


<td>

<?php

echo !empty(
    $row[
        'has_description'
    ]
)

    ? '✅'

    : '❌';

?>

</td>


<!-- HEALTH -->


<td>


<?php

echo esc_html(
    $row[
        'health'
    ][
        'status_label'
    ]
    ?? 'Unknown'
);

?>


<br>


<small>

Score:

<?php

echo esc_html(
    $row[
        'health_score'
    ]
);

?>/100

</small>


</td>


<!-- ACTION -->


<td>


<?php if (
    !empty(
        $row[
            'link'
        ]
    )
): ?>


<a
    href="<?php

        echo esc_url(

            add_query_arg(

                [

                    'page' =>
                        'blackprint-commerce',

                    'category' =>
                        $row[
                            'slug'
                        ],

                ],

                admin_url(
                    'admin.php'
                )

            )

        );

    ?>"
    class="button button-small"
>

    Inspect

</a>


<?php endif; ?>


</td>


</tr>


<?php endforeach; ?>


<?php else: ?>


<tr>


<td
    colspan="11"
>


No categories match
the selected filters.


</td>


</tr>


<?php endif; ?>


</tbody>


</table>


</div>


<p
    style="margin-top:10px;"
>


Showing

<strong>

<?php

echo esc_html(
    count(
        $category_rows
    )
);

?>

</strong>

categories.


</p>


<!-- ========================================================= -->
<!-- CATEGORY INTELLIGENCE CENTER -->
<!-- ========================================================= -->


<hr
    style="margin:40px 0;"
>


<h2>
    Category Intelligence Center
</h2>


<p>
    Inspect category structure, commerce data, content quality,
    health status, and recommendations.
</p>


<?php

$slug =
    sanitize_text_field(
        $_GET[
            'category'
        ]
        ?? ''
    );


$category = null;

$health = null;

$recommendations = [];


if (
    !empty(
        $slug
    )
) {


    $category =
        Category_Explorer::inspect(
            $slug
        );


    if (
        $category
    ) {


        $health =
            Category_Health::analyse(
                $category
            );


        $recommendations =
            \BlackPrint\Commerce\Category_Recommendations::analyse(
                $category
            );

    }

}

?>


<form
    method="get"
>


<input
    type="hidden"
    name="page"
    value="blackprint-commerce"
>


<p>


<input
    type="text"
    name="category"
    value="<?php

        echo esc_attr(
            $slug
        );

    ?>"
    placeholder="Enter category slug"
    style="width:300px;"
>


<input
    type="submit"
    class="button button-primary"
    value="Inspect"
>


</p>


</form>


<?php if (
    $category
): ?>


<table
    class="widefat striped"
    style="max-width:900px;"
>


<tbody>


<tr>

<td width="240">

<strong>
    Name
</strong>

</td>

<td>

<?php

echo esc_html(
    $category[
        'name'
    ]
);

?>

</td>

</tr>


<tr>

<td>

<strong>
    Slug
</strong>

</td>

<td>

<?php

echo esc_html(
    $category[
        'slug'
    ]
);

?>

</td>

</tr>


<tr>

<td>

<strong>
    Category ID
</strong>

</td>

<td>

<?php

echo esc_html(
    $category[
        'id'
    ]
);

?>

</td>

</tr>


<tr>

<td>

<strong>
    Taxonomy
</strong>

</td>

<td>

<?php

echo esc_html(
    $category[
        'taxonomy'
    ]
);

?>

</td>

</tr>


<tr>

<td>

<strong>
    Frontend
</strong>

</td>

<td>


<?php if (
    !empty(
        $category[
            'link'
        ]
    )
): ?>


<a
    href="<?php

        echo esc_url(
            $category[
                'link'
            ]
        );

    ?>"
    target="_blank"
    class="button button-small"
>

    View Category →

</a>


<?php endif; ?>


</td>

</tr>


<tr>

<td>

<strong>
    Parent Category
</strong>

</td>

<td>

<?php

echo esc_html(
    $category[
        'parent'
    ]
);

?>

</td>

</tr>


<tr>

<td>

<strong>
    Direct Children
</strong>

</td>

<td>

<?php

echo esc_html(
    $category[
        'children'
    ]
);

?>

</td>

</tr>


<tr>

<td>

<strong>
    Total Descendants
</strong>

</td>

<td>

<?php

echo esc_html(
    $category[
        'descendants'
    ]
    ?? 0
);

?>

</td>

</tr>


<tr>

<td>

<strong>
    Direct Products
</strong>

</td>

<td>

<?php

echo esc_html(
    $category[
        'count'
    ]
);

?>

</td>

</tr>


<tr>

<td>

<strong>
    Descendant Products
</strong>

</td>

<td>

<?php

echo esc_html(
    $category[
        'descendant_products'
    ]
    ?? 0
);

?>

</td>

</tr>


<tr>

<td>

<strong>
    Total Branch Products
</strong>

</td>

<td>

<?php

echo esc_html(
    $category[
        'branch_products'
    ]
    ?? 0
);

?>

</td>

</tr>


<tr>

<td>

<strong>
    Category Image
</strong>

</td>

<td>

<?php

echo !empty(
    $category[
        'image'
    ]
)

    ? '✅ Yes'

    : '❌ Missing';

?>

</td>

</tr>


<tr>

<td>

<strong>
    Description
</strong>

</td>

<td>

<?php

echo !empty(
    trim(
        $category[
            'description'
        ]
        ?? ''
    )
)

    ? '✅ Present'

    : '❌ Missing';

?>

</td>

</tr>


</tbody>

</table>


<?php elseif (
    !empty(
        $slug
    )
): ?>


<p>

<strong>
    Category not found.
</strong>

</p>


<?php endif; ?>


<!-- ========================================================= -->
<!-- CATEGORY HEALTH -->
<!-- ========================================================= -->


<?php if (
    $health
): ?>


<hr
    style="margin:40px 0;"
>


<h2>
    Category Health
</h2>


<table
    class="widefat striped"
    style="max-width:900px;"
>


<tbody>


<tr>


<td width="240">


<strong>
    Health Score
</strong>


</td>


<td>


<?php

echo esc_html(
    $health[
        'score'
    ]
    ?? 0
);

?>/100


</td>


</tr>


<tr>


<td>


<strong>
    Status
</strong>


</td>


<td>


<?php

echo esc_html(
    $health[
        'status_label'
    ]
    ?? 'Unknown'
);

?>


</td>


</tr>


<tr>


<td
    valign="top"
>


<strong>
    Issues
</strong>


</td>


<td>


<?php


if (
    empty(
        $health[
            'issues'
        ]
    )
) {


    echo 'None';


} else {


    echo '<ul>';


    foreach (
        $health[
            'issues'
        ]
        as $issue
    ) {


        echo '<li>';

        echo esc_html(
            $issue
        );

        echo '</li>';


    }


    echo '</ul>';


}


?>


</td>


</tr>


</tbody>


</table>


<?php endif; ?>


<!-- ========================================================= -->
<!-- CATEGORY RECOMMENDATIONS -->
<!-- ========================================================= -->


<?php if (
    !empty(
        $recommendations
    )
): ?>


<hr
    style="margin:40px 0;"
>


<h2>
    Recommendations
</h2>


<table
    class="widefat striped"
    style="max-width:900px;"
>


<thead>


<tr>


<th>
    Priority
</th>


<th>
    Recommendation
</th>


</tr>


</thead>


<tbody>


<?php foreach (
    $recommendations
    as $recommendation
): ?>


<tr>


<td>


<?php

echo esc_html(

    $recommendation[
        'priority'
    ]
    ?? 'Normal'

);

?>


</td>


<td>


<?php

echo esc_html(

    $recommendation[
        'message'
    ]
    ?? $recommendation[
        'recommendation'
    ]
    ?? ''

);

?>


</td>


</tr>


<?php endforeach; ?>


</tbody>


</table>


<?php endif; ?>





?>


</div>