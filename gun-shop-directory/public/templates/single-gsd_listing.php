<?php
/**
 * Single listing template
 *
 * @package Gun_Shop_Directory
 */

get_header();

while (have_posts()) : the_post();
    $listing_id = get_the_ID();

    // Get meta data
    $business_type = get_post_meta($listing_id, '_gsd_business_type', true);
    $address = get_post_meta($listing_id, '_gsd_address', true);
    $city = get_post_meta($listing_id, '_gsd_city', true);
    $state = get_post_meta($listing_id, '_gsd_state', true);
    $zip = get_post_meta($listing_id, '_gsd_zip', true);
    $country = get_post_meta($listing_id, '_gsd_country', true);
    $phone = get_post_meta($listing_id, '_gsd_phone', true);
    $email = get_post_meta($listing_id, '_gsd_email', true);
    $website = get_post_meta($listing_id, '_gsd_website', true);
    $facebook = get_post_meta($listing_id, '_gsd_facebook', true);
    $twitter = get_post_meta($listing_id, '_gsd_twitter', true);
    $instagram = get_post_meta($listing_id, '_gsd_instagram', true);
    $latitude = get_post_meta($listing_id, '_gsd_latitude', true);
    $longitude = get_post_meta($listing_id, '_gsd_longitude', true);

    // Get reviews data
    $average_rating = GSD_Reviews::get_average_rating($listing_id);
    $review_count = GSD_Reviews::get_review_count($listing_id);
    $rating_distribution = GSD_Reviews::get_rating_distribution($listing_id);
    $reviews = GSD_Reviews::get_reviews($listing_id, 'approved');
?>

