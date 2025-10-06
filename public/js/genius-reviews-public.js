import Swiper from 'swiper';
import 'swiper/css';

(function ($) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */


	$(document).ready(function () {
		let page = 1;
		const productId = $('#gr-grid').data('product-id');
		const grid = $('#gr-grid');
		const btn = $('#gr-load-more');

		const urlParams = new URLSearchParams(window.location.search);
		const sortParam = urlParams.get('gr-sort');

		//Go to review bloc
		if (sortParam) {
			const sortBlock = $('.gr-bloc');
			console.log(sortBlock)

			if (sortBlock.length) {
				$('html, body').animate(
					{ scrollTop: sortBlock.offset().top - 100 },
					600
				);
			}
		}

		//Load more reviews
		btn.on('click', function () {
			page++;
			$.post(GR_PUBLIC.ajax, {
				action: 'load_reviews',
				nonce: GR_PUBLIC.nonce,
				product_id: productId,
				page: page,
				sort: $('#gr-sort').val()
			}, function (res) {
				if (res.success) {
					const $items = $(res.data.html).css({
						opacity: 0,
					});

					grid.append($items);

					$items.each(function (i, el) {
						$(el).delay(i * 30).animate(
							{ opacity: 1, top: 0 },
							{
								duration: 200,
							}
						);
					});

					const firstNew = $items.first();
					if (firstNew.length) {
						$('html, body').animate({
							scrollTop: firstNew.offset().top - 100
						}, 600);
					}

					if (!res.data.has_more) {
						btn.fadeOut();
					}
				}
			});
		});


		// Carousel
		if ($('.gr-swiper').length) {
			$('.gr-swiper').each(function () {
				const $el = $(this);

				const swiper = new Swiper($el[0], {
					slidesPerView: "auto",
					autoplay: {
						delay: 5000,
						disableOnInteraction: false,
					},
					loop: true,
					spaceBetween: 30,
					breakpoints: {
						768: {
							slidesPerView: 2,
						},
						1024: {
							slidesPerView: 3,
							spaceBetween: 50
						},
					},
					navigation: {
						nextEl: $el.find('.swiper-button-next')[0],
						prevEl: $el.find('.swiper-button-prev')[0],
					},
				});

			});
		}

		// Voir plus single review
		$(document).on('click', '.gr-read-more', function () {
			const $btn = $(this);
			const $excerpt = $btn.closest('.gr-excerpt');
			const $fullText = $excerpt.find('.gr-full-text');

			if ($fullText.is(':visible')) {
				$fullText.hide();
				$btn.text('Voir plus');
			} else {
				$fullText.show();
				$btn.text('Voir moins');
			}
		});


	});



})(jQuery);
