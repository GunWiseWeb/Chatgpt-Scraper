/**
 * Gun Shop Directory - Public JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        /**
         * Search Persistence - Restore search when returning from single listing
         */
        // Check if we're on the directory/archive page
        if ($('.gsd-search-form').length) {
            // Save search parameters when form is submitted
            $('.gsd-search-form').on('submit', function() {
                var searchParams = {
                    gsd_search: $(this).find('input[name="gsd_search"]').val(),
                    gsd_location: $(this).find('input[name="gsd_location"]').val(),
                    gsd_type: $(this).find('select[name="gsd_type"]').val()
                };
                sessionStorage.setItem('gsd_last_search', JSON.stringify(searchParams));
                sessionStorage.setItem('gsd_search_timestamp', Date.now());
            });

            // Restore search on page load if coming back within 30 minutes
            var lastSearch = sessionStorage.getItem('gsd_last_search');
            var searchTimestamp = sessionStorage.getItem('gsd_search_timestamp');

            if (lastSearch && searchTimestamp) {
                var timeDiff = Date.now() - parseInt(searchTimestamp);
                var thirtyMinutes = 30 * 60 * 1000;

                // Only restore if within 30 minutes and no current search params in URL
                if (timeDiff < thirtyMinutes && !window.location.search.includes('gsd_search') && !window.location.search.includes('gsd_location')) {
                    try {
                        var params = JSON.parse(lastSearch);
                        // Check if any search values exist
                        if (params.gsd_search || params.gsd_location || params.gsd_type) {
                            $('.gsd-search-form input[name="gsd_search"]').val(params.gsd_search || '');
                            $('.gsd-search-form input[name="gsd_location"]').val(params.gsd_location || '');
                            $('.gsd-search-form select[name="gsd_type"]').val(params.gsd_type || '');

                            // Auto-submit the form to restore results
                            setTimeout(function() {
                                $('.gsd-search-form').submit();
                            }, 100);
                        }
                    } catch(e) {
                        // Invalid JSON, clear storage
                        sessionStorage.removeItem('gsd_last_search');
                    }
                }
            }
        }

        // Clear search persistence when navigating to a single listing
        if ($('.gsd-single-listing').length) {
            // We're on a single listing page, do nothing (preserve storage)
        }

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
         * Layout selector
         */
        $('#gsd-layout').on('change', function() {
            var layout = $(this).val();
            // Set cookie for 30 days
            document.cookie = 'gsd_layout=' + layout + '; path=/; max-age=' + (30 * 24 * 60 * 60);
            // Reload page to apply layout
            window.location.reload();
        });

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

        /**
         * Report Listing Modal
         */
        $(document).on('click', '.gsd-report-trigger', function(e) {
            e.preventDefault();

            var listingId = $(this).data('listing-id');

            $('#gsd-report-listing-id').val(listingId);
            $('#gsd-report-modal').fadeIn(300);
            $('body').addClass('gsd-modal-open');
        });

        $(document).on('click', '.gsd-modal-close, .gsd-modal-overlay', function(e) {
            e.preventDefault();
            $('.gsd-modal').fadeOut(300);
            $('body').removeClass('gsd-modal-open');
        });

        /**
         * Report Form Submission
         */
        $('#gsd-report-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var $message = $form.find('.gsd-form-message');
            var originalBtnText = $submitBtn.text();

            $submitBtn.prop('disabled', true).text('Submitting...');
            $message.removeClass('success error').hide();

            var formData = {
                action: 'gsd_submit_report',
                nonce: gsdPublic.nonce,
                listing_id: $form.find('input[name="listing_id"]').val(),
                reporter_email: $form.find('input[name="reporter_email"]').val(),
                report_reason: $form.find('select[name="report_reason"]').val(),
                report_details: $form.find('textarea[name="report_details"]').val()
            };

            $.ajax({
                url: gsdPublic.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $message.addClass('success').text(response.data.message).show();
                        $form[0].reset();

                        // Close modal after 2 seconds
                        setTimeout(function() {
                            $('#gsd-report-modal').fadeOut(300);
                            $('body').removeClass('gsd-modal-open');
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
         * AJAX Search Form Submission
         */
        $('.gsd-search-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var $resultsContainer = $('.gsd-listings-grid, .gsd-listings-list').first();
            var $resultsCount = $('.gsd-results-count');
            var originalBtnText = $submitBtn.html();

            // Get current layout
            var layout = $('#gsd-layout').val() || 'grid-large';
            if (!layout) {
                layout = getCookie('gsd_layout') || 'grid-large';
            }

            // Show loading state
            $submitBtn.prop('disabled', true).html('<span class="dashicons dashicons-update gsd-spin"></span> Searching...');

            var formData = {
                action: 'gsd_search_listings',
                gsd_location: $form.find('input[name="gsd_location"]').val(),
                gsd_search: $form.find('input[name="gsd_search"]').val(),
                gsd_type: $form.find('select[name="gsd_type"]').val(),
                layout: layout
            };

            $.ajax({
                url: gsdPublic.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Replace just the listings container, not its parent
                        $resultsContainer.replaceWith(response.data.html);

                        // Update count
                        if ($resultsCount.length && response.data.count !== undefined) {
                            var countText = response.data.count === 1 ?
                                response.data.count + ' listing found' :
                                response.data.count + ' listings found';
                            $resultsCount.text(countText);
                        }

                        // Update pagination
                        if (response.data.pagination !== undefined) {
                            var $existingPagination = $('.navigation.pagination, .gsd-pagination');
                            if ($existingPagination.length) {
                                if (response.data.pagination) {
                                    $existingPagination.replaceWith(response.data.pagination);
                                } else {
                                    $existingPagination.remove();
                                }
                            } else if (response.data.pagination) {
                                $('.gsd-listings-grid, .gsd-listings-list').after(response.data.pagination);
                            }
                        }

                        // Scroll to results
                        $('html, body').animate({
                            scrollTop: $('.gsd-results-bar').offset().top - 100
                        }, 300);
                    } else {
                        alert('Search failed. Please try again.');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).html(originalBtnText);
                }
            });
        });

        /**
         * AJAX Pagination - Handle page link clicks
         */
        $(document).on('click', '.gsd-page-link', function(e) {
            e.preventDefault();

            var $link = $(this);
            var $pagination = $link.closest('.gsd-pagination');
            var page = $link.data('page');

            // Get search params from pagination data attributes
            var formData = {
                action: 'gsd_search_listings',
                gsd_location: $pagination.data('location'),
                gsd_search: $pagination.data('search'),
                gsd_type: $pagination.data('type'),
                layout: $pagination.data('layout'),
                paged: page
            };

            var $resultsContainer = $('.gsd-listings-grid, .gsd-listings-list').first();
            var $resultsCount = $('.gsd-results-count');

            $.ajax({
                url: gsdPublic.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Replace listings
                        $resultsContainer.replaceWith(response.data.html);

                        // Update count
                        if ($resultsCount.length && response.data.count !== undefined) {
                            var countText = response.data.count === 1 ?
                                response.data.count + ' listing found' :
                                response.data.count + ' listings found';
                            $resultsCount.text(countText);
                        }

                        // Update pagination
                        if (response.data.pagination !== undefined) {
                            var $existingPagination = $('.navigation.pagination, .gsd-pagination');
                            if ($existingPagination.length) {
                                if (response.data.pagination) {
                                    $existingPagination.replaceWith(response.data.pagination);
                                } else {
                                    $existingPagination.remove();
                                }
                            } else if (response.data.pagination) {
                                $('.gsd-listings-grid, .gsd-listings-list').after(response.data.pagination);
                            }
                        }

                        // Scroll to results
                        $('html, body').animate({
                            scrollTop: $('.gsd-results-bar').offset().top - 100
                        }, 300);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        });

        /**
         * Clear Search Form
         */
        $('.gsd-search-clear').on('click', function(e) {
            e.preventDefault();

            var $form = $(this).closest('.gsd-search-form');

            // Clear all input fields
            $form.find('input[name="gsd_search"]').val('');
            $form.find('input[name="gsd_location"]').val('');
            $form.find('select[name="gsd_type"]').val('');

            // Clear search persistence from sessionStorage
            sessionStorage.removeItem('gsd_last_search');
            sessionStorage.removeItem('gsd_search_timestamp');

            // Always redirect to clean URL without search params - don't use AJAX
            // This ensures proper pagination and avoids loading too many results
            window.location.href = $form.attr('action');
        });

        /**
         * Helper function to get cookie value
         */
        function getCookie(name) {
            var value = "; " + document.cookie;
            var parts = value.split("; " + name + "=");
            if (parts.length === 2) return parts.pop().split(";").shift();
            return null;
        }

        /**
         * Theme Login Trigger - Opens theme's login popup instead of WP login page
         */
        $(document).on('click', '.gsd-theme-login-trigger', function(e) {
            e.preventDefault();

            // Add 'open' class to theme's login popup
            $('.jws-form-login-popup').addClass('open');

            // Alternatively, if the theme uses a trigger button, click it
            // Uncomment the line below if the above doesn't work
            // $('.your-theme-login-button-class').trigger('click');
        });

    });

})(jQuery);
