<?php
/**
 * Register custom post types and taxonomies.
 *
 * @package    Gun_Shop_Directory
 * @subpackage Gun_Shop_Directory/includes
 */

class GSD_Post_Types {

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('init', array($this, 'register_query_vars'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_gsd_listing', array($this, 'save_listing_meta'));
        add_action('pre_get_posts', array($this, 'modify_search_query'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'prevent_search_404'));
    }

    /**
     * Register the Gun Shop Listing post type.
     */
    public function register_post_types() {
        $labels = array(
            'name'                  => _x('Gun Shop Listings', 'Post Type General Name', 'gun-shop-directory'),
            'singular_name'         => _x('Gun Shop Listing', 'Post Type Singular Name', 'gun-shop-directory'),
            'menu_name'             => __('Gun Shops', 'gun-shop-directory'),
            'name_admin_bar'        => __('Gun Shop', 'gun-shop-directory'),
            'archives'              => __('Listing Archives', 'gun-shop-directory'),
            'attributes'            => __('Listing Attributes', 'gun-shop-directory'),
            'parent_item_colon'     => __('Parent Listing:', 'gun-shop-directory'),
            'all_items'             => __('All Listings', 'gun-shop-directory'),
            'add_new_item'          => __('Add New Listing', 'gun-shop-directory'),
            'add_new'               => __('Add New', 'gun-shop-directory'),
            'new_item'              => __('New Listing', 'gun-shop-directory'),
            'edit_item'             => __('Edit Listing', 'gun-shop-directory'),
            'update_item'           => __('Update Listing', 'gun-shop-directory'),
            'view_item'             => __('View Listing', 'gun-shop-directory'),
            'view_items'            => __('View Listings', 'gun-shop-directory'),
            'search_items'          => __('Search Listing', 'gun-shop-directory'),
            'not_found'             => __('Not found', 'gun-shop-directory'),
            'not_found_in_trash'    => __('Not found in Trash', 'gun-shop-directory'),
        );

        $args = array(
            'label'                 => __('Gun Shop Listing', 'gun-shop-directory'),
            'description'           => __('Gun shop business listings', 'gun-shop-directory'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail', 'revisions'),
            'taxonomies'            => array('gsd_category', 'gsd_location'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-store',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => 'gun-shops',
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rewrite'               => array('slug' => 'gun-shop', 'with_front' => false),
        );

        register_post_type('gsd_listing', $args);
    }

    /**
     * Register query vars.
     */
    public function register_query_vars() {
        // Query vars are registered through add_query_vars filter
    }

    /**
     * Add custom query vars.
     */
    public function add_query_vars($vars) {
        $vars[] = 'gsd_search';
        $vars[] = 'gsd_location';
        $vars[] = 'gsd_type';
        $vars[] = 'gsd_category';
        return $vars;
    }

    /**
     * Register taxonomies for the Gun Shop Listing post type.
     */
    public function register_taxonomies() {
        // Categories taxonomy
        $category_labels = array(
            'name'              => _x('Categories', 'taxonomy general name', 'gun-shop-directory'),
            'singular_name'     => _x('Category', 'taxonomy singular name', 'gun-shop-directory'),
            'search_items'      => __('Search Categories', 'gun-shop-directory'),
            'all_items'         => __('All Categories', 'gun-shop-directory'),
            'parent_item'       => __('Parent Category', 'gun-shop-directory'),
            'parent_item_colon' => __('Parent Category:', 'gun-shop-directory'),
            'edit_item'         => __('Edit Category', 'gun-shop-directory'),
            'update_item'       => __('Update Category', 'gun-shop-directory'),
            'add_new_item'      => __('Add New Category', 'gun-shop-directory'),
            'new_item_name'     => __('New Category Name', 'gun-shop-directory'),
            'menu_name'         => __('Categories', 'gun-shop-directory'),
        );

        register_taxonomy('gsd_category', array('gsd_listing'), array(
            'hierarchical'      => true,
            'labels'            => $category_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'shop-category'),
            'show_in_rest'      => true,
        ));

        // Location taxonomy
        $location_labels = array(
            'name'              => _x('Locations', 'taxonomy general name', 'gun-shop-directory'),
            'singular_name'     => _x('Location', 'taxonomy singular name', 'gun-shop-directory'),
            'search_items'      => __('Search Locations', 'gun-shop-directory'),
            'all_items'         => __('All Locations', 'gun-shop-directory'),
            'parent_item'       => __('Parent Location', 'gun-shop-directory'),
            'parent_item_colon' => __('Parent Location:', 'gun-shop-directory'),
            'edit_item'         => __('Edit Location', 'gun-shop-directory'),
            'update_item'       => __('Update Location', 'gun-shop-directory'),
            'add_new_item'      => __('Add New Location', 'gun-shop-directory'),
            'new_item_name'     => __('New Location Name', 'gun-shop-directory'),
            'menu_name'         => __('Locations', 'gun-shop-directory'),
        );

        register_taxonomy('gsd_location', array('gsd_listing'), array(
            'hierarchical'      => true,
            'labels'            => $location_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'location'),
            'show_in_rest'      => true,
        ));
    }

    /**
     * Add meta boxes for business details.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'gsd_business_info',
            __('Business Information', 'gun-shop-directory'),
            array($this, 'render_business_info_meta_box'),
            'gsd_listing',
            'normal',
            'high'
        );

        add_meta_box(
            'gsd_contact_info',
            __('Contact Information', 'gun-shop-directory'),
            array($this, 'render_contact_info_meta_box'),
            'gsd_listing',
            'normal',
            'high'
        );

        add_meta_box(
            'gsd_hours',
            __('Business Hours', 'gun-shop-directory'),
            array($this, 'render_hours_meta_box'),
            'gsd_listing',
            'normal',
            'default'
        );

        add_meta_box(
            'gsd_rating_stats',
            __('Rating Statistics', 'gun-shop-directory'),
            array($this, 'render_rating_stats_meta_box'),
            'gsd_listing',
            'side',
            'default'
        );
    }

    /**
     * Render Business Information meta box.
     */
    public function render_business_info_meta_box($post) {
        wp_nonce_field('gsd_save_listing_meta', 'gsd_listing_nonce');

        $business_type = get_post_meta($post->ID, '_gsd_business_type', true);
        $address = get_post_meta($post->ID, '_gsd_address', true);
        $city = get_post_meta($post->ID, '_gsd_city', true);
        $state = get_post_meta($post->ID, '_gsd_state', true);
        $zip = get_post_meta($post->ID, '_gsd_zip', true);
        $country = get_post_meta($post->ID, '_gsd_country', true) ?: 'United States';
        $latitude = get_post_meta($post->ID, '_gsd_latitude', true);
        $longitude = get_post_meta($post->ID, '_gsd_longitude', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="gsd_business_type"><?php _e('Business Type', 'gun-shop-directory'); ?></label></th>
                <td>
                    <select name="gsd_business_type" id="gsd_business_type" class="regular-text">
                        <option value="brick_mortar" <?php selected($business_type, 'brick_mortar'); ?>><?php _e('Brick & Mortar', 'gun-shop-directory'); ?></option>
                        <option value="ecommerce" <?php selected($business_type, 'ecommerce'); ?>><?php _e('E-commerce Only', 'gun-shop-directory'); ?></option>
                        <option value="both" <?php selected($business_type, 'both'); ?>><?php _e('Both', 'gun-shop-directory'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="gsd_address"><?php _e('Street Address', 'gun-shop-directory'); ?></label></th>
                <td><input type="text" name="gsd_address" id="gsd_address" value="<?php echo esc_attr($address); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="gsd_city"><?php _e('City', 'gun-shop-directory'); ?></label></th>
                <td><input type="text" name="gsd_city" id="gsd_city" value="<?php echo esc_attr($city); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="gsd_state"><?php _e('State', 'gun-shop-directory'); ?></label></th>
                <td><input type="text" name="gsd_state" id="gsd_state" value="<?php echo esc_attr($state); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="gsd_zip"><?php _e('ZIP Code', 'gun-shop-directory'); ?></label></th>
                <td><input type="text" name="gsd_zip" id="gsd_zip" value="<?php echo esc_attr($zip); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="gsd_country"><?php _e('Country', 'gun-shop-directory'); ?></label></th>
                <td><input type="text" name="gsd_country" id="gsd_country" value="<?php echo esc_attr($country); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="gsd_latitude"><?php _e('Latitude', 'gun-shop-directory'); ?></label></th>
                <td><input type="text" name="gsd_latitude" id="gsd_latitude" value="<?php echo esc_attr($latitude); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="gsd_longitude"><?php _e('Longitude', 'gun-shop-directory'); ?></label></th>
                <td><input type="text" name="gsd_longitude" id="gsd_longitude" value="<?php echo esc_attr($longitude); ?>" class="regular-text"></td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render Contact Information meta box.
     */
    public function render_contact_info_meta_box($post) {
        $phone = get_post_meta($post->ID, '_gsd_phone', true);
        $email = get_post_meta($post->ID, '_gsd_email', true);
        $website = get_post_meta($post->ID, '_gsd_website', true);
        $facebook = get_post_meta($post->ID, '_gsd_facebook', true);
        $twitter = get_post_meta($post->ID, '_gsd_twitter', true);
        $instagram = get_post_meta($post->ID, '_gsd_instagram', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="gsd_phone"><?php _e('Phone', 'gun-shop-directory'); ?></label></th>
                <td><input type="text" name="gsd_phone" id="gsd_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="gsd_email"><?php _e('Email', 'gun-shop-directory'); ?></label></th>
                <td><input type="email" name="gsd_email" id="gsd_email" value="<?php echo esc_attr($email); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="gsd_website"><?php _e('Website', 'gun-shop-directory'); ?></label></th>
                <td><input type="url" name="gsd_website" id="gsd_website" value="<?php echo esc_attr($website); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="gsd_facebook"><?php _e('Facebook', 'gun-shop-directory'); ?></label></th>
                <td><input type="url" name="gsd_facebook" id="gsd_facebook" value="<?php echo esc_attr($facebook); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="gsd_twitter"><?php _e('Twitter/X', 'gun-shop-directory'); ?></label></th>
                <td><input type="url" name="gsd_twitter" id="gsd_twitter" value="<?php echo esc_attr($twitter); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="gsd_instagram"><?php _e('Instagram', 'gun-shop-directory'); ?></label></th>
                <td><input type="url" name="gsd_instagram" id="gsd_instagram" value="<?php echo esc_attr($instagram); ?>" class="regular-text"></td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render Business Hours meta box.
     */
    public function render_hours_meta_box($post) {
        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
        $day_labels = array(
            'monday' => __('Monday', 'gun-shop-directory'),
            'tuesday' => __('Tuesday', 'gun-shop-directory'),
            'wednesday' => __('Wednesday', 'gun-shop-directory'),
            'thursday' => __('Thursday', 'gun-shop-directory'),
            'friday' => __('Friday', 'gun-shop-directory'),
            'saturday' => __('Saturday', 'gun-shop-directory'),
            'sunday' => __('Sunday', 'gun-shop-directory'),
        );
        ?>
        <table class="form-table">
            <?php foreach ($days as $day) :
                $open = get_post_meta($post->ID, "_gsd_hours_{$day}_open", true);
                $close = get_post_meta($post->ID, "_gsd_hours_{$day}_close", true);
                $closed = get_post_meta($post->ID, "_gsd_hours_{$day}_closed", true);
            ?>
            <tr>
                <th><label><?php echo $day_labels[$day]; ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="gsd_hours_<?php echo $day; ?>_closed" value="1" <?php checked($closed, '1'); ?>>
                        <?php _e('Closed', 'gun-shop-directory'); ?>
                    </label>
                    <br>
                    <label>
                        <?php _e('Open:', 'gun-shop-directory'); ?>
                        <input type="time" name="gsd_hours_<?php echo $day; ?>_open" value="<?php echo esc_attr($open); ?>">
                    </label>
                    <label>
                        <?php _e('Close:', 'gun-shop-directory'); ?>
                        <input type="time" name="gsd_hours_<?php echo $day; ?>_close" value="<?php echo esc_attr($close); ?>">
                    </label>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php
    }

    /**
     * Render Rating Statistics meta box.
     */
    public function render_rating_stats_meta_box($post) {
        global $wpdb;

        $table = $wpdb->prefix . 'gsd_reviews';
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            FROM $table
            WHERE listing_id = %d AND status = 'approved'",
            $post->ID
        ));

        if ($stats && $stats->total_reviews > 0) {
            ?>
            <div class="gsd-rating-stats">
                <p><strong><?php _e('Average Rating:', 'gun-shop-directory'); ?></strong> <?php echo number_format($stats->average_rating, 1); ?> / 5.0</p>
                <p><strong><?php _e('Total Reviews:', 'gun-shop-directory'); ?></strong> <?php echo $stats->total_reviews; ?></p>
                <hr>
                <p>5 ⭐: <?php echo $stats->five_star; ?></p>
                <p>4 ⭐: <?php echo $stats->four_star; ?></p>
                <p>3 ⭐: <?php echo $stats->three_star; ?></p>
                <p>2 ⭐: <?php echo $stats->two_star; ?></p>
                <p>1 ⭐: <?php echo $stats->one_star; ?></p>
            </div>
            <?php
        } else {
            echo '<p>' . __('No reviews yet.', 'gun-shop-directory') . '</p>';
        }
    }

    /**
     * Save listing meta data.
     */
    public function save_listing_meta($post_id) {
        // Check nonce
        if (!isset($_POST['gsd_listing_nonce']) || !wp_verify_nonce($_POST['gsd_listing_nonce'], 'gsd_save_listing_meta')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save business info
        $fields = array(
            'gsd_business_type', 'gsd_address', 'gsd_city', 'gsd_state', 'gsd_zip', 'gsd_country',
            'gsd_latitude', 'gsd_longitude', 'gsd_phone', 'gsd_email', 'gsd_website',
            'gsd_facebook', 'gsd_twitter', 'gsd_instagram'
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }

        // Save business hours
        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
        foreach ($days as $day) {
            update_post_meta($post_id, "_gsd_hours_{$day}_open", sanitize_text_field($_POST["gsd_hours_{$day}_open"] ?? ''));
            update_post_meta($post_id, "_gsd_hours_{$day}_close", sanitize_text_field($_POST["gsd_hours_{$day}_close"] ?? ''));
            update_post_meta($post_id, "_gsd_hours_{$day}_closed", isset($_POST["gsd_hours_{$day}_closed"]) ? '1' : '0');
        }
    }

    /**
     * Modify search query to include location search by zip, city, and state.
     */
    public function modify_search_query($query) {
        // Only modify the main query on the frontend for gsd_listing post type
        if (is_admin() || !$query->is_main_query()) {
            return;
        }

        // Check if we have gun shop search parameters
        $has_search_params = (
            !empty($_GET['gsd_search']) ||
            !empty($_GET['gsd_location']) ||
            !empty($_GET['gsd_type']) ||
            !empty($_GET['gsd_category'])
        );

        // Check if this is a gun shop search (either archive or has our search parameters)
        $is_gun_shop_search = (
            is_post_type_archive('gsd_listing') ||
            is_tax('gsd_category') ||
            is_tax('gsd_location') ||
            ($has_search_params && strpos($_SERVER['REQUEST_URI'], 'gun-shops') !== false)
        );

        if (!$is_gun_shop_search) {
            return;
        }

        // Force this to be a gsd_listing query
        if ($has_search_params) {
            $query->set('post_type', 'gsd_listing');
        }

        // Handle location search with post__in
        $location_search = isset($_GET['gsd_location']) ? sanitize_text_field($_GET['gsd_location']) : '';
        if (!empty($location_search)) {
            global $wpdb;

            // Debug: Check what's in the database
            $debug_query = $wpdb->prepare(
                "SELECT pm.post_id, pm.meta_key, pm.meta_value
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE p.post_type = 'gsd_listing'
                AND p.post_status = 'publish'
                AND pm.meta_key IN ('_gsd_city', '_gsd_state', '_gsd_zip')
                ORDER BY pm.post_id"
            );
            $all_meta = $wpdb->get_results($debug_query);

            // Add to page as HTML comment for debugging
            if (isset($_GET['debug'])) {
                echo "<!-- DEBUG: All location meta:\n";
                foreach ($all_meta as $meta) {
                    echo "Post ID: {$meta->post_id}, Key: {$meta->meta_key}, Value: {$meta->meta_value}\n";
                }
                echo "Searching for: {$location_search}\n-->";
            }

            $post_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT p.ID
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'gsd_listing'
                AND p.post_status = 'publish'
                AND (
                    (pm.meta_key = '_gsd_city' AND pm.meta_value LIKE %s) OR
                    (pm.meta_key = '_gsd_state' AND pm.meta_value LIKE %s) OR
                    (pm.meta_key = '_gsd_zip' AND pm.meta_value LIKE %s)
                )",
                '%' . $wpdb->esc_like($location_search) . '%',
                '%' . $wpdb->esc_like($location_search) . '%',
                '%' . $wpdb->esc_like($location_search) . '%'
            ));

            if (isset($_GET['debug'])) {
                echo "<!-- DEBUG: Found post IDs: " . print_r($post_ids, true) . " -->";
            }

            if (!empty($post_ids)) {
                $query->set('post__in', $post_ids);
            } else {
                // No results found, set impossible condition
                $query->set('post__in', array(0));
            }
        }

        // Handle general search parameter
        $general_search = isset($_GET['gsd_search']) ? sanitize_text_field($_GET['gsd_search']) : get_query_var('gsd_search');
        if (!empty($general_search)) {
            $query->set('s', $general_search);
        }

        // Handle business type filter
        $business_type = isset($_GET['gsd_type']) ? sanitize_text_field($_GET['gsd_type']) : get_query_var('gsd_type');
        if (!empty($business_type)) {
            $meta_query = $query->get('meta_query') ?: array();
            $meta_query[] = array(
                'key' => '_gsd_business_type',
                'value' => $business_type,
                'compare' => '='
            );
            $query->set('meta_query', $meta_query);
        }

        // Handle category filter
        $category = isset($_GET['gsd_category']) ? sanitize_text_field($_GET['gsd_category']) : get_query_var('gsd_category');
        if (!empty($category)) {
            $tax_query = $query->get('tax_query') ?: array();
            $tax_query[] = array(
                'taxonomy' => 'gsd_category',
                'field' => 'slug',
                'terms' => $category
            );
            $query->set('tax_query', $tax_query);
        }
    }

    /**
     * Prevent 404 on search results page even when no results found.
     */
    public function prevent_search_404() {
        global $wp_query;

        // Check if we have gun shop search parameters
        $has_search_params = (
            !empty($_GET['gsd_search']) ||
            !empty($_GET['gsd_location']) ||
            !empty($_GET['gsd_type']) ||
            !empty($_GET['gsd_category'])
        );

        // If we have search parameters and current URL contains gun-shops
        if ($has_search_params && strpos($_SERVER['REQUEST_URI'], 'gun-shops') !== false) {
            // Only fix 404s, don't interfere with valid results
            if (is_404()) {
                status_header(200);
                $wp_query->is_404 = false;
                $wp_query->is_archive = true;
                $wp_query->is_post_type_archive = true;
                $wp_query->post_type = 'gsd_listing';

                // Load the archive template
                add_filter('template_include', function($template) {
                    $archive_template = locate_template('archive-gsd_listing.php');
                    if ($archive_template) {
                        return $archive_template;
                    }
                    // Use plugin template
                    return plugin_dir_path(dirname(__FILE__)) . 'public/templates/archive-gsd_listing.php';
                });
            }
        }
    }
}
