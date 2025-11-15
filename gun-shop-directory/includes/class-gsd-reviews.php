<?php
/**
 * Handle reviews and ratings functionality.
 *
 * @package    Gun_Shop_Directory
 * @subpackage Gun_Shop_Directory/includes
 */

class GSD_Reviews {

    /**
     * Initialize the class.
     */
    public function __construct() {
        add_action('wp_ajax_gsd_submit_review', array($this, 'ajax_submit_review'));
        add_action('wp_ajax_nopriv_gsd_submit_review', array($this, 'ajax_submit_review'));
        add_action('wp_ajax_gsd_approve_review', array($this, 'ajax_approve_review'));
        add_action('wp_ajax_gsd_delete_review', array($this, 'ajax_delete_review'));
    }

    /**
     * Get reviews for a listing.
     *
     * @param int $listing_id The listing ID.
     * @param string $status Review status (approved, pending, all).
     * @param int $limit Number of reviews to retrieve.
     * @return array Reviews.
     */
    public static function get_reviews($listing_id, $status = 'approved', $limit = -1) {
        global $wpdb;
        $table = $wpdb->prefix . 'gsd_reviews';

        $where = $wpdb->prepare("listing_id = %d", $listing_id);

        if ($status !== 'all') {
            $where .= $wpdb->prepare(" AND status = %s", $status);
        }

        $limit_clause = $limit > 0 ? $wpdb->prepare("LIMIT %d", $limit) : '';

        $query = "SELECT * FROM $table WHERE $where ORDER BY created_at DESC $limit_clause";

        return $wpdb->get_results($query);
    }

