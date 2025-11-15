<?php
/**
 * Archive template for Gun Shop Listings
 *
 * @package Gun_Shop_Directory
 */

get_header();
?>

<div class="gsd-archive-container">
    <div class="gsd-archive-header">
        <h1 class="gsd-archive-title">
            <?php
            if (is_tax('gsd_category')) {
                single_term_title('');
            } elseif (is_tax('gsd_location')) {
                single_term_title(__('Gun Shops in ', 'gun-shop-directory'));
            } else {
                _e('Find Your Perfect Gun Shop', 'gun-shop-directory');
            }
            ?>
        </h1>
        <p class="gsd-archive-subtitle">
            <?php
            if (is_tax('gsd_category') || is_tax('gsd_location')) {
                echo term_description();
            } else {
                _e('Discover trusted gun shops, read reviews, and find the best firearms dealers near you.', 'gun-shop-directory');
            }
            ?>
        </p>
    </div>

    <!-- Search Form -->
    <div class="gsd-search-wrapper">
        <?php echo do_shortcode('[gsd_search]'); ?>
    </div>

    <?php if (have_posts()) : ?>
        <div class="gsd-results-bar">
            <span class="gsd-results-count">
                <?php
                global $wp_query;
                printf(
                    _n('%s listing found', '%s listings found', $wp_query->found_posts, 'gun-shop-directory'),
                    number_format_i18n($wp_query->found_posts)
                );
                ?>
            </span>

            <div class="gsd-results-controls">
                <div class="gsd-results-sorting">
                    <label for="gsd-layout"><?php _e('Layout:', 'gun-shop-directory'); ?></label>
                    <select id="gsd-layout" name="layout">
                        <option value="grid-large" <?php selected(isset($_COOKIE['gsd_layout']) ? $_COOKIE['gsd_layout'] : 'grid-large', 'grid-large'); ?>><?php _e('Grid - Large', 'gun-shop-directory'); ?></option>
                        <option value="grid-compact" <?php selected(isset($_COOKIE['gsd_layout']) ? $_COOKIE['gsd_layout'] : '', 'grid-compact'); ?>><?php _e('Grid - Compact', 'gun-shop-directory'); ?></option>
                        <option value="grid-minimal" <?php selected(isset($_COOKIE['gsd_layout']) ? $_COOKIE['gsd_layout'] : '', 'grid-minimal'); ?>><?php _e('Grid - Minimal', 'gun-shop-directory'); ?></option>
                        <option value="list-detailed" <?php selected(isset($_COOKIE['gsd_layout']) ? $_COOKIE['gsd_layout'] : '', 'list-detailed'); ?>><?php _e('List - Detailed', 'gun-shop-directory'); ?></option>
                        <option value="list-simple" <?php selected(isset($_COOKIE['gsd_layout']) ? $_COOKIE['gsd_layout'] : '', 'list-simple'); ?>><?php _e('List - Simple', 'gun-shop-directory'); ?></option>
                    </select>
                </div>

                <div class="gsd-results-sorting">
                    <label for="gsd-sort"><?php _e('Sort by:', 'gun-shop-directory'); ?></label>
                    <select id="gsd-sort" name="orderby">
                        <option value="date" <?php selected(get_query_var('orderby'), 'date'); ?>><?php _e('Newest', 'gun-shop-directory'); ?></option>
                        <option value="title" <?php selected(get_query_var('orderby'), 'title'); ?>><?php _e('Name', 'gun-shop-directory'); ?></option>
                        <option value="rating" <?php selected(get_query_var('orderby'), 'rating'); ?>><?php _e('Rating', 'gun-shop-directory'); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <?php
        $layout = isset($_COOKIE['gsd_layout']) ? $_COOKIE['gsd_layout'] : 'grid-large';
        $public = new GSD_Public('gun-shop-directory', GSD_VERSION);
        $container_classes = array(
            'grid-large' => 'gsd-listings-grid gsd-grid-large',
            'grid-compact' => 'gsd-listings-grid gsd-grid-compact',
            'grid-minimal' => 'gsd-listings-grid gsd-grid-minimal',
            'list-simple' => 'gsd-listings-list gsd-list-simple',
            'list-detailed' => 'gsd-listings-list gsd-list-detailed',
        );
        $container_class = isset($container_classes[$layout]) ? $container_classes[$layout] : $container_classes['grid-large'];
        ?>

        <div class="<?php echo esc_attr($container_class); ?>">
            <?php
            while (have_posts()) {
                the_post();

                // Render based on layout
                switch ($layout) {
                    case 'list-simple':
                        // Use a method that exists or inline the simple list
                        echo '<div class="gsd-listing-row gsd-listing-simple">';
                        echo '<div class="gsd-listing-main-info">';
                        echo '<h3 class="gsd-listing-title"><a href="' . get_permalink() . '">' . get_the_title() . '</a></h3>';
                        $city = get_post_meta(get_the_ID(), '_gsd_city', true);
                        $state = get_post_meta(get_the_ID(), '_gsd_state', true);
                        if ($city || $state) {
                            echo '<span class="gsd-listing-location">' . esc_html(trim("$city, $state", ', ')) . '</span>';
                        }
                        echo '</div>';
                        $rating = class_exists('GSD_Reviews') ? GSD_Reviews::get_average_rating(get_the_ID()) : 0;
                        if ($rating > 0) {
                            echo '<div class="gsd-listing-rating">' . GSD_Reviews::render_stars($rating, false) . '</div>';
                        }
                        $phone = get_post_meta(get_the_ID(), '_gsd_phone', true);
                        if ($phone) {
                            echo '<div class="gsd-listing-phone">' . esc_html($phone) . '</div>';
                        }
                        echo '<div class="gsd-listing-action"><a href="' . get_permalink() . '" class="gsd-button gsd-button-primary gsd-button-sm">' . __('View', 'gun-shop-directory') . '</a></div>';
                        echo '</div>';
                        break;
                    case 'list-detailed':
                    case 'grid-compact':
                    case 'grid-minimal':
                        // For these we need to call methods from the public class
                        if (method_exists($public, 'render_listing_by_layout')) {
                            $public->render_listing_by_layout(get_the_ID(), $layout);
                        } else {
                            $public->render_listing_card(get_the_ID());
                        }
                        break;
                    case 'grid-large':
                    default:
                        $public->render_listing_card(get_the_ID());
                        break;
                }
            }
            ?>
        </div>

        <?php
        // Pagination
        the_posts_pagination(array(
            'mid_size' => 2,
            'prev_text' => __('&laquo; Previous', 'gun-shop-directory'),
            'next_text' => __('Next &raquo;', 'gun-shop-directory'),
        ));
        ?>

    <?php else : ?>
        <div class="gsd-no-results">
            <h2><?php _e('No listings found', 'gun-shop-directory'); ?></h2>
            <p><?php _e('Try adjusting your search criteria.', 'gun-shop-directory'); ?></p>
        </div>
    <?php endif; ?>
</div>

<?php
get_footer();
