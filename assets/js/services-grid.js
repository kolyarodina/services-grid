(function ($) {
    'use strict';

    function initSliders(context) {
        $(context).find('.sg-slider').each(function () {
            var $slider = $(this);

            if ($slider.data('initialized')) {
                return;
            }
            $slider.data('initialized', true);

            var $track = $slider.find('.sg-slider-track');
            var $slides = $slider.find('.sg-slide');
            var index = 0;
            var total = $slides.length;

            function update() {
                var offset = -index * 100;
                $track.css('transform', 'translateX(' + offset + '%)');
            }

            $slider.find('.sg-next').on('click', function () {
                index = (index + 1) % total;
                update();
            });

            $slider.find('.sg-prev').on('click', function () {
                index = (index - 1 + total) % total;
                update();
            });

            update();
        });
    }

    function initAppearAnimation(context) {
        var cards = $(context).find('.sg-card');

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        $(entry.target).addClass('sg-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1
            });

            cards.each(function () {
                observer.observe(this);
            });
        } else {
            cards.addClass('sg-visible');
        }
    }

    function fetchPage($wrapper, page, append) {
        var $grid = $wrapper.find('.sg-grid');
        var $loadMore = $wrapper.find('.sg-load-more');
        var $form = $wrapper.find('.sg-filter-form');

        var minPrice = $form.find('input[name="min_price"]').val();
        var maxPrice = $form.find('input[name="max_price"]').val();

        $loadMore.prop('disabled', true);

        $.ajax({
            url: ServicesGridSettings.ajax_url,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'services_grid_load',
                security: ServicesGridSettings.nonce,
                page: page,
                min_price: minPrice,
                max_price: maxPrice
            }
        }).done(function (response) {
            if (response && response.success) {
                if (append) {
                    $grid.append(response.data.html);
                } else {
                    $grid.html(response.data.html);
                }

                $wrapper.attr('data-current-page', page);

                if (response.data.has_more) {
                    $loadMore.show().prop('disabled', false);
                } else {
                    $loadMore.hide();
                }

                initSliders($grid);
                initAppearAnimation($grid);
            } else {
                $loadMore.hide();
            }
        }).fail(function () {
            $loadMore.prop('disabled', false);
        });
    }

    $(function () {

        $('.sg-services-wrapper').each(function () {
            var $wrapper = $(this);
            var $grid = $wrapper.find('.sg-grid');

            initSliders($grid);
            initAppearAnimation($grid);
        });

        $(document).on('click', '.sg-load-more', function () {
            var $btn = $(this);
            var $wrapper = $btn.closest('.sg-services-wrapper');
            var currentPage = parseInt($wrapper.attr('data-current-page'), 10) || 1;
            var nextPage = currentPage + 1;

            fetchPage($wrapper, nextPage, true);
        });

        $(document).on('click', '.sg-filter-apply', function () {
            var $wrapper = $(this).closest('.sg-services-wrapper');

            fetchPage($wrapper, 1, false);
        });
    });

})(jQuery);
