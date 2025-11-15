<?php
/**
 * Plugin Name: Gun Shop Directory
 * Plugin URI: https://github.com/GunWiseWeb/gun-shop-directory
 * Description: A professional business directory plugin for gun shops with ratings and reviews.
 * Version: 2.0.0
 * Author: GunWise
 * Author URI: https://gunwise.com
 * License: GPL-2.0+
 * Text Domain: gun-shop-directory
 */

if (!defined('WPINC')) {
    die;
}

define('GSD_VERSION', '2.0.0');
define('GSD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GSD_PLUGIN_URL', plugin_dir_url(__FILE__));

// Activation
register_activation_hook(__FILE__, 'gsd_activate');
function gsd_activate() {
    require_once GSD_PLUGIN_DIR . 'includes/class-gsd-activator.php';
    GSD_Activator::activate();
    flush_rewrite_rules();
}

// Deactivation
register_deactivation_hook(__FILE__, 'gsd_deactivate');
function gsd_deactivate() {
    flush_rewrite_rules();
}

// Load core
require GSD_PLUGIN_DIR . 'includes/class-gsd-post-types.php';
require GSD_PLUGIN_DIR . 'includes/class-gsd-reviews.php';
require GSD_PLUGIN_DIR . 'includes/class-gsd-public.php';
require GSD_PLUGIN_DIR . 'includes/class-gsd-admin.php';
require GSD_PLUGIN_DIR . 'includes/class-gsd-activator.php';

// Initialize
add_action('plugins_loaded', 'gsd_init');
function gsd_init() {
    new GSD_Post_Types();
    new GSD_Reviews();

    $public = new GSD_Public('gun-shop-directory', GSD_VERSION);
    add_action('wp_enqueue_scripts', array($public, 'enqueue_styles'));
    add_action('wp_enqueue_scripts', array($public, 'enqueue_scripts'));
    add_action('init', array($public, 'register_shortcodes'));
    add_action('wp_ajax_gsd_submit_claim', array($public, 'handle_claim_submission'));
    add_action('wp_ajax_nopriv_gsd_submit_claim', array($public, 'handle_claim_submission'));
    add_action('wp_ajax_gsd_submit_review', array($public, 'handle_review_submission'));
    add_action('wp_ajax_nopriv_gsd_submit_review', array($public, 'handle_review_submission'));

    $admin = new GSD_Admin('gun-shop-directory', GSD_VERSION);
    add_action('admin_enqueue_scripts', array($admin, 'enqueue_styles'));
    add_action('admin_enqueue_scripts', array($admin, 'enqueue_scripts'));
    add_action('admin_menu', array($admin, 'add_admin_menu'));
    add_action('admin_init', array($admin, 'register_settings'));
    add_action('wp_ajax_gsd_approve_claim', array($admin, 'approve_claim'));
    add_action('wp_ajax_gsd_reject_claim', array($admin, 'reject_claim'));
}
