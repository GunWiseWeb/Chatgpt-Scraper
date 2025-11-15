<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Gun_Shop_Directory
 * @subpackage Gun_Shop_Directory/includes
 */

class GSD_Admin {

    /**
     * The ID of this plugin.
     *
     * @var string
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @var string
     */
    private $version;

    /**
     * Initialize the class.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            GSD_PLUGIN_URL . 'admin/css/gsd-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            GSD_PLUGIN_URL . 'admin/js/gsd-admin.js',
            array('jquery'),
            $this->version,
            false
        );

        wp_localize_script($this->plugin_name, 'gsdAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gsd_admin_reviews'),
        ));
    }

    /**
     * Add admin menu pages.
     */
    public function add_admin_menu() {
        // Main settings page
        add_submenu_page(
            'edit.php?post_type=gsd_listing',
            __('Settings', 'gun-shop-directory'),
            __('Settings', 'gun-shop-directory'),
            'manage_options',
            'gsd-settings',
            array($this, 'render_settings_page')
        );

        // Reviews management page
        add_submenu_page(
            'edit.php?post_type=gsd_listing',
            __('Reviews', 'gun-shop-directory'),
            __('Reviews', 'gun-shop-directory'),
            'manage_options',
            'gsd-reviews',
            array($this, 'render_reviews_page')
        );
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        // General settings
        register_setting('gsd_general_settings', 'gsd_require_approval');
        register_setting('gsd_general_settings', 'gsd_allow_anonymous_reviews');
        register_setting('gsd_general_settings', 'gsd_allow_user_submissions');
        register_setting('gsd_general_settings', 'gsd_items_per_page');
        register_setting('gsd_general_settings', 'gsd_google_maps_api_key');
        register_setting('gsd_general_settings', 'gsd_enable_map');
        register_setting('gsd_general_settings', 'gsd_directory_layout');
        register_setting('gsd_general_settings', 'gsd_directory_show_search');
        register_setting('gsd_general_settings', 'gsd_directory_show_submit');

        add_settings_section(
            'gsd_general_section',
            __('General Settings', 'gun-shop-directory'),
            array($this, 'render_general_section'),
            'gsd_general_settings'
        );

        add_settings_section(
            'gsd_directory_section',
            __('Directory Display Settings', 'gun-shop-directory'),
            array($this, 'render_directory_section'),
            'gsd_general_settings'
        );

        add_settings_field(
            'gsd_require_approval',
            __('Require Review Approval', 'gun-shop-directory'),
            array($this, 'render_checkbox_field'),
            'gsd_general_settings',
            'gsd_general_section',
            array('option' => 'gsd_require_approval', 'description' => __('Reviews must be approved before appearing on the site', 'gun-shop-directory'))
        );

        add_settings_field(
            'gsd_allow_anonymous_reviews',
            __('Allow Anonymous Reviews', 'gun-shop-directory'),
            array($this, 'render_checkbox_field'),
            'gsd_general_settings',
            'gsd_general_section',
            array('option' => 'gsd_allow_anonymous_reviews', 'description' => __('Allow non-logged-in users to submit reviews', 'gun-shop-directory'))
        );

        add_settings_field(
            'gsd_allow_user_submissions',
            __('Allow User Listing Submissions', 'gun-shop-directory'),
            array($this, 'render_checkbox_field'),
            'gsd_general_settings',
            'gsd_general_section',
            array('option' => 'gsd_allow_user_submissions', 'description' => __('Allow logged-in users to submit listings (pending approval)', 'gun-shop-directory'))
        );

        add_settings_field(
            'gsd_items_per_page',
            __('Listings Per Page', 'gun-shop-directory'),
            array($this, 'render_number_field'),
            'gsd_general_settings',
            'gsd_general_section',
            array('option' => 'gsd_items_per_page', 'description' => __('Number of listings to show per page', 'gun-shop-directory'))
        );

        add_settings_field(
            'gsd_enable_map',
            __('Enable Map Display', 'gun-shop-directory'),
            array($this, 'render_checkbox_field'),
            'gsd_general_settings',
            'gsd_general_section',
            array('option' => 'gsd_enable_map', 'description' => __('Show maps on listing pages', 'gun-shop-directory'))
        );

        add_settings_field(
            'gsd_google_maps_api_key',
            __('Google Maps API Key', 'gun-shop-directory'),
            array($this, 'render_text_field'),
            'gsd_general_settings',
            'gsd_general_section',
            array('option' => 'gsd_google_maps_api_key', 'description' => __('Enter your Google Maps API key for map functionality', 'gun-shop-directory'))
        );

        // Directory display settings
        add_settings_field(
            'gsd_directory_layout',
            __('Directory Layout', 'gun-shop-directory'),
            array($this, 'render_select_field'),
            'gsd_general_settings',
            'gsd_directory_section',
            array(
                'option' => 'gsd_directory_layout',
                'description' => __('Choose the layout style for the directory page', 'gun-shop-directory'),
                'options' => array(
                    'grid-large' => __('Grid - Large Cards', 'gun-shop-directory'),
                    'grid-compact' => __('Grid - Compact Cards', 'gun-shop-directory'),
                    'grid-minimal' => __('Grid - Minimal', 'gun-shop-directory'),
                    'list-simple' => __('List - Simple', 'gun-shop-directory'),
                    'list-detailed' => __('List - Detailed', 'gun-shop-directory'),
                )
            )
        );

        add_settings_field(
            'gsd_directory_show_search',
            __('Show Search Form', 'gun-shop-directory'),
            array($this, 'render_checkbox_field'),
            'gsd_general_settings',
            'gsd_directory_section',
            array('option' => 'gsd_directory_show_search', 'description' => __('Display search form on directory page', 'gun-shop-directory'))
        );

        add_settings_field(
            'gsd_directory_show_submit',
            __('Show Submit Button', 'gun-shop-directory'),
            array($this, 'render_checkbox_field'),
            'gsd_general_settings',
            'gsd_directory_section',
            array('option' => 'gsd_directory_show_submit', 'description' => __('Display "Add Your Listing" button on directory page', 'gun-shop-directory'))
        );
    }

