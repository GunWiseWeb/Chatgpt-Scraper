<?php
/**
 * The core plugin class.
 */
class GSD_Core {

    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->version = GSD_VERSION;
        $this->plugin_name = 'gun-shop-directory';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once GSD_PLUGIN_DIR . 'includes/class-gsd-post-types.php';
        require_once GSD_PLUGIN_DIR . 'includes/class-gsd-reviews.php';
        require_once GSD_PLUGIN_DIR . 'includes/class-gsd-admin.php';
        require_once GSD_PLUGIN_DIR . 'includes/class-gsd-public.php';
        require_once GSD_PLUGIN_DIR . 'includes/class-gsd-importer.php';
    }

    private function define_admin_hooks() {
        $admin = new GSD_Admin($this->get_plugin_name(), $this->get_version());

        add_action('admin_enqueue_scripts', array($admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_scripts'));
        add_action('admin_menu', array($admin, 'add_admin_menu'));
        add_action('admin_init', array($admin, 'register_settings'));
        add_action('wp_ajax_gsd_approve_claim', array($admin, 'approve_claim'));
        add_action('wp_ajax_gsd_reject_claim', array($admin, 'reject_claim'));

        // Add notification bubbles to admin menu
        add_filter('add_menu_classes', array($admin, 'add_pending_listings_bubble'));
    }

    private function define_public_hooks() {
        $public = new GSD_Public($this->get_plugin_name(), $this->get_version());

        add_action('wp_enqueue_scripts', array($public, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($public, 'enqueue_scripts'));
        add_filter('template_include', array($public, 'template_loader'));

        // Shortcodes
        add_shortcode('gsd_directory', array($public, 'directory_shortcode'));
        add_shortcode('gsd_listings', array($public, 'listings_shortcode'));
        add_shortcode('gsd_search', array($public, 'search_shortcode'));
        add_shortcode('gsd_submit_listing', array($public, 'submit_listing_shortcode'));

        // Handle listing submission
        add_action('template_redirect', array($public, 'handle_listing_submission'));

        // AJAX handlers
        add_action('wp_ajax_gsd_submit_claim', array($public, 'ajax_submit_claim'));
        add_action('wp_ajax_nopriv_gsd_submit_claim', array($public, 'ajax_submit_claim'));
        add_action('wp_ajax_gsd_search_listings', array($public, 'ajax_search_listings'));
        add_action('wp_ajax_nopriv_gsd_search_listings', array($public, 'ajax_search_listings'));
    }

    public function run() {
        new GSD_Post_Types();
        new GSD_Reviews();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }
}
