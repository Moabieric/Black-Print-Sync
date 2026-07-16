<?php
/**
 * Plugin Name: BlackPrint Commerce
 * Plugin URI: https://blackprint.co.za
 * Description: Commerce Engine for BlackPrint OS.
 * Version: 0.1.0
 * Author: BlackPrint
 * Author URI: https://blackprint.co.za
 * License: GPL2+
 * Text Domain: blackprint-commerce
 */

defined('ABSPATH') || exit;

/*
|--------------------------------------------------------------------------
| Plugin Constants
|--------------------------------------------------------------------------
*/

define('BP_COMMERCE_VERSION', '0.1.0');

define('BP_COMMERCE_FILE', __FILE__);

define('BP_COMMERCE_PATH', plugin_dir_path(__FILE__));

define('BP_COMMERCE_URL', plugin_dir_url(__FILE__));

/*
|--------------------------------------------------------------------------
| Load Core
|--------------------------------------------------------------------------
*/

require_once BP_COMMERCE_PATH . 'includes/class-loader.php';

/*
|--------------------------------------------------------------------------
| Boot Plugin
|--------------------------------------------------------------------------
*/

function bp_commerce()
{
    return BlackPrint\Commerce\Loader::instance();
}

bp_commerce();