    /**
     * Render general settings section.
     */
    public function render_general_section() {
        echo '<p>' . __('Configure the general settings for the Gun Shop Directory plugin.', 'gun-shop-directory') . '</p>';
    }

    /**
     * Render directory settings section.
     */
    public function render_directory_section() {
        $page_id = get_option('gsd_directory_page_id');
        $page_link = $page_id ? get_edit_post_link($page_id) : '';
        echo '<p>' . __('Configure how the directory page displays.', 'gun-shop-directory');
        if ($page_link) {
            echo ' <a href="' . esc_url($page_link) . '" target="_blank">' . __('Edit Directory Page', 'gun-shop-directory') . '</a>';
        }
        echo '</p>';
    }

    /**
     * Render checkbox field.
     */
    public function render_checkbox_field($args) {
        $option = get_option($args['option'], '0');
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr($args['option']); ?>" value="1" <?php checked($option, '1'); ?>>
            <?php echo esc_html($args['description']); ?>
        </label>
        <?php
    }

    /**
     * Render text field.
     */
    public function render_text_field($args) {
        $option = get_option($args['option'], '');
        ?>
        <input type="text" name="<?php echo esc_attr($args['option']); ?>" value="<?php echo esc_attr($option); ?>" class="regular-text">
        <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php
    }

    /**
     * Render number field.
     */
    public function render_number_field($args) {
        $option = get_option($args['option'], '12');
        ?>
        <input type="number" name="<?php echo esc_attr($args['option']); ?>" value="<?php echo esc_attr($option); ?>" class="small-text" min="1">
        <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php
    }

