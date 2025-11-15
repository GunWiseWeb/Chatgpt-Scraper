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
         * Review Sort functionality
         */
        $('#gsd-review-sort').on('change', function() {
            var sortBy = $(this).val();
            var $reviewsList = $('.gsd-reviews-list');
            var $reviews = $reviewsList.find('.gsd-review-item').get();

            $reviews.sort(function(a, b) {
                if (sortBy === 'latest') {
                    // Sort by date (newest first) - based on DOM order
                    return 0; // Keep original order which should be latest first
                } else if (sortBy === 'highest') {
                    // Sort by highest rating
                    var ratingA = $(a).find('.gsd-review-header .gsd-stars').data('rating') ||
                                  $(a).find('.gsd-stars input:checked').length;
                    var ratingB = $(b).find('.gsd-review-header .gsd-stars').data('rating') ||
                                  $(b).find('.gsd-stars input:checked').length;
                    return ratingB - ratingA;
                } else if (sortBy === 'lowest') {
                    // Sort by lowest rating
                    var ratingA = $(a).find('.gsd-review-header .gsd-stars').data('rating') ||
                                  $(a).find('.gsd-stars input:checked').length;
                    var ratingB = $(b).find('.gsd-review-header .gsd-stars').data('rating') ||
                                  $(b).find('.gsd-stars input:checked').length;
                    return ratingA - ratingB;
                }
                return 0;
            });

            // Reorder the reviews
            $.each($reviews, function(idx, review) {
                $reviewsList.append(review);
            });

            // Smooth scroll to reviews
            $('html, body').animate({
                scrollTop: $reviewsList.offset().top - 150
            }, 300);
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
        $(document).on('click', '.gsd-submit-trigger', function(e) {
            e.preventDefault();
            console.log('Add listing button clicked');

            var $submitSection = $('#gsd-submit-form');
            console.log('Submit section found:', $submitSection.length);

            if ($submitSection.length) {
                $submitSection.slideToggle(300, function() {
                    if ($submitSection.is(':visible')) {
                        // Scroll to form
                        $('html, body').animate({
                            scrollTop: $submitSection.offset().top - 100
                        }, 300);
                    }
                });
            } else {
                console.error('Submit form section not found!');
            }
        });

        /**
         * Claim Business Modal
         */
        $(document).on('click', '.gsd-claim-trigger', function(e) {
            e.preventDefault();
            console.log('Claim button clicked');

            var listingId = $(this).data('listing-id');
            console.log('Listing ID:', listingId);

            $('#gsd-claim-listing-id').val(listingId);
            $('#gsd-claim-modal').fadeIn(300);
            $('body').addClass('gsd-modal-open');
        });

        $(document).on('click', '.gsd-modal-close', function(e) {
            e.preventDefault();
            $('#gsd-claim-modal').fadeOut(300);
            $('body').removeClass('gsd-modal-open');
        });

        $(document).on('click', '.gsd-modal-overlay', function(e) {
            e.preventDefault();
            $('#gsd-claim-modal').fadeOut(300);
            $('body').removeClass('gsd-modal-open');
        });

        /**
         * Claim Form Submission
         */
        $('#gsd-claim-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var $message = $form.find('.gsd-form-message');
            var originalBtnText = $submitBtn.text();

            $submitBtn.prop('disabled', true).text('Submitting...');
            $message.removeClass('success error').hide();

            var formData = {
                action: 'gsd_submit_claim',
                nonce: gsdPublic.nonce,
                listing_id: $form.find('input[name="listing_id"]').val(),
                claimant_name: $form.find('input[name="claimant_name"]').val(),
                claimant_position: $form.find('input[name="claimant_position"]').val(),
                business_phone: $form.find('input[name="business_phone"]').val(),
                verification_details: $form.find('textarea[name="verification_details"]').val()
            };

            $.ajax({
                url: gsdPublic.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $message.addClass('success').text(response.data.message).show();
                        $form[0].reset();

                        // Close modal and reload after 2 seconds
                        setTimeout(function() {
                            $('#gsd-claim-modal').fadeOut(300);
                            $('body').removeClass('gsd-modal-open');
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

    });

})(jQuery);
