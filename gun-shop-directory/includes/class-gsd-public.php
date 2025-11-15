<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    Gun_Shop_Directory
 * @subpackage Gun_Shop_Directory/includes
 */

class GSD_Public {

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
     * Register the stylesheets for the public-facing side.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            GSD_PLUGIN_URL . 'public/css/gsd-public.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side.
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            GSD_PLUGIN_URL . 'public/js/gsd-public.js',
            array('jquery'),
            $this->version,
            false
        );

        wp_localize_script($this->plugin_name, 'gsdPublic', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gsd_submit_review'),
        ));

        // Enqueue Google Maps if enabled
        if (get_option('gsd_enable_map', '1') == '1') {
            $api_key = get_option('gsd_google_maps_api_key', '');
            if (!empty($api_key)) {
                wp_enqueue_script(
                    'google-maps',
                    'https://maps.googleapis.com/maps/api/js?key=' . $api_key,
                    array(),
                    null,
                    false
                );
            }
        }
    }

    /**
     * Load custom templates for listings.
     */
    public function template_loader($template) {
        if (is_singular('gsd_listing')) {
            $custom_template = $this->locate_template('single-gsd_listing.php');
            if ($custom_template) {
                return $custom_template;
            }
        }

        if (is_post_type_archive('gsd_listing') || is_tax('gsd_category') || is_tax('gsd_location')) {
            $custom_template = $this->locate_template('archive-gsd_listing.php');
            if ($custom_template) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Locate template.
     */
    private function locate_template($template_name) {
        // Check theme first
        $theme_template = locate_template(array('gun-shop-directory/' . $template_name));
        if ($theme_template) {
            return $theme_template;
        }

        // Check plugin
        $plugin_template = GSD_PLUGIN_DIR . 'public/templates/' . $template_name;
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }

        return false;
    }

    /**
     * Listings shortcode.
     */
    public function listings_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => get_option('gsd_items_per_page', 12),
            'category' => '',
            'location' => '',
            'orderby' => 'date',
            'order' => 'DESC',
        ), $atts);

        $args = array(
            'post_type' => 'gsd_listing',
            'posts_per_page' => intval($atts['limit']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
        );

        if (!empty($atts['category'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'gsd_category',
                'field' => 'slug',
                'terms' => $atts['category'],
            );
        }

        if (!empty($atts['location'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'gsd_location',
                'field' => 'slug',
                'terms' => $atts['location'],
            );
        }

        $query = new WP_Query($args);

        ob_start();

        if ($query->have_posts()) {
            echo '<div class="gsd-listings-grid">';

            while ($query->have_posts()) {
                $query->the_post();
                $this->render_listing_card(get_the_ID());
            }

            echo '</div>';

            wp_reset_postdata();
        } else {
            echo '<p>' . __('No listings found.', 'gun-shop-directory') . '</p>';
        }

        return ob_get_clean();
    }

    /**
     * Search shortcode.
     */
    public function search_shortcode($atts) {
        ob_start();
        ?>
        <div class="gsd-search-form">
            <form method="get" action="<?php echo get_post_type_archive_link('gsd_listing'); ?>">
                <div class="gsd-search-fields">
                    <div class="gsd-search-field">
                        <input type="text" name="gsd_search" placeholder="<?php _e('Search gun shops...', 'gun-shop-directory'); ?>" value="<?php echo esc_attr(get_query_var('gsd_search')); ?>">
                    </div>

                    <div class="gsd-search-field">
                        <input type="text" name="gsd_location" placeholder="<?php _e('Location...', 'gun-shop-directory'); ?>" value="<?php echo esc_attr(get_query_var('gsd_location')); ?>">
                    </div>

                    <div class="gsd-search-field">
                        <select name="gsd_category">
                            <option value=""><?php _e('All Categories', 'gun-shop-directory'); ?></option>
                            <?php
                            $categories = get_terms(array('taxonomy' => 'gsd_category', 'hide_empty' => false));
                            foreach ($categories as $cat) {
                                $selected = get_query_var('gsd_category') == $cat->slug ? 'selected' : '';
                                echo '<option value="' . esc_attr($cat->slug) . '" ' . $selected . '>' . esc_html($cat->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="gsd-search-field">
                        <select name="gsd_type">
                            <option value=""><?php _e('All Types', 'gun-shop-directory'); ?></option>
                            <option value="brick_mortar" <?php selected(get_query_var('gsd_type'), 'brick_mortar'); ?>><?php _e('Brick & Mortar', 'gun-shop-directory'); ?></option>
                            <option value="ecommerce" <?php selected(get_query_var('gsd_type'), 'ecommerce'); ?>><?php _e('E-commerce', 'gun-shop-directory'); ?></option>
                            <option value="both" <?php selected(get_query_var('gsd_type'), 'both'); ?>><?php _e('Both', 'gun-shop-directory'); ?></option>
                        </select>
                    </div>

                    <div class="gsd-search-field">
                        <button type="submit" class="gsd-search-submit"><?php _e('Search', 'gun-shop-directory'); ?></button>
                    </div>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render listing card.
     */
    public function render_listing_card($listing_id) {
        $rating = GSD_Reviews::get_average_rating($listing_id);
        $review_count = GSD_Reviews::get_review_count($listing_id);
        $business_type = get_post_meta($listing_id, '_gsd_business_type', true);
        $city = get_post_meta($listing_id, '_gsd_city', true);
        $state = get_post_meta($listing_id, '_gsd_state', true);
        $phone = get_post_meta($listing_id, '_gsd_phone', true);

        ?>
        <div class="gsd-listing-card">
            <?php if (has_post_thumbnail($listing_id)) : ?>
                <div class="gsd-listing-image">
                    <a href="<?php echo get_permalink($listing_id); ?>">
                        <?php echo get_the_post_thumbnail($listing_id, 'medium'); ?>
                    </a>
                </div>
            <?php endif; ?>

            <div class="gsd-listing-content">
                <h3 class="gsd-listing-title">
                    <a href="<?php echo get_permalink($listing_id); ?>"><?php echo get_the_title($listing_id); ?></a>
                </h3>

                <?php if ($rating > 0) : ?>
                    <div class="gsd-listing-rating">
                        <?php echo GSD_Reviews::render_stars($rating); ?>
                        <span class="gsd-review-count">(<?php echo $review_count; ?> <?php _e('reviews', 'gun-shop-directory'); ?>)</span>
                    </div>
                <?php endif; ?>

                <?php if ($business_type) : ?>
                    <div class="gsd-listing-type">
                        <span class="gsd-badge gsd-badge-<?php echo esc_attr($business_type); ?>">
                            <?php echo $this->get_business_type_label($business_type); ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ($city || $state) : ?>
                    <div class="gsd-listing-location">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html(trim("$city, $state", ', ')); ?>
                    </div>
                <?php endif; ?>

                <?php if ($phone) : ?>
                    <div class="gsd-listing-phone">
                        <span class="dashicons dashicons-phone"></span>
                        <?php echo esc_html($phone); ?>
                    </div>
                <?php endif; ?>

                <div class="gsd-listing-excerpt">
                    <?php echo wp_trim_words(get_the_excerpt($listing_id), 20); ?>
                </div>

                <a href="<?php echo get_permalink($listing_id); ?>" class="gsd-button gsd-button-primary">
                    <?php _e('View Details', 'gun-shop-directory'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Get business type label.
     */
    private function get_business_type_label($type) {
        $labels = array(
            'brick_mortar' => __('Brick & Mortar', 'gun-shop-directory'),
            'ecommerce' => __('E-commerce', 'gun-shop-directory'),
            'both' => __('Both', 'gun-shop-directory'),
        );

        return isset($labels[$type]) ? $labels[$type] : $type;
    }
}