    /**
     * Render select field.
     */
    public function render_select_field($args) {
        $option = get_option($args['option'], '');
        ?>
        <select name="<?php echo esc_attr($args['option']); ?>">
            <?php foreach ($args['options'] as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($option, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('gsd_general_settings');
                do_settings_sections('gsd_general_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render reviews management page.
     */
    public function render_reviews_page() {
        global $wpdb;

        $table = $wpdb->prefix . 'gsd_reviews';
        $status = isset($_GET['review_status']) ? sanitize_text_field($_GET['review_status']) : 'pending';

        $where = $status !== 'all' ? $wpdb->prepare("WHERE status = %s", $status) : '';
        $reviews = $wpdb->get_results("SELECT * FROM $table $where ORDER BY created_at DESC");

        // Get counts
        $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'pending'");
        $approved_count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'approved'");
        $all_count = $wpdb->get_var("SELECT COUNT(*) FROM $table");

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <ul class="subsubsub">
                <li><a href="?post_type=gsd_listing&page=gsd-reviews&review_status=pending" <?php echo $status === 'pending' ? 'class="current"' : ''; ?>>
                    <?php _e('Pending', 'gun-shop-directory'); ?> <span class="count">(<?php echo $pending_count; ?>)</span>
                </a> |</li>
                <li><a href="?post_type=gsd_listing&page=gsd-reviews&review_status=approved" <?php echo $status === 'approved' ? 'class="current"' : ''; ?>>
                    <?php _e('Approved', 'gun-shop-directory'); ?> <span class="count">(<?php echo $approved_count; ?>)</span>
                </a> |</li>
                <li><a href="?post_type=gsd_listing&page=gsd-reviews&review_status=all" <?php echo $status === 'all' ? 'class="current"' : ''; ?>>
                    <?php _e('All', 'gun-shop-directory'); ?> <span class="count">(<?php echo $all_count; ?>)</span>
                </a></li>
            </ul>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Rating', 'gun-shop-directory'); ?></th>
                        <th><?php _e('Review', 'gun-shop-directory'); ?></th>
                        <th><?php _e('Listing', 'gun-shop-directory'); ?></th>
                        <th><?php _e('Author', 'gun-shop-directory'); ?></th>
                        <th><?php _e('Date', 'gun-shop-directory'); ?></th>
                        <th><?php _e('Status', 'gun-shop-directory'); ?></th>
                        <th><?php _e('Actions', 'gun-shop-directory'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)) : ?>
                        <tr>
                            <td colspan="7"><?php _e('No reviews found.', 'gun-shop-directory'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($reviews as $review) :
                            $listing = get_post($review->listing_id);
                            $user = get_userdata($review->user_id);
                        ?>
                        <tr data-review-id="<?php echo $review->id; ?>">
                            <td><?php echo GSD_Reviews::render_stars($review->rating, false); ?></td>
                            <td>
                                <strong><?php echo esc_html($review->title); ?></strong><br>
                                <?php echo esc_html(wp_trim_words($review->content, 20)); ?>
                            </td>
                            <td>
                                <?php if ($listing) : ?>
                                    <a href="<?php echo get_edit_post_link($listing->ID); ?>"><?php echo esc_html($listing->post_title); ?></a>
                                <?php else : ?>
                                    <?php _e('(Deleted)', 'gun-shop-directory'); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $user ? esc_html($user->display_name) : __('Anonymous', 'gun-shop-directory'); ?></td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($review->created_at)); ?></td>
                            <td><?php echo ucfirst($review->status); ?></td>
                            <td>
                                <?php if ($review->status === 'pending') : ?>
                                    <button class="button button-primary gsd-approve-review" data-review-id="<?php echo $review->id; ?>">
                                        <?php _e('Approve', 'gun-shop-directory'); ?>
                                    </button>
                                <?php endif; ?>
                                <button class="button gsd-delete-review" data-review-id="<?php echo $review->id; ?>">
                                    <?php _e('Delete', 'gun-shop-directory'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
