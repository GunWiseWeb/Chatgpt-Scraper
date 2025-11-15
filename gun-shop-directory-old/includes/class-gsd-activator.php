<?php
/**
 * Fired during plugin activation.
 *
 * @package    Gun_Shop_Directory
 * @subpackage Gun_Shop_Directory/includes
 */

class GSD_Activator {

    /**
     * Activate the plugin.
     *
     * Creates database tables and sets default options.
     *
     * @since    1.0.0
     */
    public static function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Reviews table
        $table_reviews = $wpdb->prefix . 'gsd_reviews';
        $sql_reviews = "CREATE TABLE $table_reviews (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            listing_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            rating int(1) NOT NULL,
            title varchar(255) NOT NULL,
            content text NOT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY listing_id (listing_id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";

        // Review meta table
        $table_review_meta = $wpdb->prefix . 'gsd_review_meta';
        $sql_review_meta = "CREATE TABLE $table_review_meta (
            meta_id bigint(20) NOT NULL AUTO_INCREMENT,
            review_id bigint(20) NOT NULL,
            meta_key varchar(255) NOT NULL,
            meta_value longtext,
            PRIMARY KEY  (meta_id),
            KEY review_id (review_id),
            KEY meta_key (meta_key)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_reviews);
        dbDelta($sql_review_meta);

        // Set default options
        add_option('gsd_version', GSD_VERSION);
        add_option('gsd_require_approval', '1');
        add_option('gsd_allow_anonymous_reviews', '0');
        add_option('gsd_allow_user_submissions', '1');
        add_option('gsd_items_per_page', '12');
        add_option('gsd_google_maps_api_key', '');
        add_option('gsd_enable_map', '1');
        add_option('gsd_directory_layout', 'grid-large');
        add_option('gsd_directory_show_search', '1');
        add_option('gsd_directory_show_submit', '1');

        // Create directory page if it doesn't exist
        $existing_page_id = get_option('gsd_directory_page_id');

        // Check if page exists or if stored ID is invalid
        if (!$existing_page_id || !get_post($existing_page_id)) {
            // Look for existing page by title
            $existing_pages = get_posts(array(
                'post_type' => 'page',
                'post_status' => 'any',
                'title' => 'Gun Shop Directory',
                'posts_per_page' => 1,
                'fields' => 'ids'
            ));

            if (!empty($existing_pages)) {
                // Page exists, just store its ID
                update_option('gsd_directory_page_id', $existing_pages[0]);
            } else {
                // Create new page
                $directory_page = array(
                    'post_title'    => 'Gun Shop Directory',
                    'post_content'  => '[gsd_directory]',
                    'post_status'   => 'publish',
                    'post_type'     => 'page',
                    'post_author'   => get_current_user_id() ?: 1,
                    'comment_status' => 'closed'
                );
                $page_id = wp_insert_post($directory_page);

                if ($page_id && !is_wp_error($page_id)) {
                    update_option('gsd_directory_page_id', $page_id);
                }
            }
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
