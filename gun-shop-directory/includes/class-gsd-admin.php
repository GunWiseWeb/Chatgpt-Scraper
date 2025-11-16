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
        // Get pending counts for notification bubbles
        $pending_reviews = $this->get_pending_reviews_count();

        // Main settings page
        add_submenu_page(
            'edit.php?post_type=gsd_listing',
            __('Settings', 'gun-shop-directory'),
            __('Settings', 'gun-shop-directory'),
            'manage_options',
            'gsd-settings',
            array($this, 'render_settings_page')
        );

        // Reviews management page with notification bubble
        $reviews_menu_title = __('Reviews', 'gun-shop-directory');
        if ($pending_reviews > 0) {
            $reviews_menu_title .= ' <span class="awaiting-mod count-' . $pending_reviews . '"><span class="pending-count">' . number_format_i18n($pending_reviews) . '</span></span>';
        }

        add_submenu_page(
            'edit.php?post_type=gsd_listing',
            __('Reviews', 'gun-shop-directory'),
            $reviews_menu_title,
            'manage_options',
            'gsd-reviews',
            array($this, 'render_reviews_page')
        );

        // FFL Import page
        add_submenu_page(
            'edit.php?post_type=gsd_listing',
            __('Import FFLs', 'gun-shop-directory'),
            __('Import FFLs', 'gun-shop-directory'),
            'manage_options',
            'gsd-import',
            array($this, 'render_import_page')
        );
    }

    /**
     * Get count of pending reviews.
     */
    private function get_pending_reviews_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gsd_reviews';
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'pending'");
    }

    /**
     * Add notification bubble to main Gun Shops menu for pending listings.
     */
    public function add_pending_listings_bubble($menu) {
        global $submenu;

        // Count pending listings
        $pending_count = wp_count_posts('gsd_listing');
        $pending_listings = isset($pending_count->pending) ? $pending_count->pending : 0;

        if ($pending_listings > 0) {
            // Find the Gun Shops menu item
            foreach ($menu as $key => $item) {
                if ($item[2] === 'edit.php?post_type=gsd_listing') {
                    $menu[$key][0] .= ' <span class="awaiting-mod count-' . $pending_listings . '"><span class="pending-count">' . number_format_i18n($pending_listings) . '</span></span>';
                    break;
                }
            }
        }

        return $menu;
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
        $archive_url = get_post_type_archive_link('gsd_listing');
        echo '<p>' . __('Configure how the directory displays.', 'gun-shop-directory');
        if ($archive_url) {
            echo ' ' . sprintf(__('The directory is available at: <a href="%s" target="_blank">%s</a>', 'gun-shop-directory'), esc_url($archive_url), esc_url($archive_url));
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

    /**
     * Render import page
     */
    public function render_import_page() {
        // Handle form submission
        if (isset($_POST['gsd_import_submit']) && check_admin_referer('gsd_import_ffls', 'gsd_import_nonce')) {
            $this->process_import();
        }

        // Handle fix business types
        if (isset($_POST['gsd_fix_types_submit']) && check_admin_referer('gsd_fix_types', 'gsd_fix_types_nonce')) {
            $this->process_fix_business_types();
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Import FFL Listings', 'gun-shop-directory'); ?></h1>

            <div class="card" style="max-width: 800px;">
                <h2><?php _e('ATF FFL Database Import', 'gun-shop-directory'); ?></h2>
                <p><?php _e('Import gun shop listings from the ATF Federal Firearms License database.', 'gun-shop-directory'); ?></p>

                <h3><?php _e('How to get the FFL data:', 'gun-shop-directory'); ?></h3>
                <ol>
                    <li><?php _e('Visit the ATF website:', 'gun-shop-directory'); ?> <a href="https://www.atf.gov/firearms/listing-federal-firearms-licensees" target="_blank">https://www.atf.gov/firearms/listing-federal-firearms-licensees</a></li>
                    <li><?php _e('Download the "Complete Listing" file (Excel/XLSX format)', 'gun-shop-directory'); ?></li>
                    <li><?php _e('Open the file in Excel or Google Sheets', 'gun-shop-directory'); ?></li>
                    <li><?php _e('Save/Export as CSV format', 'gun-shop-directory'); ?></li>
                    <li><?php _e('Upload the CSV file below', 'gun-shop-directory'); ?></li>
                </ol>

                <h3><?php _e('Expected CSV Columns:', 'gun-shop-directory'); ?></h3>
                <p><?php _e('The CSV should contain these column headers:', 'gun-shop-directory'); ?></p>
                <ul>
                    <li><strong>License Number</strong> - <?php _e('FFL number', 'gun-shop-directory'); ?></li>
                    <li><strong>Business Name</strong> - <?php _e('Name of the business', 'gun-shop-directory'); ?></li>
                    <li><strong>Premise Street</strong> - <?php _e('Street address', 'gun-shop-directory'); ?></li>
                    <li><strong>Premise City</strong> - <?php _e('City', 'gun-shop-directory'); ?></li>
                    <li><strong>Premise State</strong> - <?php _e('State', 'gun-shop-directory'); ?></li>
                    <li><strong>Premise Zip Code</strong> - <?php _e('ZIP code', 'gun-shop-directory'); ?></li>
                    <li><strong>License Type</strong> - <?php _e('Type of license', 'gun-shop-directory'); ?></li>
                    <li><?php _e('Optional: Expiration Date, Business Phone', 'gun-shop-directory'); ?></li>
                </ul>

                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('gsd_import_ffls', 'gsd_import_nonce'); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="gsd_import_file"><?php _e('CSV File', 'gun-shop-directory'); ?></label>
                            </th>
                            <td>
                                <input type="file" name="gsd_import_file" id="gsd_import_file" accept=".csv" required>
                                <p class="description"><?php _e('Upload the CSV file exported from the ATF Excel file.', 'gun-shop-directory'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="gsd_import_status"><?php _e('Import Status', 'gun-shop-directory'); ?></label>
                            </th>
                            <td>
                                <select name="gsd_import_status" id="gsd_import_status">
                                    <option value="publish"><?php _e('Published (listings go live immediately)', 'gun-shop-directory'); ?></option>
                                    <option value="pending"><?php _e('Pending (require review before publishing)', 'gun-shop-directory'); ?></option>
                                </select>
                                <p class="description"><?php _e('Choose whether imported listings should be published immediately or pending review.', 'gun-shop-directory'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="gsd_import_update"><?php _e('Update Existing', 'gun-shop-directory'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="gsd_import_update" id="gsd_import_update" value="1">
                                    <?php _e('Update existing listings if FFL number already exists', 'gun-shop-directory'); ?>
                                </label>
                                <p class="description"><?php _e('If unchecked, existing listings will be skipped.', 'gun-shop-directory'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" name="gsd_import_submit" class="button button-primary button-large">
                            <?php _e('Import FFLs', 'gun-shop-directory'); ?>
                        </button>
                    </p>
                </form>

                <div class="notice notice-info inline">
                    <p><strong><?php _e('Note:', 'gun-shop-directory'); ?></strong> <?php _e('Large imports may take several minutes. Do not close this page during import.', 'gun-shop-directory'); ?></p>
                </div>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php _e('Fix Already-Imported Listings', 'gun-shop-directory'); ?></h2>
                <p><?php _e('If you imported FFLs before version 2.2.1, their business types may be incorrect (set to "both"). Use this tool to fix them.', 'gun-shop-directory'); ?></p>

                <h3><?php _e('What this will do:', 'gun-shop-directory'); ?></h3>
                <ul>
                    <li><?php _e('Update all imported FFL listings to use correct business types based on license type', 'gun-shop-directory'); ?></li>
                    <li><?php _e('Delete Type 03 collector listings (not retail businesses)', 'gun-shop-directory'); ?></li>
                    <li><?php _e('Set dealers (Type 01, 02, 09) to "Brick & Mortar"', 'gun-shop-directory'); ?></li>
                    <li><?php _e('Set manufacturers/importers (Type 06-08, 10-11) to "Brick & Mortar"', 'gun-shop-directory'); ?></li>
                </ul>

                <form method="post">
                    <?php wp_nonce_field('gsd_fix_types', 'gsd_fix_types_nonce'); ?>

                    <p class="submit">
                        <button type="submit" name="gsd_fix_types_submit" class="button button-secondary button-large" onclick="return confirm('<?php esc_attr_e('This will update all imported FFL listings and delete Type 03 collectors. Continue?', 'gun-shop-directory'); ?>');">
                            <?php _e('Fix Business Types', 'gun-shop-directory'); ?>
                        </button>
                    </p>
                </form>

                <div class="notice notice-warning inline">
                    <p><strong><?php _e('Warning:', 'gun-shop-directory'); ?></strong> <?php _e('This will permanently delete Type 03 collector listings. Make sure you have a backup if needed.', 'gun-shop-directory'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Process FFL import
     */
    private function process_import() {
        // Check if file was uploaded
        if (!isset($_FILES['gsd_import_file']) || $_FILES['gsd_import_file']['error'] !== UPLOAD_ERR_OK) {
            add_settings_error(
                'gsd_import',
                'file_upload_error',
                __('Error uploading file. Please try again.', 'gun-shop-directory'),
                'error'
            );
            return;
        }

        $file = $_FILES['gsd_import_file'];
        $import_status = isset($_POST['gsd_import_status']) ? $_POST['gsd_import_status'] : 'publish';

        // Validate file type
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_ext !== 'csv') {
            add_settings_error(
                'gsd_import',
                'invalid_file_type',
                __('Please upload a CSV file.', 'gun-shop-directory'),
                'error'
            );
            return;
        }

        // Process import
        $importer = new GSD_Importer();

        // Increase time limit for large imports
        set_time_limit(300); // 5 minutes

        $results = $importer->import_from_csv($file['tmp_name'], array(), 50, $import_status);

        // Display results
        if ($results['success']) {
            $message = sprintf(
                __('Import completed! Imported: %d, Updated: %d, Skipped: %d, Errors: %d', 'gun-shop-directory'),
                $results['imported'],
                $results['updated'],
                $results['skipped'],
                $results['errors']
            );
            add_settings_error('gsd_import', 'import_success', $message, 'success');

            if (!empty($results['messages'])) {
                foreach ($results['messages'] as $msg) {
                    add_settings_error('gsd_import', 'import_message', $msg, 'warning');
                }
            }
        } else {
            add_settings_error(
                'gsd_import',
                'import_failed',
                $results['message'],
                'error'
            );
        }

        settings_errors('gsd_import');
    }

    /**
     * Process fix business types for imported listings
     */
    private function process_fix_business_types() {
        $importer = new GSD_Importer();

        // Increase time limit for large operations
        set_time_limit(300); // 5 minutes

        $results = $importer->fix_imported_business_types();

        // Display results
        if ($results['success']) {
            $message = sprintf(
                __('Business types fixed! Updated: %d, Deleted: %d, Skipped: %d', 'gun-shop-directory'),
                $results['updated'],
                $results['deleted'],
                $results['skipped']
            );
            add_settings_error('gsd_import', 'fix_success', $message, 'success');

            if (isset($results['message'])) {
                add_settings_error('gsd_import', 'fix_info', $results['message'], 'info');
            }
        } else {
            add_settings_error(
                'gsd_import',
                'fix_failed',
                isset($results['message']) ? $results['message'] : __('An error occurred.', 'gun-shop-directory'),
                'error'
            );
        }

        settings_errors('gsd_import');
    }
}
