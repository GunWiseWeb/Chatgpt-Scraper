/**
 * Gun Shop Directory - Public JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        /**
         * Review Form Submission (both create and update)
         */
        $('#gsd-review-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var $message = $form.find('.gsd-form-message');
            var mode = $form.data('mode') || 'create';
            var originalBtnText = $submitBtn.text();

            // Disable submit button
            $submitBtn.prop('disabled', true).text('Submitting...');
            $message.removeClass('success error').hide();

            var formData = {
                action: mode === 'update' ? 'gsd_update_review' : 'gsd_submit_review',
                nonce: gsdPublic.nonce,
                listing_id: $form.find('input[name="listing_id"]').val(),
                rating: $form.find('input[name="rating"]:checked').val(),
                title: $form.find('input[name="title"]').val(),
                content: $form.find('textarea[name="content"]').val()
            };

            // Add review_id if updating
            if (mode === 'update') {
                formData.review_id = $form.find('input[name="review_id"]').val();
            }

            $.ajax({
                url: gsdPublic.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $message.addClass('success').text(response.data.message).show();

                        // Only reset form if creating new review
                        if (mode === 'create') {
                            $form[0].reset();
                        }

                        // Scroll to message
                        $('html, body').animate({
                            scrollTop: $message.offset().top - 100
                        }, 500);

                        // Reload page after 2 seconds to show updated review
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $message.addClass('error').text(response.data.message).show();
                    }
                },
                error: function() {
                    $message.addClass('error').text('An error occurred. Please try again.').show();
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text(originalBtnText);
                }
            });
        });

        /**
         * Google Maps Integration
         */
        if (typeof google !== 'undefined' && google.maps) {
            var $map = $('#gsd-map');
            if ($map.length) {
                var lat = parseFloat($map.data('lat'));
                var lng = parseFloat($map.data('lng'));

                if (lat && lng) {
                    var mapOptions = {
                        center: new google.maps.LatLng(lat, lng),
                        zoom: 15,
                        mapTypeId: google.maps.MapTypeId.ROADMAP
                    };

                    var map = new google.maps.Map($map[0], mapOptions);

                    var marker = new google.maps.Marker({
                        position: new google.maps.LatLng(lat, lng),
                        map: map,
                        title: $('h1.gsd-listing-title').text()
                    });
                }
            }
        }

        /**
         * Sort functionality
         */
        $('#gsd-sort').on('change', function() {
            var orderby = $(this).val();
            var url = new URL(window.location.href);
            url.searchParams.set('orderby', orderby);
            window.location.href = url.toString();
        });

        /**
         * Star rating hover effect
         */
        $('.gsd-star-input label').hover(
            function() {
                $(this).addClass('hover');
                $(this).nextAll('label').addClass('hover');
            },
            function() {
                $('.gsd-star-input label').removeClass('hover');
            }
        );

        /**
         * Smooth scroll to reviews
         */
        $('a[href="#reviews"]').on('click', function(e) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $('#reviews').offset().top - 100
            }, 500);
        });

        /**
         * Image gallery (if needed)
         */
        $('.gsd-listing-gallery img').on('click', function() {
            // Future implementation for lightbox
        });

        /**
         * Load more reviews (pagination)
         */
        var reviewsPage = 1;
        $('#gsd-load-more-reviews').on('click', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var listingId = $btn.data('listing-id');

            $btn.prop('disabled', true).text('Loading...');

            $.ajax({
                url: gsdPublic.ajax_url,
                type: 'POST',
                data: {
                    action: 'gsd_load_more_reviews',
                    listing_id: listingId,
                    page: ++reviewsPage
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        $('.gsd-reviews-list').append(response.data.html);

                        if (!response.data.has_more) {
                            $btn.remove();
                        } else {
                            $btn.prop('disabled', false).text('Load More Reviews');
                        }
                    } else {
                        $btn.remove();
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Load More Reviews');
                }
            });
        });

        /**
         * Toggle submit listing form in directory view
         */
        $('.gsd-submit-toggle-btn').on('click', function(e) {
            e.preventDefault();

            var $submitSection = $('#gsd-directory-submit-form');

            if ($submitSection.length) {
                $submitSection.slideToggle(300, function() {
                    if ($submitSection.is(':visible')) {
                        // Scroll to form
                        $('html, body').animate({
                            scrollTop: $submitSection.offset().top - 100
                        }, 300);
                    }
                });
            }
        });

    });

})(jQuery);
