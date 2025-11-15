<?php
/**
 * Register custom post types and handle search
 */
class GSD_Post_Types {

    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_gsd_listing', array($this, 'save_listing_meta'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('pre_get_posts', array($this, 'modify_archive_query'));
        add_action('template_redirect', array($this, 'prevent_search_404'));
    }

    public function register_post_types() {
        register_post_type('gsd_listing', array(
            'labels' => array(
                'name' => 'Gun Shop Listings',
                'singular_name' => 'Gun Shop Listing',
                'menu_name' => 'Gun Shops',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Listing',
                'edit_item' => 'Edit Listing',
                'view_item' => 'View Listing',
                'all_items' => 'All Listings',
            ),
            'public' => true,
            'has_archive' => 'gun-shops',
            'rewrite' => array('slug' => 'gun-shop'),
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-store',
            'show_in_rest' => true,
        ));
    }

    public function register_taxonomies() {
        register_taxonomy('gsd_category', 'gsd_listing', array(
            'labels' => array(
                'name' => 'Categories',
                'singular_name' => 'Category',
            ),
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'shop-category'),
        ));

        register_taxonomy('gsd_location', 'gsd_listing', array(
            'labels' => array(
                'name' => 'Locations',
                'singular_name' => 'Location',
            ),
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'location'),
        ));
    }

    public function add_query_vars($vars) {
        $vars[] = 'gsd_search';
        $vars[] = 'gsd_location';
        $vars[] = 'gsd_type';
        return $vars;
    }

    /**
     * Modify the main query for gsd_listing archives
     */
    public function modify_archive_query($query) {
        // Only modify the main query on the frontend for our post type archive
        if (is_admin() || !$query->is_main_query()) {
            return;
        }

        // Only handle gsd_listing archives
        if (!is_post_type_archive('gsd_listing') && !$query->is_post_type_archive('gsd_listing')) {
            // Check if we have search params and URL contains gun-shops
            if ((isset($_GET['gsd_location']) || isset($_GET['gsd_search']) || isset($_GET['gsd_type'])) &&
                strpos($_SERVER['REQUEST_URI'], 'gun-shops') !== false) {
                // Force it to be a post type archive
                $query->set('post_type', 'gsd_listing');
                $query->is_archive = true;
                $query->is_post_type_archive = true;
                $query->is_404 = false;
            } else {
                return;
            }
        }

        // Check for search parameters
        $location = isset($_GET['gsd_location']) ? sanitize_text_field($_GET['gsd_location']) : '';
        $search = isset($_GET['gsd_search']) ? sanitize_text_field($_GET['gsd_search']) : '';
        $type = isset($_GET['gsd_type']) ? sanitize_text_field($_GET['gsd_type']) : '';

        // If we have location search, find matching post IDs via direct SQL
        if (!empty($location)) {
            global $wpdb;

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
                '%' . $wpdb->esc_like($location) . '%',
                '%' . $wpdb->esc_like($location) . '%',
                '%' . $wpdb->esc_like($location) . '%'
            ));

            if (!empty($post_ids)) {
                $query->set('post__in', $post_ids);
            } else {
                $query->set('post__in', array(0)); // No results
            }
        }

        // Handle keyword search
        if (!empty($search)) {
            $query->set('s', $search);
        }

        // Handle type filter
        if (!empty($type)) {
            $meta_query = $query->get('meta_query') ?: array();
            $meta_query[] = array(
                'key' => '_gsd_business_type',
                'value' => $type,
                'compare' => '='
            );
            $query->set('meta_query', $meta_query);
        }
    }

    public function add_meta_boxes() {
        add_meta_box('gsd_business_info', 'Business Information', array($this, 'render_business_info_meta_box'), 'gsd_listing', 'normal', 'high');
        add_meta_box('gsd_contact_info', 'Contact Information', array($this, 'render_contact_info_meta_box'), 'gsd_listing', 'normal', 'high');
        add_meta_box('gsd_hours', 'Business Hours', array($this, 'render_hours_meta_box'), 'gsd_listing', 'normal', 'default');
    }

    public function render_business_info_meta_box($post) {
        wp_nonce_field('gsd_save_listing_meta', 'gsd_listing_nonce');

        $business_type = get_post_meta($post->ID, '_gsd_business_type', true);
        $address = get_post_meta($post->ID, '_gsd_address', true);
        $city = get_post_meta($post->ID, '_gsd_city', true);
        $state = get_post_meta($post->ID, '_gsd_state', true);
        $zip = get_post_meta($post->ID, '_gsd_zip', true);
        $country = get_post_meta($post->ID, '_gsd_country', true) ?: 'United States';
        ?>
        <table class="form-table">
            <tr>
                <th><label for="gsd_business_type">Business Type</label></th>
                <td>
                    <select name="gsd_business_type" id="gsd_business_type" class="regular-text">
                        <option value="brick_mortar" <?php selected($business_type, 'brick_mortar'); ?>>Brick & Mortar</option>
                        <option value="ecommerce" <?php selected($business_type, 'ecommerce'); ?>>E-commerce Only</option>
                        <option value="both" <?php selected($business_type, 'both'); ?>>Both</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="gsd_address">Street Address</label></th>
                <td><input type="text" name="gsd_address" id="gsd_address" value="<?php echo esc_attr($address); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="gsd_city">City</label></th>
                <td><input type="text" name="gsd_city" id="gsd_city" value="<?php echo esc_attr($city); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="gsd_state">State</label></th>
                <td><input type="text" name="gsd_state" id="gsd_state" value="<?php echo esc_attr($state); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="gsd_zip">ZIP Code</label></th>
                <td><input type="text" name="gsd_zip" id="gsd_zip" value="<?php echo esc_attr($zip); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="gsd_country">Country</label></th>
                <td><input type="text" name="gsd_country" id="gsd_country" value="<?php echo esc_attr($country); ?>" class="regular-text"></td>
            </tr>
        </table>
        <?php
    }

    public function render_contact_info_meta_box($post) {
        $phone = get_post_meta($post->ID, '_gsd_phone', true);
        $email = get_post_meta($post->ID, '_gsd_email', true);
        $website = get_post_meta($post->ID, '_gsd_website', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="gsd_phone">Phone</label></th>
                <td><input type="text" name="gsd_phone" id="gsd_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="gsd_email">Email</label></th>
                <td><input type="email" name="gsd_email" id="gsd_email" value="<?php echo esc_attr($email); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="gsd_website">Website</label></th>
                <td><input type="url" name="gsd_website" id="gsd_website" value="<?php echo esc_attr($website); ?>" class="regular-text"></td>
            </tr>
        </table>
        <?php
    }

    public function render_hours_meta_box($post) {
        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
        $day_labels = array(
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
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
                        Closed
                    </label>
                    <br>
                    <label>
                        Open: <input type="time" name="gsd_hours_<?php echo $day; ?>_open" value="<?php echo esc_attr($open); ?>">
                    </label>
                    <label>
                        Close: <input type="time" name="gsd_hours_<?php echo $day; ?>_close" value="<?php echo esc_attr($close); ?>">
                    </label>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php
    }

    public function save_listing_meta($post_id) {
        if (!isset($_POST['gsd_listing_nonce']) || !wp_verify_nonce($_POST['gsd_listing_nonce'], 'gsd_save_listing_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save all meta fields
        $fields = array('gsd_business_type', 'gsd_address', 'gsd_city', 'gsd_state', 'gsd_zip', 'gsd_country', 'gsd_phone', 'gsd_email', 'gsd_website');

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
     * Prevent 404 errors on gun shop search pages
     */
    public function prevent_search_404() {
        global $wp_query;

        // Only run if we have search parameters and are on gun-shops URL
        if (empty($_GET['gsd_location']) && empty($_GET['gsd_search']) && empty($_GET['gsd_type'])) {
            return;
        }

        if (strpos($_SERVER['REQUEST_URI'], 'gun-shops') === false) {
            return;
        }

        // If WordPress thinks this is a 404, correct it
        if (is_404()) {
            status_header(200);
            $wp_query->is_404 = false;
            $wp_query->is_archive = true;
            $wp_query->is_post_type_archive = true;

            // Make sure the post type is set
            if (!isset($wp_query->query_vars['post_type'])) {
                $wp_query->query_vars['post_type'] = 'gsd_listing';
                $wp_query->set('post_type', 'gsd_listing');
            }
        }
    }
}
