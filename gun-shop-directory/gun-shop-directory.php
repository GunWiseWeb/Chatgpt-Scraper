<?php
/**
 * Plugin Name: Gun Shop Directory
 * Plugin URI: https://github.com/GunWiseWeb/gun-shop-directory
 * Description: A professional business directory plugin for gun shops with ratings and reviews, similar to Yelp.
 * Version: 1.3.0
 * Author: GunWise
 * Author URI: https://gunwise.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: gun-shop-directory
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('GSD_VERSION', '1.3.0');
define('GSD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GSD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GSD_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_gun_shop_directory() {
    require_once GSD_PLUGIN_DIR . 'includes/class-gsd-activator.php';
    GSD_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_gun_shop_directory() {
    require_once GSD_PLUGIN_DIR . 'includes/class-gsd-deactivator.php';
    GSD_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_gun_shop_directory');
register_deactivation_hook(__FILE__, 'deactivate_gun_shop_directory');

/**
 * The core plugin class.
 */
require GSD_PLUGIN_DIR . 'includes/class-gsd-core.php';

/**
 * Begins execution of the plugin.
 */
function run_gun_shop_directory() {
    $plugin = new GSD_Core();
    $plugin->run();
}

run_gun_shop_directory();