    /**
     * Get average rating for a listing.
     *
     * @param int $listing_id The listing ID.
     * @return float Average rating.
     */
    public static function get_average_rating($listing_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'gsd_reviews';

        $average = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(rating) FROM $table WHERE listing_id = %d AND status = 'approved'",
            $listing_id
        ));

        return $average ? floatval($average) : 0;
    }

    /**
     * Get total review count for a listing.
     *
     * @param int $listing_id The listing ID.
     * @return int Total reviews.
     */
    public static function get_review_count($listing_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'gsd_reviews';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE listing_id = %d AND status = 'approved'",
            $listing_id
        ));

        return intval($count);
    }

    /**
     * Get rating distribution for a listing.
     *
     * @param int $listing_id The listing ID.
     * @return array Rating distribution.
     */
    public static function get_rating_distribution($listing_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'gsd_reviews';

        $distribution = array(
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0,
        );

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT rating, COUNT(*) as count FROM $table WHERE listing_id = %d AND status = 'approved' GROUP BY rating",
            $listing_id
        ));

        foreach ($results as $result) {
            $distribution[$result->rating] = intval($result->count);
        }

        return $distribution;
    }

    /**
     * Add a new review.
     *
     * @param array $data Review data.
     * @return int|false Review ID or false on failure.
     */
    public static function add_review($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'gsd_reviews';

        $defaults = array(
            'listing_id' => 0,
            'user_id' => get_current_user_id(),
            'rating' => 0,
            'title' => '',
            'content' => '',
            'status' => get_option('gsd_require_approval', '1') == '1' ? 'pending' : 'approved',
        );

        $data = wp_parse_args($data, $defaults);

        // Validate
        if (empty($data['listing_id']) || empty($data['rating']) || empty($data['content'])) {
            return false;
        }

        // Check if user already reviewed this listing
        if ($data['user_id'] > 0) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE listing_id = %d AND user_id = %d",
                $data['listing_id'],
                $data['user_id']
            ));

            if ($existing) {
                return false; // User already reviewed
            }
        }

        $inserted = $wpdb->insert(
            $table,
            array(
                'listing_id' => $data['listing_id'],
                'user_id' => $data['user_id'],
                'rating' => $data['rating'],
                'title' => $data['title'],
                'content' => $data['content'],
                'status' => $data['status'],
            ),
            array('%d', '%d', '%d', '%s', '%s', '%s')
        );

        if ($inserted) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Update review status.
     *
     * @param int $review_id Review ID.
     * @param string $status New status.
     * @return bool Success.
     */
    public static function update_review_status($review_id, $status) {
        global $wpdb;
        $table = $wpdb->prefix . 'gsd_reviews';

        return $wpdb->update(
            $table,
            array('status' => $status),
            array('id' => $review_id),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Delete a review.
     *
     * @param int $review_id Review ID.
     * @return bool Success.
     */
    public static function delete_review($review_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'gsd_reviews';

        return $wpdb->delete(
            $table,
            array('id' => $review_id),
            array('%d')
        );
    }

    /**
     * AJAX: Submit a review.
     */
    public function ajax_submit_review() {
        check_ajax_referer('gsd_submit_review', 'nonce');

        // Check if user is logged in or anonymous reviews are allowed
        if (!is_user_logged_in() && get_option('gsd_allow_anonymous_reviews', '0') != '1') {
            wp_send_json_error(array('message' => __('You must be logged in to submit a review.', 'gun-shop-directory')));
        }

        $listing_id = intval($_POST['listing_id']);
        $rating = intval($_POST['rating']);
        $title = sanitize_text_field($_POST['title']);
        $content = sanitize_textarea_field($_POST['content']);

        if (empty($listing_id) || empty($rating) || empty($content)) {
            wp_send_json_error(array('message' => __('Please fill in all required fields.', 'gun-shop-directory')));
        }

        if ($rating < 1 || $rating > 5) {
            wp_send_json_error(array('message' => __('Invalid rating value.', 'gun-shop-directory')));
        }

        $review_id = self::add_review(array(
            'listing_id' => $listing_id,
            'rating' => $rating,
            'title' => $title,
            'content' => $content,
        ));

        if ($review_id) {
            $message = get_option('gsd_require_approval', '1') == '1'
                ? __('Thank you! Your review has been submitted and is pending approval.', 'gun-shop-directory')
                : __('Thank you! Your review has been posted.', 'gun-shop-directory');

            wp_send_json_success(array('message' => $message));
        } else {
            wp_send_json_error(array('message' => __('Failed to submit review. You may have already reviewed this listing.', 'gun-shop-directory')));
        }
    }

    /**
     * AJAX: Approve a review.
     */
    public function ajax_approve_review() {
        check_ajax_referer('gsd_admin_reviews', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'gun-shop-directory')));
        }

        $review_id = intval($_POST['review_id']);

        if (self::update_review_status($review_id, 'approved')) {
            wp_send_json_success(array('message' => __('Review approved.', 'gun-shop-directory')));
        } else {
            wp_send_json_error(array('message' => __('Failed to approve review.', 'gun-shop-directory')));
        }
    }

    /**
     * AJAX: Delete a review.
     */
    public function ajax_delete_review() {
        check_ajax_referer('gsd_admin_reviews', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'gun-shop-directory')));
        }

        $review_id = intval($_POST['review_id']);

        if (self::delete_review($review_id)) {
            wp_send_json_success(array('message' => __('Review deleted.', 'gun-shop-directory')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete review.', 'gun-shop-directory')));
        }
    }

    /**
     * Render star rating HTML.
     *
     * @param float $rating Rating value.
     * @param bool $show_number Show rating number.
     * @return string HTML output.
     */
    public static function render_stars($rating, $show_number = true) {
        $rating = floatval($rating);
        $full_stars = floor($rating);
        $half_star = ($rating - $full_stars) >= 0.5;
        $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

        $output = '<div class="gsd-star-rating">';

        // Full stars
        for ($i = 0; $i < $full_stars; $i++) {
            $output .= '<span class="gsd-star gsd-star-full">★</span>';
        }

        // Half star
        if ($half_star) {
            $output .= '<span class="gsd-star gsd-star-half">★</span>';
        }

        // Empty stars
        for ($i = 0; $i < $empty_stars; $i++) {
            $output .= '<span class="gsd-star gsd-star-empty">☆</span>';
        }

        if ($show_number) {
            $output .= ' <span class="gsd-rating-number">' . number_format($rating, 1) . '</span>';
        }

        $output .= '</div>';

        return $output;
    }
}