<div class="gsd-single-listing">
    <div class="gsd-listing-header">
        <div class="gsd-listing-header-content">
            <h1 class="gsd-listing-title"><?php the_title(); ?></h1>

            <?php if ($average_rating > 0) : ?>
                <div class="gsd-listing-rating-summary">
                    <?php echo GSD_Reviews::render_stars($average_rating); ?>
                    <span class="gsd-review-count"><?php printf(__('%d reviews', 'gun-shop-directory'), $review_count); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($business_type) : ?>
                <span class="gsd-badge gsd-badge-<?php echo esc_attr($business_type); ?>">
                    <?php
                    $types = array(
                        'brick_mortar' => __('Brick & Mortar', 'gun-shop-directory'),
                        'ecommerce' => __('E-commerce', 'gun-shop-directory'),
                        'both' => __('Both', 'gun-shop-directory'),
                    );
                    echo isset($types[$business_type]) ? $types[$business_type] : $business_type;
                    ?>
                </span>
            <?php endif; ?>
        </div>

        <?php if (has_post_thumbnail()) : ?>
            <div class="gsd-listing-featured-image">
                <?php the_post_thumbnail('large'); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="gsd-listing-main">
        <div class="gsd-listing-content">
            <!-- Description -->
            <div class="gsd-listing-section">
                <h2><?php _e('About', 'gun-shop-directory'); ?></h2>
                <div class="gsd-listing-description">
                    <?php the_content(); ?>
                </div>
            </div>

            <!-- Categories -->
            <?php
            $categories = get_the_terms($listing_id, 'gsd_category');
            if ($categories && !is_wp_error($categories)) :
            ?>
                <div class="gsd-listing-section">
                    <h3><?php _e('Categories', 'gun-shop-directory'); ?></h3>
                    <div class="gsd-listing-categories">
                        <?php foreach ($categories as $category) : ?>
                            <a href="<?php echo get_term_link($category); ?>" class="gsd-category-tag">
                                <?php echo esc_html($category->name); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Business Hours -->
            <?php
            $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
            $has_hours = false;
            foreach ($days as $day) {
                $open = get_post_meta($listing_id, "_gsd_hours_{$day}_open", true);
                if (!empty($open)) {
                    $has_hours = true;
                    break;
                }
            }

            if ($has_hours) :
            ?>
                <div class="gsd-listing-section">
                    <h2><?php _e('Business Hours', 'gun-shop-directory'); ?></h2>
                    <table class="gsd-hours-table">
                        <?php
                        $day_labels = array(
                            'monday' => __('Monday', 'gun-shop-directory'),
                            'tuesday' => __('Tuesday', 'gun-shop-directory'),
                            'wednesday' => __('Wednesday', 'gun-shop-directory'),
                            'thursday' => __('Thursday', 'gun-shop-directory'),
                            'friday' => __('Friday', 'gun-shop-directory'),
                            'saturday' => __('Saturday', 'gun-shop-directory'),
                            'sunday' => __('Sunday', 'gun-shop-directory'),
                        );

                        foreach ($days as $day) :
                            $open = get_post_meta($listing_id, "_gsd_hours_{$day}_open", true);
                            $close = get_post_meta($listing_id, "_gsd_hours_{$day}_close", true);
                            $closed = get_post_meta($listing_id, "_gsd_hours_{$day}_closed", true);
                        ?>
                            <tr>
                                <td class="gsd-hours-day"><?php echo $day_labels[$day]; ?></td>
                                <td class="gsd-hours-time">
                                    <?php
                                    if ($closed == '1') {
                                        _e('Closed', 'gun-shop-directory');
                                    } elseif (!empty($open) && !empty($close)) {
                                        echo esc_html($open . ' - ' . $close);
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Reviews Section -->
            <div class="gsd-listing-section gsd-reviews-section" id="reviews">
                <h2><?php _e('Customer Reviews', 'gun-shop-directory'); ?></h2>

                <?php if ($review_count > 0) : ?>
                    <!-- Rating Summary -->
                    <div class="gsd-rating-summary">
                        <div class="gsd-rating-summary-score">
                            <div class="gsd-rating-number"><?php echo number_format($average_rating, 1); ?></div>
                            <?php echo GSD_Reviews::render_stars($average_rating, false); ?>
                            <div class="gsd-rating-count"><?php printf(__('%d reviews', 'gun-shop-directory'), $review_count); ?></div>
                        </div>

                        <div class="gsd-rating-distribution">
                            <?php for ($i = 5; $i >= 1; $i--) :
                                $count = isset($rating_distribution[$i]) ? $rating_distribution[$i] : 0;
                                $percentage = $review_count > 0 ? ($count / $review_count) * 100 : 0;
                            ?>
                                <div class="gsd-rating-bar">
                                    <span class="gsd-rating-label"><?php echo $i; ?> ⭐</span>
                                    <div class="gsd-rating-bar-bg">
                                        <div class="gsd-rating-bar-fill" style="width: <?php echo $percentage; ?>%;"></div>
                                    </div>
                                    <span class="gsd-rating-count"><?php echo $count; ?></span>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Review List -->
                    <div class="gsd-reviews-list">
                        <?php foreach ($reviews as $review) :
                            $user = get_userdata($review->user_id);
                        ?>
                            <div class="gsd-review-item">
                                <div class="gsd-review-header">
                                    <div class="gsd-review-author">
                                        <?php echo get_avatar($review->user_id, 48); ?>
                                        <div class="gsd-review-author-info">
                                            <strong><?php echo $user ? esc_html($user->display_name) : __('Anonymous', 'gun-shop-directory'); ?></strong>
                                            <span class="gsd-review-date"><?php echo date_i18n(get_option('date_format'), strtotime($review->created_at)); ?></span>
                                        </div>
                                    </div>
                                    <?php echo GSD_Reviews::render_stars($review->rating, false); ?>
                                </div>
                                <?php if (!empty($review->title)) : ?>
                                    <h4 class="gsd-review-title"><?php echo esc_html($review->title); ?></h4>
                                <?php endif; ?>
                                <div class="gsd-review-content">
                                    <?php echo wp_kses_post(nl2br($review->content)); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p><?php _e('No reviews yet. Be the first to review!', 'gun-shop-directory'); ?></p>
                <?php endif; ?>

                <!-- Review Form -->
                <?php if (is_user_logged_in() || get_option('gsd_allow_anonymous_reviews', '0') == '1') : ?>
                    <?php
                    // Check if user has already reviewed
                    $user_review = null;
                    $is_updating = false;
                    if (is_user_logged_in()) {
                        $user_review = GSD_Reviews::get_user_review($listing_id);
                        $is_updating = !empty($user_review);
                    }
                    ?>

                    <div class="gsd-review-form-wrapper">
                        <?php if ($is_updating) : ?>
                            <div class="gsd-review-notice">
                                <p><strong><?php _e('You\'ve already reviewed this business. You can update your review below.', 'gun-shop-directory'); ?></strong></p>
                            </div>
                            <h3><?php _e('Update Your Review', 'gun-shop-directory'); ?></h3>
                        <?php else : ?>
                            <h3><?php _e('Write a Review', 'gun-shop-directory'); ?></h3>
                        <?php endif; ?>

                        <form id="gsd-review-form" class="gsd-review-form" data-mode="<?php echo $is_updating ? 'update' : 'create'; ?>">
                            <input type="hidden" name="listing_id" value="<?php echo $listing_id; ?>">
                            <?php if ($is_updating) : ?>
                                <input type="hidden" name="review_id" value="<?php echo $user_review->id; ?>">
                            <?php endif; ?>

                            <div class="gsd-form-field">
                                <label><?php _e('Your Rating', 'gun-shop-directory'); ?> *</label>
                                <div class="gsd-star-input">
                                    <?php for ($i = 5; $i >= 1; $i--) : ?>
                                        <input type="radio" name="rating" value="<?php echo $i; ?>" id="star-<?php echo $i; ?>"
                                            <?php echo ($is_updating && $user_review->rating == $i) ? 'checked' : ''; ?> required>
                                        <label for="star-<?php echo $i; ?>">★</label>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div class="gsd-form-field">
                                <label for="review-title"><?php _e('Review Title', 'gun-shop-directory'); ?></label>
                                <input type="text" name="title" id="review-title" class="gsd-input"
                                    value="<?php echo $is_updating ? esc_attr($user_review->title) : ''; ?>">
                            </div>

                            <div class="gsd-form-field">
                                <label for="review-content"><?php _e('Your Review', 'gun-shop-directory'); ?> *</label>
                                <textarea name="content" id="review-content" rows="5" class="gsd-textarea" required><?php echo $is_updating ? esc_textarea($user_review->content) : ''; ?></textarea>
                            </div>

                            <div class="gsd-form-field">
                                <button type="submit" class="gsd-button gsd-button-primary">
                                    <?php echo $is_updating ? __('Update Review', 'gun-shop-directory') : __('Submit Review', 'gun-shop-directory'); ?>
                                </button>
                            </div>

                            <div class="gsd-form-message"></div>
                        </form>
                    </div>
                <?php else : ?>
                    <p class="gsd-login-message">
                        <?php printf(__('Please <a href="%s">log in</a> to write a review.', 'gun-shop-directory'), wp_login_url(get_permalink())); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="gsd-listing-sidebar">
            <!-- Contact Information -->
            <div class="gsd-sidebar-box">
                <h3><?php _e('Contact Information', 'gun-shop-directory'); ?></h3>

                <?php if ($address || $city || $state) : ?>
                    <div class="gsd-contact-item">
                        <span class="dashicons dashicons-location"></span>
                        <div>
                            <?php if ($address) echo esc_html($address) . '<br>'; ?>
                            <?php echo esc_html(trim("$city, $state $zip", ', ')); ?>
                            <?php if ($country) echo '<br>' . esc_html($country); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($phone) : ?>
                    <div class="gsd-contact-item">
                        <span class="dashicons dashicons-phone"></span>
                        <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a>
                    </div>
                <?php endif; ?>

                <?php if ($email) : ?>
                    <div class="gsd-contact-item">
                        <span class="dashicons dashicons-email"></span>
                        <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                    </div>
                <?php endif; ?>

                <?php if ($website) : ?>
                    <div class="gsd-contact-item">
                        <span class="dashicons dashicons-admin-site"></span>
                        <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener"><?php _e('Visit Website', 'gun-shop-directory'); ?></a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Claim Business -->
            <?php
            $is_claimed = get_post_meta($listing_id, '_gsd_claimed', true);
            $claimed_by = get_post_meta($listing_id, '_gsd_claimed_by', true);
            $current_user_id = get_current_user_id();
            $is_owner = $current_user_id && $claimed_by == $current_user_id;
            $pending_claim = get_post_meta($listing_id, '_gsd_claim_pending', true);
            $can_claim = is_user_logged_in() && !$is_claimed && !$pending_claim;
            ?>

            <?php if ($is_owner) : ?>
                <div class="gsd-sidebar-box gsd-claim-box gsd-claimed">
                    <div class="gsd-claim-badge">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <strong><?php _e('Verified Owner', 'gun-shop-directory'); ?></strong>
                    </div>
                    <p><?php _e('You own this listing', 'gun-shop-directory'); ?></p>
                </div>
            <?php elseif ($pending_claim) : ?>
                <div class="gsd-sidebar-box gsd-claim-box gsd-claim-pending">
                    <div class="gsd-claim-badge">
                        <span class="dashicons dashicons-clock"></span>
                        <strong><?php _e('Claim Pending', 'gun-shop-directory'); ?></strong>
                    </div>
                    <p><?php _e('A claim for this business is pending review', 'gun-shop-directory'); ?></p>
                </div>
            <?php elseif ($can_claim) : ?>
                <div class="gsd-sidebar-box gsd-claim-box">
                    <h3><?php _e('Own This Business?', 'gun-shop-directory'); ?></h3>
                    <p><?php _e('Claim your business to update information and respond to reviews.', 'gun-shop-directory'); ?></p>
                    <a href="#" class="gsd-button gsd-button-secondary gsd-claim-trigger" data-listing-id="<?php echo $listing_id; ?>">
                        <span class="dashicons dashicons-businessman"></span>
                        <?php _e('Claim This Business', 'gun-shop-directory'); ?>
                    </a>
                </div>
            <?php elseif (!is_user_logged_in() && !$is_claimed) : ?>
                <div class="gsd-sidebar-box gsd-claim-box">
                    <h3><?php _e('Own This Business?', 'gun-shop-directory'); ?></h3>
                    <p><?php printf(__('<a href="%s">Log in</a> to claim this business.', 'gun-shop-directory'), wp_login_url(get_permalink())); ?></p>
                </div>
            <?php endif; ?>

            <!-- Social Media -->
            <?php if ($facebook || $twitter || $instagram) : ?>
                <div class="gsd-sidebar-box">
                    <h3><?php _e('Follow Us', 'gun-shop-directory'); ?></h3>
                    <div class="gsd-social-links">
                        <?php if ($facebook) : ?>
                            <a href="<?php echo esc_url($facebook); ?>" target="_blank" rel="noopener" class="gsd-social-link">
                                <span class="dashicons dashicons-facebook"></span>
                            </a>
                        <?php endif; ?>
                        <?php if ($twitter) : ?>
                            <a href="<?php echo esc_url($twitter); ?>" target="_blank" rel="noopener" class="gsd-social-link">
                                <span class="dashicons dashicons-twitter"></span>
                            </a>
                        <?php endif; ?>
                        <?php if ($instagram) : ?>
                            <a href="<?php echo esc_url($instagram); ?>" target="_blank" rel="noopener" class="gsd-social-link">
                                <span class="dashicons dashicons-instagram"></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Map -->
            <?php if (get_option('gsd_enable_map', '1') == '1' && $latitude && $longitude) : ?>
                <div class="gsd-sidebar-box">
                    <h3><?php _e('Location', 'gun-shop-directory'); ?></h3>
                    <div id="gsd-map" class="gsd-map" data-lat="<?php echo esc_attr($latitude); ?>" data-lng="<?php echo esc_attr($longitude); ?>"></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Claim Business Modal -->
<div id="gsd-claim-modal" class="gsd-modal" style="display:none;">
    <div class="gsd-modal-overlay"></div>
    <div class="gsd-modal-content">
        <div class="gsd-modal-header">
            <h2><?php _e('Claim This Business', 'gun-shop-directory'); ?></h2>
            <button class="gsd-modal-close">&times;</button>
        </div>
        <div class="gsd-modal-body">
            <p><?php _e('Please provide information to verify that you own or manage this business. An administrator will review your claim.', 'gun-shop-directory'); ?></p>
            <form id="gsd-claim-form">
                <input type="hidden" name="listing_id" id="gsd-claim-listing-id" value="">

                <div class="gsd-form-group">
                    <label><?php _e('Your Name', 'gun-shop-directory'); ?> <span class="required">*</span></label>
                    <input type="text" name="claimant_name" required class="gsd-input">
                </div>

                <div class="gsd-form-group">
                    <label><?php _e('Your Position', 'gun-shop-directory'); ?> <span class="required">*</span></label>
                    <input type="text" name="claimant_position" placeholder="<?php _e('e.g., Owner, Manager', 'gun-shop-directory'); ?>" required class="gsd-input">
                </div>

                <div class="gsd-form-group">
                    <label><?php _e('Business Phone', 'gun-shop-directory'); ?> <span class="required">*</span></label>
                    <input type="tel" name="business_phone" required class="gsd-input">
                </div>

                <div class="gsd-form-group">
                    <label><?php _e('Verification Details', 'gun-shop-directory'); ?> <span class="required">*</span></label>
                    <textarea name="verification_details" rows="4" placeholder="<?php _e('Provide details to help us verify your ownership (e.g., business registration number, EIN, etc.)', 'gun-shop-directory'); ?>" required class="gsd-textarea"></textarea>
                </div>

                <div class="gsd-form-message"></div>

                <div class="gsd-modal-actions">
                    <button type="button" class="gsd-button gsd-modal-close"><?php _e('Cancel', 'gun-shop-directory'); ?></button>
                    <button type="submit" class="gsd-button gsd-button-primary"><?php _e('Submit Claim', 'gun-shop-directory'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
endwhile;

get_footer();
