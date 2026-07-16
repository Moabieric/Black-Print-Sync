<?php defined('ABSPATH') || exit; ?>

<div class="wrap">

<h1>BlackPrint Commerce</h1>

<p><strong>Version:</strong> <?php echo esc_html(BP_COMMERCE_VERSION); ?></p>

<hr>

<div style="display:grid;
grid-template-columns:repeat(3,1fr);
gap:20px;
max-width:900px;">

<?php

$cards = [
'Products',
'Categories',
'Images',
'Suppliers',
'Store Health',
'System'
];

foreach ($cards as $card): ?>

<div style="
background:#fff;
padding:20px;
border:1px solid #ddd;
border-radius:8px;">

<h2><?php echo esc_html($card); ?></h2>

<p>Coming Soon</p>

</div>

<?php endforeach; ?>

</div>

</div>