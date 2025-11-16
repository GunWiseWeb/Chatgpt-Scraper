/**
 * Gun Shop Directory - Minimal JavaScript
 * ONLY search and pagination - nothing else
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        /**
         * AJAX Search Form
         */
        $('.gsd-search-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var $resultsContainer = $('.gsd-listings-grid, .gsd-listings-list').first();
            var originalBtnText = $submitBtn.html();

            $submitBtn.prop('disabled', true).html('Searching...');

            $.ajax({
                url: gsdPublic.ajax_url,
                type: 'POST',
                data: {
                    action: 'gsd_search_listings',
                    gsd_location: $form.find('input[name="gsd_location"]').val(),
                    gsd_search: $form.find('input[name="gsd_search"]').val(),
                    gsd_type: $form.find('select[name="gsd_type"]').val(),
                    layout: 'grid-large'
                },
                success: function(response) {
                    if (response.success) {
                        $resultsContainer.replaceWith(response.data.html);
                        if (response.data.pagination) {
                            $('.gsd-pagination').remove();
                            $('.gsd-listings-grid, .gsd-listings-list').after(response.data.pagination);
                        }
                    }
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
            var $resultsContainer = $('.gsd-listings-grid, .gsd-listings-list').first();

            $.ajax({
                url: gsdPublic.ajax_url,
                type: 'POST',
                data: {
                    action: 'gsd_search_listings',
                    gsd_location: $pagination.data('location'),
                    gsd_search: $pagination.data('search'),
                    gsd_type: $pagination.data('type'),
                    layout: 'grid-large',
                    paged: $link.data('page')
                },
                success: function(response) {
                    if (response.success) {
                        $resultsContainer.replaceWith(response.data.html);
                        if (response.data.pagination) {
                            $('.gsd-pagination').remove();
                            $('.gsd-listings-grid, .gsd-listings-list').after(response.data.pagination);
                        }
                    }
                }
            });
        });

    });

})(jQuery);
