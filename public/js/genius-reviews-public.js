import Swiper from 'swiper/bundle';
import 'swiper/css/bundle';


(function ($) {
	'use strict';

	$(document).ready(function () {
		const btn = $('#gr-load-more');
		let page = 1;


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

		$(document).on('click', '.gr-badge', function (e) {
			e.preventDefault();

			const reviewBlock = $('.gr-bloc');
			if (reviewBlock.length) {
				$('html, body').animate(
					{ scrollTop: reviewBlock.offset().top - 100 },
					600
				);
			}
		});


		btn.on('click', function () {
			page++;

			// Onglet actif (products ou shop)
			const activeTab = $('.gr-tab.gr-tab-active').data('tab') || 'products';
			const activeGrid = $(`#gr-tab-${activeTab} .gr-grid`);
			const activeProductId = activeGrid.data('product-id') || 0;

			// Totaux et limite récupérés depuis le bouton global
			const limit = parseInt(btn.data('limit')) || 12;
			const totalProducts = parseInt(btn.data('total-products')) || 0;
			const totalShop = parseInt(btn.data('total-shop')) || 0;
			const totalForTab = activeTab === 'shop' ? totalShop : totalProducts;
			const mode = btn.data('mode') || '';
			const currentCount = activeGrid.children().length;

			if (currentCount >= totalForTab) {
				btn.fadeOut();
				return;
			}

			$.post(GR_PUBLIC.ajax, {
				action: 'load_reviews',
				nonce: GR_PUBLIC.nonce,
				product_id: activeProductId,
				page,
				limit,
				sort: $('#gr-sort').val(),
				mode
			}, function (res) {
				if (res.success) {
					const $items = $(res.data.html).css({ opacity: 0 });
					activeGrid.append($items);

					$items.each(function (i, el) {
						$(el).delay(i * 30).animate({ opacity: 1, top: 0 }, 200);
					});

					const firstNew = $items.first();
					if (firstNew.length) {
						$('html, body').animate({
							scrollTop: firstNew.offset().top - 100
						}, 600);
					}

					const updatedCount = activeGrid.children().length;
					if (updatedCount >= totalForTab || !res.data.has_more) {
						btn.fadeOut();
					}
				}
			});
		});

		// Gestion du changement d’onglet
		$(document).on('click', '.gr-tab', function () {
			const $tab = $(this);
			const id = $tab.data('tab');

			$('.gr-tab').removeClass('gr-tab-active');
			$tab.addClass('gr-tab-active');

			$('.gr-tab-content').addClass('hidden');
			$(`#gr-tab-${id}`).removeClass('hidden');

			const btn = $('#gr-load-more');
			const limit = parseInt(btn.data('limit')) || 12;
			const totalProducts = parseInt(btn.data('total-products')) || 0;
			const totalShop = parseInt(btn.data('total-shop')) || 0;
			const totalForTab = id === 'shop' ? totalShop : totalProducts;
			const activeGrid = $(`#gr-tab-${id} .gr-grid`);
			const currentCount = activeGrid.children().length;

			if (currentCount >= totalForTab) {
				btn.fadeOut();
			} else {
				btn.fadeIn();
			}
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
