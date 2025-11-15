<?php
/**
 * The core plugin class.
 *
 * @package    Gun_Shop_Directory
 * @subpackage Gun_Shop_Directory/includes
 */

class GSD_Core {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @var GSD_Loader
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @var string
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @var string
     */
    protected $version;

    /**
     * Initialize the plugin.
     */
    public function __construct() {
        $this->version = GSD_VERSION;
        $this->plugin_name = 'gun-shop-directory';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies.
     */
    private function load_dependencies() {
        require_once GSD_PLUGIN_DIR . 'includes/class-gsd-post-types.php';
        require_once GSD_PLUGIN_DIR . 'includes/class-gsd-reviews.php';
        require_once GSD_PLUGIN_DIR . 'includes/class-gsd-admin.php';
        require_once GSD_PLUGIN_DIR . 'includes/class-gsd-public.php';
    }

    /**
     * Register all admin hooks.
     */
    private function define_admin_hooks() {
        $admin = new GSD_Admin($this->get_plugin_name(), $this->get_version());

        add_action('admin_enqueue_scripts', array($admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_scripts'));
        add_action('admin_menu', array($admin, 'add_admin_menu'));
        add_action('admin_init', array($admin, 'register_settings'));
    }

    /**
     * Register all public hooks.
     */
    private function define_public_hooks() {
        $public = new GSD_Public($this->get_plugin_name(), $this->get_version());

        add_action('wp_enqueue_scripts', array($public, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($public, 'enqueue_scripts'));
        add_filter('template_include', array($public, 'template_loader'));

        // Shortcodes
        add_shortcode('gsd_directory', array($public, 'directory_shortcode')); // New unified shortcode
        add_shortcode('gsd_listings', array($public, 'listings_shortcode'));
        add_shortcode('gsd_search', array($public, 'search_shortcode'));
        add_shortcode('gsd_submit_listing', array($public, 'submit_listing_shortcode'));

        // Handle listing submission
        add_action('template_redirect', array($public, 'handle_listing_submission'));

        // AJAX handlers
        add_action('wp_ajax_gsd_submit_claim', array($public, 'ajax_submit_claim'));
        add_action('wp_ajax_nopriv_gsd_submit_claim', array($public, 'ajax_submit_claim'));
    }

    /**
     * Run the loader to execute all hooks.
     */
    public function run() {
        $post_types = new GSD_Post_Types();
        $reviews = new GSD_Reviews();
    }

    /**
     * The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}
