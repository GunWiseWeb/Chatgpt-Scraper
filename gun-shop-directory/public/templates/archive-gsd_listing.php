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

            <div class="gsd-results-sorting">
                <label for="gsd-sort"><?php _e('Sort by:', 'gun-shop-directory'); ?></label>
                <select id="gsd-sort" name="orderby">
                    <option value="date" <?php selected(get_query_var('orderby'), 'date'); ?>><?php _e('Newest', 'gun-shop-directory'); ?></option>
                    <option value="title" <?php selected(get_query_var('orderby'), 'title'); ?>><?php _e('Name', 'gun-shop-directory'); ?></option>
                    <option value="rating" <?php selected(get_query_var('orderby'), 'rating'); ?>><?php _e('Rating', 'gun-shop-directory'); ?></option>
                </select>
            </div>
        </div>

        <div class="gsd-listings-grid">
            <?php
            while (have_posts()) {
                the_post();
                $public = new GSD_Public('gun-shop-directory', GSD_VERSION);
                $public->render_listing_card(get_the_ID());
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
