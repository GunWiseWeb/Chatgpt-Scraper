/**
 * Gun Shop Directory - Public JavaScript
 * Clean rewrite - no complex features, just basic functionality
 */

(function($) {
    'use strict';

    $(document).ready(function() {

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
                        // Replace listings container
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
         * AJAX Pagination
         */
        $(document).on('click', '.gsd-page-link', function(e) {
            e.preventDefault();

            var $link = $(this);
            var $pagination = $link.closest('.gsd-pagination');
            var page = $link.data('page');

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
                        $resultsContainer.replaceWith(response.data.html);

                        if ($resultsCount.length && response.data.count !== undefined) {
                            var countText = response.data.count === 1 ?
                                response.data.count + ' listing found' :
                                response.data.count + ' listings found';
                            $resultsCount.text(countText);
                        }

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
         * Helper functions
         */
        function setCookie(name, value, days) {
            var expires = "";
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "") + expires + "; path=/";
        }

        function getCookie(name) {
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }

        /**
         * Review Form
         */
        $('#gsd-review-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var $message = $form.find('.gsd-form-message');
            var mode = $form.data('mode') || 'create';
            var originalBtnText = $submitBtn.text();

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
                        if (mode === 'create') {
                            $form[0].reset();
                        }
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $message.addClass('error').text(response.data.message || 'An error occurred').show();
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
         * Star rating
         */
        $('.gsd-star-rating input[type="radio"]').on('change', function() {
            var rating = $(this).val();
            var $container = $(this).closest('.gsd-star-rating');
            $container.find('label').each(function() {
                var labelRating = $(this).attr('for').replace('rating-', '');
                if (labelRating <= rating) {
                    $(this).addClass('selected');
                } else {
                    $(this).removeClass('selected');
                }
            });
        });

        /**
         * Smooth scroll
         */
        $('a[href="#reviews"]').on('click', function(e) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $('#reviews').offset().top - 100
            }, 500);
        });

        /**
         * Gallery
         */
        $('.gsd-listing-gallery img').on('click', function() {
            var src = $(this).attr('src');
            window.open(src, '_blank');
        });

        /**
         * Load more reviews
         */
        $('#gsd-load-more-reviews').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var listingId = $btn.data('listing-id');
            var offset = $btn.data('offset');
            var originalText = $btn.text();

            $btn.text('Loading...').prop('disabled', true);

            $.ajax({
                url: gsdPublic.ajax_url,
                type: 'POST',
                data: {
                    action: 'gsd_load_more_reviews',
                    listing_id: listingId,
                    offset: offset
                },
                success: function(response) {
                    if (response.success) {
                        $('.gsd-reviews-list').append(response.data.html);
                        $btn.data('offset', parseInt(offset) + response.data.count);
                        if (!response.data.has_more) {
                            $btn.hide();
                        }
                    }
                },
                error: function() {
                    alert('Failed to load more reviews');
                },
                complete: function() {
                    $btn.text(originalText).prop('disabled', false);
                }
            });
        });

        /**
         * Submit listing
         */
        $(document).on('click', '.gsd-submit-trigger', function(e) {
            e.preventDefault();
            $('#gsd-submit-form').slideToggle();
        });

        $('#gsd-submit-listing-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var $message = $form.find('.gsd-form-message');
            var originalBtnText = $submitBtn.text();

            $submitBtn.prop('disabled', true).text('Submitting...');
            $message.removeClass('success error').hide();

            $.ajax({
                url: gsdPublic.ajax_url,
                type: 'POST',
                data: $form.serialize() + '&action=gsd_submit_listing&nonce=' + gsdPublic.nonce,
                success: function(response) {
                    if (response.success) {
                        $message.addClass('success').text(response.data.message).show();
                        $form[0].reset();
                    } else {
                        $message.addClass('error').text(response.data.message || 'An error occurred').show();
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
         * Claim listing
         */
        $(document).on('click', '.gsd-claim-trigger', function(e) {
            e.preventDefault();
            $('#gsd-claim-modal').fadeIn();
        });

        $(document).on('click', '.gsd-modal-close, .gsd-modal-overlay', function(e) {
            e.preventDefault();
            $(this).closest('.gsd-modal').fadeOut();
        });

        $('#gsd-claim-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var $message = $form.find('.gsd-form-message');
            var originalBtnText = $submitBtn.text();

            $submitBtn.prop('disabled', true).text('Submitting...');
            $message.removeClass('success error').hide();

            $.ajax({
                url: gsdPublic.ajax_url,
                type: 'POST',
                data: $form.serialize() + '&action=gsd_submit_claim&nonce=' + gsdPublic.nonce,
                success: function(response) {
                    if (response.success) {
                        $message.addClass('success').text(response.data.message).show();
                        $form[0].reset();
                        setTimeout(function() {
                            $('#gsd-claim-modal').fadeOut();
                        }, 2000);
                    } else {
                        $message.addClass('error').text(response.data.message || 'An error occurred').show();
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
         * Report listing
         */
        $(document).on('click', '.gsd-report-trigger', function(e) {
            e.preventDefault();
            $('#gsd-report-modal').fadeIn();
        });

        $('#gsd-report-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var $message = $form.find('.gsd-form-message');
            var originalBtnText = $submitBtn.text();

            $submitBtn.prop('disabled', true).text('Submitting...');
            $message.removeClass('success error').hide();

            $.ajax({
                url: gsdPublic.ajax_url,
                type: 'POST',
                data: $form.serialize() + '&action=gsd_submit_report&nonce=' + gsdPublic.nonce,
                success: function(response) {
                    if (response.success) {
                        $message.addClass('success').text(response.data.message).show();
                        $form[0].reset();
                        setTimeout(function() {
                            $('#gsd-report-modal').fadeOut();
                        }, 2000);
                    } else {
                        $message.addClass('error').text(response.data.message || 'An error occurred').show();
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
         * Clear search
         */
        $('.gsd-search-clear').on('click', function(e) {
            e.preventDefault();
            var $form = $('.gsd-search-form');
            $form.find('input[type="text"]').val('');
            $form.find('select').prop('selectedIndex', 0);
        });

        /**
         * Layout switcher
         */
        $('.gsd-layout-option').on('click', function(e) {
            e.preventDefault();
            var layout = $(this).data('layout');
            $('.gsd-layout-option').removeClass('active');
            $(this).addClass('active');
            setCookie('gsd_layout', layout, 30);
            $('#gsd-layout').val(layout);
            $('.gsd-search-form').trigger('submit');
        });

        /**
         * Login trigger
         */
        $(document).on('click', '.gsd-theme-login-trigger', function(e) {
            e.preventDefault();
            window.location.href = gsdPublic.login_url || '/wp-login.php';
        });

    });

})(jQuery);
