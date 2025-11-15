<?php
/**
 * Plugin Name: Gun Shop Directory
 * Plugin URI: https://github.com/GunWiseWeb/gun-shop-directory
 * Description: A professional business directory plugin for gun shops with ratings and reviews.
 * Version: 2.0.7
 * Author: GunWise
 * Author URI: https://gunwise.com
 * License: GPL-2.0+
 * Text Domain: gun-shop-directory
 */

if (!defined('WPINC')) {
    die;
}

define('GSD_VERSION', '2.0.7');
define('GSD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GSD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GSD_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Activation
function activate_gun_shop_directory() {
    require_once GSD_PLUGIN_DIR . 'includes/class-gsd-activator.php';
    GSD_Activator::activate();
    flush_rewrite_rules();
}

// Deactivation
function deactivate_gun_shop_directory() {
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'activate_gun_shop_directory');
register_deactivation_hook(__FILE__, 'deactivate_gun_shop_directory');

// Load core
require GSD_PLUGIN_DIR . 'includes/class-gsd-core.php';

// Run plugin
function run_gun_shop_directory() {
    $plugin = new GSD_Core();
    $plugin->run();
}

run_gun_shop_directory();
