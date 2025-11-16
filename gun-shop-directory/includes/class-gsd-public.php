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
        // Enqueue dashicons
        wp_enqueue_style('dashicons');

        wp_enqueue_style(
            $this->plugin_name,
            GSD_PLUGIN_URL . 'public/css/gsd-public.css',
            array('dashicons'),
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
        $atts = shortcode_atts(array(
            'show_add_button' => 'false',
        ), $atts);

        ob_start();

        // Get the correct form action URL
        // Note: Form submission is handled by AJAX, so this is just for fallback
        $form_action = '';
        if (is_post_type_archive('gsd_listing') || is_tax('gsd_category') || is_tax('gsd_location')) {
            $form_action = esc_url(remove_query_arg(array('gsd_search', 'gsd_location', 'gsd_type', 'gsd_category')));
        } else {
            $form_action = get_post_type_archive_link('gsd_listing');
        }
        ?>
        <div class="gsd-search-wrapper">
            <form method="get" action="<?php echo $form_action; ?>" class="gsd-search-form">
                <div class="gsd-search-main">
                    <div class="gsd-search-inputs">
                        <div class="gsd-search-field gsd-search-field-wide">
                            <input type="text" name="gsd_search" placeholder="<?php _e('Search gun shops...', 'gun-shop-directory'); ?>" value="<?php echo esc_attr(isset($_GET['gsd_search']) ? $_GET['gsd_search'] : ''); ?>">
                        </div>

                        <div class="gsd-search-field">
                            <input type="text" name="gsd_location" placeholder="<?php _e('City, State, or ZIP', 'gun-shop-directory'); ?>" value="<?php echo esc_attr(isset($_GET['gsd_location']) ? $_GET['gsd_location'] : ''); ?>">
                        </div>

                        <div class="gsd-search-field">
                            <select name="gsd_type">
                                <option value=""><?php _e('All Types', 'gun-shop-directory'); ?></option>
                                <option value="brick_mortar" <?php selected(isset($_GET['gsd_type']) ? $_GET['gsd_type'] : '', 'brick_mortar'); ?>><?php _e('Brick & Mortar', 'gun-shop-directory'); ?></option>
                                <option value="ecommerce" <?php selected(isset($_GET['gsd_type']) ? $_GET['gsd_type'] : '', 'ecommerce'); ?>><?php _e('Online Store', 'gun-shop-directory'); ?></option>
                                <option value="both" <?php selected(isset($_GET['gsd_type']) ? $_GET['gsd_type'] : '', 'both'); ?>><?php _e('Both', 'gun-shop-directory'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="gsd-search-actions">
                        <button type="submit" class="gsd-search-submit gsd-button gsd-button-primary">
                            <span class="dashicons dashicons-search"></span>
                            <?php _e('Search', 'gun-shop-directory'); ?>
                        </button>

                        <button type="button" class="gsd-search-clear gsd-button gsd-button-secondary" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                            <span class="dashicons dashicons-no-alt"></span>
                            <?php _e('Clear', 'gun-shop-directory'); ?>
                        </button>

                        <?php if ($atts['show_add_button'] === 'true' && get_option('gsd_allow_user_submissions', '1') == '1') : ?>
                            <button type="button" class="gsd-button gsd-button-secondary gsd-submit-trigger">
                                <span class="dashicons dashicons-plus-alt"></span>
                                <?php _e('Add Listing', 'gun-shop-directory'); ?>
                            </button>
                        <?php endif; ?>
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

    /**
     * Directory shortcode - unified directory with search, listings, and submit button.
     *
     * @param array $atts Shortcode attributes
     * Available layouts:
     * - grid-large (default) - Large cards with images
     * - grid-compact - Smaller cards, more per row
     * - grid-minimal - Very compact grid
     * - list-simple - Horizontal layout, minimal info
     * - list-detailed - Horizontal layout with more details
     */
    public function directory_shortcode($atts) {
        // Get defaults from admin settings
        $default_layout = get_option('gsd_directory_layout', 'grid-large');
        $default_show_search = get_option('gsd_directory_show_search', '1') == '1' ? 'true' : 'false';
        $default_show_submit = get_option('gsd_directory_show_submit', '1') == '1' ? 'true' : 'false';

        $atts = shortcode_atts(array(
            'layout' => $default_layout,
            'show_search' => $default_show_search,
            'show_submit' => $default_show_submit,
            'limit' => get_option('gsd_items_per_page', 12),
            'category' => '',
            'location' => '',
            'orderby' => 'date',
            'order' => 'DESC',
        ), $atts);

        $layout_class = 'gsd-layout-' . esc_attr($atts['layout']);

        ob_start();
        ?>
        <div class="gsd-directory-wrapper <?php echo $layout_class; ?>">
            <div class="gsd-directory-header">
                <h1 class="gsd-directory-title"><?php _e('Find Your Perfect Gun Shop', 'gun-shop-directory'); ?></h1>
                <p class="gsd-directory-subtitle"><?php _e('Discover trusted gun shops, read reviews, and find the best firearms dealers near you.', 'gun-shop-directory'); ?></p>
            </div>

            <?php if ($atts['show_search'] === 'true') : ?>
                <div class="gsd-directory-search">
                    <?php echo $this->search_shortcode(array('show_add_button' => $atts['show_submit'])); ?>
                </div>
            <?php endif; ?>

            <div class="gsd-directory-listings">
                <?php echo $this->get_listings_html($atts, $atts['layout']); ?>
            </div>

            <?php if ($atts['show_submit'] === 'true') : ?>
                <div id="gsd-submit-form" class="gsd-directory-submit-section" style="display:none;">
                    <?php echo $this->submit_listing_shortcode(array()); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get listings HTML with specific layout.
     */
    private function get_listings_html($atts, $layout) {
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
            $container_class = $this->get_layout_container_class($layout);
            echo '<div class="' . esc_attr($container_class) . '">';

            while ($query->have_posts()) {
                $query->the_post();
                $this->render_listing_by_layout(get_the_ID(), $layout);
            }

            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p class="gsd-no-results">' . __('No listings found.', 'gun-shop-directory') . '</p>';
        }

        return ob_get_clean();
    }

    /**
     * Get container class based on layout.
     */
    private function get_layout_container_class($layout) {
        $classes = array(
            'grid-large' => 'gsd-listings-grid gsd-grid-large',
            'grid-compact' => 'gsd-listings-grid gsd-grid-compact',
            'grid-minimal' => 'gsd-listings-grid gsd-grid-minimal',
            'list-simple' => 'gsd-listings-list gsd-list-simple',
            'list-detailed' => 'gsd-listings-list gsd-list-detailed',
        );

        return isset($classes[$layout]) ? $classes[$layout] : $classes['grid-large'];
    }

    /**
     * Render listing based on layout type.
     */
    public function render_listing_by_layout($listing_id, $layout) {
        switch ($layout) {
            case 'list-simple':
                $this->render_listing_list_simple($listing_id);
                break;
            case 'list-detailed':
                $this->render_listing_list_detailed($listing_id);
                break;
            case 'grid-minimal':
            case 'grid-compact':
                $this->render_listing_grid_compact($listing_id);
                break;
            case 'grid-large':
            default:
                $this->render_listing_card($listing_id);
                break;
        }
    }

    /**
     * Render compact grid listing.
     */
    private function render_listing_grid_compact($listing_id) {
        $rating = GSD_Reviews::get_average_rating($listing_id);
        $review_count = GSD_Reviews::get_review_count($listing_id);
        $business_type = get_post_meta($listing_id, '_gsd_business_type', true);
        $city = get_post_meta($listing_id, '_gsd_city', true);
        $state = get_post_meta($listing_id, '_gsd_state', true);
        ?>
        <div class="gsd-listing-card gsd-listing-compact">
            <?php if (has_post_thumbnail($listing_id)) : ?>
                <div class="gsd-listing-image-compact">
                    <a href="<?php echo get_permalink($listing_id); ?>">
                        <?php echo get_the_post_thumbnail($listing_id, 'medium'); ?>
                    </a>
                    <?php if ($business_type) : ?>
                        <span class="gsd-badge gsd-badge-<?php echo esc_attr($business_type); ?> gsd-badge-overlay">
                            <?php echo $this->get_business_type_label($business_type); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="gsd-listing-content">
                <h3 class="gsd-listing-title">
                    <a href="<?php echo get_permalink($listing_id); ?>"><?php echo get_the_title($listing_id); ?></a>
                </h3>

                <?php if ($rating > 0) : ?>
                    <div class="gsd-listing-rating">
                        <?php echo GSD_Reviews::render_stars($rating, false); ?>
                        <span class="gsd-rating-number"><?php echo number_format($rating, 1); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($city || $state) : ?>
                    <div class="gsd-listing-location">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html(trim("$city, $state", ', ')); ?>
                    </div>
                <?php endif; ?>

                <a href="<?php echo get_permalink($listing_id); ?>" class="gsd-button gsd-button-primary gsd-button-sm gsd-button-block">
                    <?php _e('View Details', 'gun-shop-directory'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Render simple list listing.
     */
    private function render_listing_list_simple($listing_id) {
        $rating = GSD_Reviews::get_average_rating($listing_id);
        $review_count = GSD_Reviews::get_review_count($listing_id);
        $city = get_post_meta($listing_id, '_gsd_city', true);
        $state = get_post_meta($listing_id, '_gsd_state', true);
        $phone = get_post_meta($listing_id, '_gsd_phone', true);
        ?>
        <div class="gsd-listing-row gsd-listing-simple">
            <div class="gsd-listing-main-info">
                <h3 class="gsd-listing-title">
                    <a href="<?php echo get_permalink($listing_id); ?>"><?php echo get_the_title($listing_id); ?></a>
                </h3>
                <?php if ($city || $state) : ?>
                    <span class="gsd-listing-location"><?php echo esc_html(trim("$city, $state", ', ')); ?></span>
                <?php endif; ?>
            </div>

            <?php if ($rating > 0) : ?>
                <div class="gsd-listing-rating">
                    <?php echo GSD_Reviews::render_stars($rating, false); ?>
                    <span class="gsd-rating-number"><?php echo number_format($rating, 1); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($phone) : ?>
                <div class="gsd-listing-phone"><?php echo esc_html($phone); ?></div>
            <?php endif; ?>

            <div class="gsd-listing-action">
                <a href="<?php echo get_permalink($listing_id); ?>" class="gsd-button gsd-button-primary gsd-button-sm">
                    <?php _e('View', 'gun-shop-directory'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Render detailed list listing.
     */
    private function render_listing_list_detailed($listing_id) {
        $rating = GSD_Reviews::get_average_rating($listing_id);
        $review_count = GSD_Reviews::get_review_count($listing_id);
        $business_type = get_post_meta($listing_id, '_gsd_business_type', true);
        $city = get_post_meta($listing_id, '_gsd_city', true);
        $state = get_post_meta($listing_id, '_gsd_state', true);
        $phone = get_post_meta($listing_id, '_gsd_phone', true);
        $has_thumb = has_post_thumbnail($listing_id);
        $layout_class = $has_thumb ? 'gsd-listing-detailed' : 'gsd-listing-detailed gsd-no-thumb';
        ?>
        <div class="gsd-listing-row <?php echo $layout_class; ?>">
            <?php if (has_post_thumbnail($listing_id)) : ?>
                <div class="gsd-listing-thumb">
                    <a href="<?php echo get_permalink($listing_id); ?>">
                        <?php echo get_the_post_thumbnail($listing_id, 'thumbnail'); ?>
                    </a>
                </div>
            <?php endif; ?>

            <div class="gsd-listing-details">
                <h3 class="gsd-listing-title">
                    <a href="<?php echo get_permalink($listing_id); ?>"><?php echo get_the_title($listing_id); ?></a>
                </h3>

                <div class="gsd-listing-meta-row">
                    <?php if ($business_type) : ?>
                        <span class="gsd-badge gsd-badge-<?php echo esc_attr($business_type); ?> gsd-badge-sm">
                            <?php echo $this->get_business_type_label($business_type); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($rating > 0) : ?>
                        <div class="gsd-listing-rating">
                            <?php echo GSD_Reviews::render_stars($rating); ?>
                            <span class="gsd-review-count">(<?php echo $review_count; ?> reviews)</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="gsd-listing-excerpt">
                    <?php echo wp_trim_words(get_the_excerpt($listing_id), 15); ?>
                </div>

                <div class="gsd-listing-meta">
                    <?php if ($city || $state) : ?>
                        <span class="gsd-meta-item">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html(trim("$city, $state", ', ')); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($phone) : ?>
                        <span class="gsd-meta-item">
                            <span class="dashicons dashicons-phone"></span>
                            <?php echo esc_html($phone); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="gsd-listing-action">
                <a href="<?php echo get_permalink($listing_id); ?>" class="gsd-button gsd-button-primary">
                    <?php _e('View Details', 'gun-shop-directory'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Submit listing shortcode - frontend listing submission form.
     */
    public function submit_listing_shortcode($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="gsd-submit-listing-wrapper"><p>' . __('Please <a href="#" class="gsd-theme-login-trigger">log in</a> to submit a listing.', 'gun-shop-directory') . '</p></div>';
        }

        // Check if user submissions are enabled
        if (get_option('gsd_allow_user_submissions', '1') != '1') {
            return '<div class="gsd-submit-listing-wrapper"><p>' . __('Listing submissions are currently disabled.', 'gun-shop-directory') . '</p></div>';
        }

        ob_start();
        ?>
        <div class="gsd-submit-listing-wrapper">
            <h2><?php _e('Submit Your Gun Shop Listing', 'gun-shop-directory'); ?></h2>

            <?php if (isset($_GET['submitted']) && $_GET['submitted'] == 'true') : ?>
                <div class="gsd-success-message">
                    <?php _e('Thank you! Your listing has been submitted and is pending approval.', 'gun-shop-directory'); ?>
                </div>
            <?php endif; ?>

            <form id="gsd-submit-listing-form" class="gsd-submit-form" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('gsd_submit_listing', 'gsd_submit_nonce'); ?>

                <!-- Basic Information -->
                <div class="gsd-form-section">
                    <h3><?php _e('Basic Information', 'gun-shop-directory'); ?></h3>

                    <div class="gsd-form-group">
                        <label><?php _e('Business Name', 'gun-shop-directory'); ?> <span class="required">*</span></label>
                        <input type="text" name="business_name" required>
                    </div>

                    <div class="gsd-form-group">
                        <label><?php _e('Business Type', 'gun-shop-directory'); ?> <span class="required">*</span></label>
                        <select name="business_type" required>
                            <option value=""><?php _e('Select...', 'gun-shop-directory'); ?></option>
                            <option value="brick_mortar"><?php _e('Brick & Mortar', 'gun-shop-directory'); ?></option>
                            <option value="ecommerce"><?php _e('E-commerce Only', 'gun-shop-directory'); ?></option>
                            <option value="both"><?php _e('Both', 'gun-shop-directory'); ?></option>
                        </select>
                    </div>

                    <div class="gsd-form-group">
                        <label><?php _e('Description', 'gun-shop-directory'); ?> <span class="required">*</span></label>
                        <textarea name="description" required></textarea>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="gsd-form-section">
                    <h3><?php _e('Contact Information', 'gun-shop-directory'); ?></h3>

                    <div class="gsd-form-row">
                        <div class="gsd-form-group">
                            <label><?php _e('Phone', 'gun-shop-directory'); ?> <span class="required">*</span></label>
                            <input type="tel" name="phone" required>
                        </div>

                        <div class="gsd-form-group">
                            <label><?php _e('Email', 'gun-shop-directory'); ?> <span class="required">*</span></label>
                            <input type="email" name="email" required>
                        </div>
                    </div>

                    <div class="gsd-form-group">
                        <label><?php _e('Website', 'gun-shop-directory'); ?></label>
                        <input type="url" name="website">
                    </div>
                </div>

                <!-- Address -->
                <div class="gsd-form-section">
                    <h3><?php _e('Address', 'gun-shop-directory'); ?></h3>

                    <div class="gsd-form-group">
                        <label><?php _e('Street Address', 'gun-shop-directory'); ?> <span class="required">*</span></label>
                        <input type="text" name="address" required>
                    </div>

                    <div class="gsd-form-row">
                        <div class="gsd-form-group">
                            <label><?php _e('City', 'gun-shop-directory'); ?> <span class="required">*</span></label>
                            <input type="text" name="city" required>
                        </div>

                        <div class="gsd-form-group">
                            <label><?php _e('State', 'gun-shop-directory'); ?> <span class="required">*</span></label>
                            <input type="text" name="state" required>
                        </div>
                    </div>

                    <div class="gsd-form-row">
                        <div class="gsd-form-group">
                            <label><?php _e('ZIP Code', 'gun-shop-directory'); ?> <span class="required">*</span></label>
                            <input type="text" name="zip" required>
                        </div>

                        <div class="gsd-form-group">
                            <label><?php _e('Country', 'gun-shop-directory'); ?></label>
                            <input type="text" name="country" value="United States">
                        </div>
                    </div>
                </div>

                <div class="gsd-form-group">
                    <button type="submit" class="gsd-button gsd-button-secondary" style="width: 100%; font-size: 1.1em; padding: 15px;">
                        <?php _e('Submit Listing for Approval', 'gun-shop-directory'); ?>
                    </button>
                </div>

                <div class="gsd-form-message"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle frontend listing submission.
     */
    public function handle_listing_submission() {
        if (!isset($_POST['gsd_submit_nonce']) || !wp_verify_nonce($_POST['gsd_submit_nonce'], 'gsd_submit_listing')) {
            return;
        }

        if (!is_user_logged_in()) {
            return;
        }

        if (get_option('gsd_allow_user_submissions', '1') != '1') {
            return;
        }

        // Sanitize and validate inputs
        $business_name = sanitize_text_field($_POST['business_name']);
        $business_type = sanitize_text_field($_POST['business_type']);
        $description = wp_kses_post($_POST['description']);
        $phone = sanitize_text_field($_POST['phone']);
        $email = sanitize_email($_POST['email']);
        $website = esc_url_raw($_POST['website']);
        $address = sanitize_text_field($_POST['address']);
        $city = sanitize_text_field($_POST['city']);
        $state = sanitize_text_field($_POST['state']);
        $zip = sanitize_text_field($_POST['zip']);
        $country = sanitize_text_field($_POST['country']);

        // Create the listing post
        $post_data = array(
            'post_title' => $business_name,
            'post_content' => $description,
            'post_type' => 'gsd_listing',
            'post_status' => 'pending', // Pending approval
            'post_author' => get_current_user_id(),
        );

        $listing_id = wp_insert_post($post_data);

        if ($listing_id && !is_wp_error($listing_id)) {
            // Save meta data
            update_post_meta($listing_id, '_gsd_business_type', $business_type);
            update_post_meta($listing_id, '_gsd_phone', $phone);
            update_post_meta($listing_id, '_gsd_email', $email);
            update_post_meta($listing_id, '_gsd_website', $website);
            update_post_meta($listing_id, '_gsd_address', $address);
            update_post_meta($listing_id, '_gsd_city', $city);
            update_post_meta($listing_id, '_gsd_state', $state);
            update_post_meta($listing_id, '_gsd_zip', $zip);
            update_post_meta($listing_id, '_gsd_country', $country);

            // Redirect with success message
            wp_redirect(add_query_arg('submitted', 'true', get_permalink()));
            exit;
        }
    }

    /**
     * Handle business claim submission via AJAX.
     */
    public function ajax_submit_claim() {
        check_ajax_referer('gsd_submit_review', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to claim a business.', 'gun-shop-directory')));
        }

        $listing_id = intval($_POST['listing_id']);
        $claimant_name = sanitize_text_field($_POST['claimant_name']);
        $claimant_position = sanitize_text_field($_POST['claimant_position']);
        $business_phone = sanitize_text_field($_POST['business_phone']);
        $verification_details = sanitize_textarea_field($_POST['verification_details']);

        // Check if listing exists
        if (!get_post($listing_id) || get_post_type($listing_id) !== 'gsd_listing') {
            wp_send_json_error(array('message' => __('Invalid listing.', 'gun-shop-directory')));
        }

        // Check if already claimed or pending
        $is_claimed = get_post_meta($listing_id, '_gsd_claimed', true);
        $pending_claim = get_post_meta($listing_id, '_gsd_claim_pending', true);

        if ($is_claimed) {
            wp_send_json_error(array('message' => __('This business has already been claimed.', 'gun-shop-directory')));
        }

        if ($pending_claim) {
            wp_send_json_error(array('message' => __('A claim for this business is already pending review.', 'gun-shop-directory')));
        }

        // Store claim data
        $claim_data = array(
            'user_id' => get_current_user_id(),
            'claimant_name' => $claimant_name,
            'claimant_position' => $claimant_position,
            'business_phone' => $business_phone,
            'verification_details' => $verification_details,
            'submitted_at' => current_time('mysql'),
        );

        update_post_meta($listing_id, '_gsd_claim_data', $claim_data);
        update_post_meta($listing_id, '_gsd_claim_pending', '1');

        // Send notification to admin
        $admin_email = get_option('admin_email');
        $listing_title = get_the_title($listing_id);
        $listing_url = get_edit_post_link($listing_id);
        $user = wp_get_current_user();

        $subject = sprintf(__('[%s] New Business Claim Request', 'gun-shop-directory'), get_bloginfo('name'));
        $message = sprintf(
            __("A new business claim has been submitted:\n\nBusiness: %s\nClaimant: %s (%s)\nPosition: %s\nUser: %s\n\nReview claim: %s", 'gun-shop-directory'),
            $listing_title,
            $claimant_name,
            $business_phone,
            $claimant_position,
            $user->user_email,
            $listing_url
        );

        wp_mail($admin_email, $subject, $message);

        wp_send_json_success(array(
            'message' => __('Your claim has been submitted successfully! An administrator will review it shortly.', 'gun-shop-directory')
        ));
    }

    /**
     * Handle listing report submission via AJAX.
     */
    public function ajax_submit_report() {
        check_ajax_referer('gsd_submit_review', 'nonce');

        $listing_id = intval($_POST['listing_id']);
        $reporter_email = sanitize_email($_POST['reporter_email']);
        $report_reason = sanitize_text_field($_POST['report_reason']);
        $report_details = sanitize_textarea_field($_POST['report_details']);

        // Validate inputs
        if (empty($reporter_email) || !is_email($reporter_email)) {
            wp_send_json_error(array('message' => __('Please provide a valid email address.', 'gun-shop-directory')));
        }

        if (empty($report_reason)) {
            wp_send_json_error(array('message' => __('Please select a reason for the report.', 'gun-shop-directory')));
        }

        if (empty($report_details)) {
            wp_send_json_error(array('message' => __('Please provide details about your report.', 'gun-shop-directory')));
        }

        // Check if listing exists
        if (!get_post($listing_id) || get_post_type($listing_id) !== 'gsd_listing') {
            wp_send_json_error(array('message' => __('Invalid listing.', 'gun-shop-directory')));
        }

        // Store report data
        $report_data = array(
            'reporter_email' => $reporter_email,
            'report_reason' => $report_reason,
            'report_details' => $report_details,
            'submitted_at' => current_time('mysql'),
            'reporter_ip' => $_SERVER['REMOTE_ADDR'],
        );

        // Get existing reports for this listing
        $existing_reports = get_post_meta($listing_id, '_gsd_reports', true);
        if (!is_array($existing_reports)) {
            $existing_reports = array();
        }
        $existing_reports[] = $report_data;
        update_post_meta($listing_id, '_gsd_reports', $existing_reports);

        // Send notification to admin
        $admin_email = get_option('admin_email');
        $listing_title = get_the_title($listing_id);
        $listing_url = get_permalink($listing_id);
        $edit_url = get_edit_post_link($listing_id);

        $reason_labels = array(
            'incorrect_info' => __('Incorrect Information', 'gun-shop-directory'),
            'closed' => __('Business is Closed', 'gun-shop-directory'),
            'duplicate' => __('Duplicate Listing', 'gun-shop-directory'),
            'remove_request' => __('Business Owner - Request Removal', 'gun-shop-directory'),
            'inappropriate' => __('Inappropriate Content', 'gun-shop-directory'),
            'other' => __('Other', 'gun-shop-directory'),
        );
        $reason_label = isset($reason_labels[$report_reason]) ? $reason_labels[$report_reason] : $report_reason;

        $subject = sprintf(__('[%s] Listing Report: %s', 'gun-shop-directory'), get_bloginfo('name'), $listing_title);
        $message = sprintf(
            __("A listing has been reported:\n\nListing: %s\nView listing: %s\nEdit listing: %s\n\nReporter Email: %s\nReason: %s\n\nDetails:\n%s\n\nSubmitted: %s", 'gun-shop-directory'),
            $listing_title,
            $listing_url,
            $edit_url,
            $reporter_email,
            $reason_label,
            $report_details,
            current_time('mysql')
        );

        wp_mail($admin_email, $subject, $message, array('Reply-To: ' . $reporter_email));

        wp_send_json_success(array(
            'message' => __('Your report has been submitted successfully! We will review it and contact you if needed.', 'gun-shop-directory')
        ));
    }

    /**
     * AJAX search handler
     */
    public function ajax_search_listings() {
        $location = isset($_POST['gsd_location']) ? sanitize_text_field($_POST['gsd_location']) : '';
        $search = isset($_POST['gsd_search']) ? sanitize_text_field($_POST['gsd_search']) : '';
        $type = isset($_POST['gsd_type']) ? sanitize_text_field($_POST['gsd_type']) : '';
        $layout = isset($_POST['layout']) ? sanitize_text_field($_POST['layout']) : 'grid-large';
        $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;

        // Get posts per page setting (default to 12)
        $posts_per_page = get_option('gsd_items_per_page', 12);

        // Build query args
        $args = array(
            'post_type' => 'gsd_listing',
            'post_status' => 'publish',
            'posts_per_page' => intval($posts_per_page),
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        // Location search (city, state, or zip)
        if (!empty($location)) {
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => '_gsd_city',
                    'value' => $location,
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => '_gsd_state',
                    'value' => $location,
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => '_gsd_zip',
                    'value' => $location,
                    'compare' => 'LIKE',
                ),
            );
        }
        // Keyword search (title/content)
        elseif (!empty($search)) {
            $args['s'] = $search;
        }

        // Business type filter
        if (!empty($type)) {
            if (!empty($location)) {
                // Already has meta_query, add to it
                $args['meta_query'][] = array(
                    'key' => '_gsd_business_type',
                    'value' => $type,
                    'compare' => '=',
                );
                $args['meta_query']['relation'] = 'AND';
            } else {
                // No existing meta_query
                $args['meta_query'] = array(
                    array(
                        'key' => '_gsd_business_type',
                        'value' => $type,
                        'compare' => '=',
                    ),
                );
            }
        }

        $query = new WP_Query($args);

        ob_start();

        if ($query->have_posts()) {
            $container_classes = array(
                'grid-large' => 'gsd-listings-grid gsd-grid-large',
                'grid-compact' => 'gsd-listings-grid gsd-grid-compact',
                'grid-minimal' => 'gsd-listings-grid gsd-grid-minimal',
                'list-simple' => 'gsd-listings-list gsd-list-simple',
                'list-detailed' => 'gsd-listings-list gsd-list-detailed',
            );
            $container_class = isset($container_classes[$layout]) ? $container_classes[$layout] : $container_classes['grid-large'];

            echo '<div class="' . esc_attr($container_class) . '">';

            while ($query->have_posts()) {
                $query->the_post();
                $this->render_listing_by_layout(get_the_ID(), $layout);
            }

            echo '</div>';

            wp_reset_postdata();

            $results_html = ob_get_clean();

            // Generate AJAX-friendly pagination
            ob_start();
            $total_pages = $query->max_num_pages;
            if ($total_pages > 1) {
                echo '<nav class="gsd-pagination" data-location="' . esc_attr($location) . '" data-search="' . esc_attr($search) . '" data-type="' . esc_attr($type) . '" data-layout="' . esc_attr($layout) . '">';

                // Previous link
                if ($paged > 1) {
                    echo '<a href="#" class="gsd-page-link prev" data-page="' . ($paged - 1) . '">&laquo; Previous</a>';
                }

                // Page numbers
                for ($i = 1; $i <= $total_pages; $i++) {
                    if ($i == $paged) {
                        echo '<span class="page-numbers current">' . $i . '</span>';
                    } else {
                        echo '<a href="#" class="gsd-page-link" data-page="' . $i . '">' . $i . '</a>';
                    }
                }

                // Next link
                if ($paged < $total_pages) {
                    echo '<a href="#" class="gsd-page-link next" data-page="' . ($paged + 1) . '">Next &raquo;</a>';
                }

                echo '</nav>';
            }
            $pagination_html = ob_get_clean();

            wp_send_json_success(array(
                'html' => $results_html,
                'count' => $query->found_posts,
                'pagination' => $pagination_html
            ));
        } else {
            echo '<div class="gsd-no-results">';
            echo '<h2>' . __('No listings found', 'gun-shop-directory') . '</h2>';
            echo '<p>' . __('Try adjusting your search criteria.', 'gun-shop-directory') . '</p>';
            echo '</div>';

            $results_html = ob_get_clean();
            wp_send_json_success(array(
                'html' => $results_html,
                'count' => 0,
                'pagination' => '' // No pagination for no results
            ));
        }
    }

    /**
     * Modify main query to preserve search parameters in pagination
     */
    public function modify_main_query($query) {
        // Only modify main query on frontend for gsd_listing archives
        if (is_admin() || !$query->is_main_query()) {
            return;
        }

        // Check if this is a gsd_listing archive
        if ($query->get('post_type') !== 'gsd_listing' && !is_post_type_archive('gsd_listing')) {
            return;
        }

        // Get search parameters from URL
        $location = isset($_GET['gsd_location']) ? sanitize_text_field($_GET['gsd_location']) : '';
        $search = isset($_GET['gsd_search']) ? sanitize_text_field($_GET['gsd_search']) : '';
        $type = isset($_GET['gsd_type']) ? sanitize_text_field($_GET['gsd_type']) : '';

        // Only modify if search parameters are present
        if (empty($location) && empty($search) && empty($type)) {
            return;
        }

        // Location search (city, state, or zip)
        if (!empty($location)) {
            $meta_query = array(
                'relation' => 'OR',
                array(
                    'key' => '_gsd_city',
                    'value' => $location,
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => '_gsd_state',
                    'value' => $location,
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => '_gsd_zip',
                    'value' => $location,
                    'compare' => 'LIKE',
                ),
            );

            // Add business type filter if present
            if (!empty($type)) {
                $meta_query['relation'] = 'AND';
                $meta_query[] = array(
                    'key' => '_gsd_business_type',
                    'value' => $type,
                    'compare' => '=',
                );
            }

            $query->set('meta_query', $meta_query);
        }
        // Keyword search (title/content)
        elseif (!empty($search)) {
            $query->set('s', $search);

            // Add business type filter if present
            if (!empty($type)) {
                $query->set('meta_query', array(
                    array(
                        'key' => '_gsd_business_type',
                        'value' => $type,
                        'compare' => '=',
                    ),
                ));
            }
        }
        // Just business type filter
        elseif (!empty($type)) {
            $query->set('meta_query', array(
                array(
                    'key' => '_gsd_business_type',
                    'value' => $type,
                    'compare' => '=',
                ),
            ));
        }
    }
}
