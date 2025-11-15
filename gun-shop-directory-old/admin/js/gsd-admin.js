/**
 * Gun Shop Directory - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        /**
         * Approve Review
         */
        $('.gsd-approve-review').on('click', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var reviewId = $btn.data('review-id');
            var $row = $btn.closest('tr');

            if (!confirm('Are you sure you want to approve this review?')) {
                return;
            }

            $btn.prop('disabled', true).text('Approving...');

            $.ajax({
                url: gsdAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'gsd_approve_review',
                    nonce: gsdAdmin.nonce,
                    review_id: reviewId
                },
                success: function(response) {
                    if (response.success) {
                        $row.find('td:nth-child(6)').text('Approved');
                        $btn.remove();
                        showAdminMessage('Review approved successfully.', 'success');
                    } else {
                        showAdminMessage(response.data.message, 'error');
                        $btn.prop('disabled', false).text('Approve');
                    }
                },
                error: function() {
                    showAdminMessage('An error occurred. Please try again.', 'error');
                    $btn.prop('disabled', false).text('Approve');
                }
            });
        });

        /**
         * Delete Review
         */
        $('.gsd-delete-review').on('click', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var reviewId = $btn.data('review-id');
            var $row = $btn.closest('tr');

            if (!confirm('Are you sure you want to delete this review? This action cannot be undone.')) {
                return;
            }

            $btn.prop('disabled', true).text('Deleting...');

            $.ajax({
                url: gsdAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'gsd_delete_review',
                    nonce: gsdAdmin.nonce,
                    review_id: reviewId
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                        });
                        showAdminMessage('Review deleted successfully.', 'success');
                    } else {
                        showAdminMessage(response.data.message, 'error');
                        $btn.prop('disabled', false).text('Delete');
                    }
                },
                error: function() {
                    showAdminMessage('An error occurred. Please try again.', 'error');
                    $btn.prop('disabled', false).text('Delete');
                }
            });
        });

        /**
         * Show admin message
         */
        function showAdminMessage(message, type) {
            var $message = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after($message);

            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }

        /**
         * Toggle business hours based on "Closed" checkbox
         */
        $('input[name^="gsd_hours_"][name$="_closed"]').on('change', function() {
            var $checkbox = $(this);
            var day = $checkbox.attr('name').match(/gsd_hours_(.+?)_closed/)[1];
            var $timeInputs = $('input[name="gsd_hours_' + day + '_open"], input[name="gsd_hours_' + day + '_close"]');

            if ($checkbox.is(':checked')) {
                $timeInputs.prop('disabled', true).css('opacity', '0.5');
            } else {
                $timeInputs.prop('disabled', false).css('opacity', '1');
            }
        }).trigger('change');

        /**
         * Geocoding for address
         */
        if (typeof google !== 'undefined' && google.maps) {
            var geocoder = new google.maps.Geocoder();

            $('#gsd-geocode-address').on('click', function(e) {
                e.preventDefault();

                var address = $('#gsd_address').val();
                var city = $('#gsd_city').val();
                var state = $('#gsd_state').val();
                var zip = $('#gsd_zip').val();

                var fullAddress = [address, city, state, zip].filter(Boolean).join(', ');

                if (!fullAddress) {
                    alert('Please enter an address first.');
                    return;
                }

                geocoder.geocode({ 'address': fullAddress }, function(results, status) {
                    if (status === 'OK') {
                        var location = results[0].geometry.location;
                        $('#gsd_latitude').val(location.lat());
                        $('#gsd_longitude').val(location.lng());
                        alert('Coordinates found and filled in!');
                    } else {
                        alert('Geocoding failed: ' + status);
                    }
                });
            });
        }

        /**
         * Bulk actions for reviews
         */
        $('#doaction, #doaction2').on('click', function(e) {
            var action = $(this).siblings('select').val();

            if (action === 'approve' || action === 'delete') {
                if (!confirm('Are you sure you want to perform this bulk action?')) {
                    e.preventDefault();
                }
            }
        });

    });

})(jQuery);
