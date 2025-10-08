<?php
if (! defined('ABSPATH')) exit;

class Genius_Reviews_Admin_Page
{

	public static function add_menu()
	{
		add_submenu_page(
			'edit.php?post_type=genius_review',
			__('Options & Import', 'genius-reviews'),
			__('Options & Import', 'genius-reviews'),
			'manage_woocommerce',
			'gr-options',
			[__CLASS__, 'render_page']
		);

	}

	public static function render_page()
	{
		if (! current_user_can('manage_woocommerce')) wp_die('Nope.');

		if (!empty($_POST['gr_options_nonce']) && wp_verify_nonce($_POST['gr_options_nonce'], 'gr_save_options')) {
			update_option('gr_option_active_reviews_on_product_page', !empty($_POST['gr_option_active_reviews_on_product_page']) ? 1 : 0);
			update_option('gr_option_active_badge_on_product_page', !empty($_POST['gr_option_active_badge_on_product_page']) ? 1 : 0);
			update_option('gr_option_active_badge_on_collection_page', !empty($_POST['gr_option_active_badge_on_collection_page']) ? 1 : 0);


			if (isset($_POST['gr_color_brand_custom'])) {
				update_option('gr_color_brand_custom', sanitize_hex_color($_POST['gr_color_brand_custom']));
			}
			echo '<div class="notice notice-success is-dismissible"><p>' . __('Options sauvegardées.', 'genius-reviews') . '</p></div>';
		}

		$active_reviews_on_product_page   = (int) get_option('gr_option_active_reviews_on_product_page', 0);
		$active_badge_on_product_page = (int) get_option('gr_option_active_badge_on_product_page', 0);
		$active_badge_on_collection_page = (int) get_option('gr_option_active_badge_on_collection_page', 0);

		$color_brand_custom = get_option('gr_color_brand_custom', '#58AF59');
		?>
		<div class="wrap !p-0">
			<div class="tw bg-white container mx-auto p-6">
				<h1 class="text-2xl font-semibold mb-6"><?php _e('Genius Reviews — Options & Import CSV', 'genius-reviews'); ?></h1>

				<!-- Options -->
				<form method="post" class="mb-8 bg-white border rounded-2xl shadow-sm p-6 space-y-3">
					<?php wp_nonce_field('gr_save_options', 'gr_options_nonce'); ?>
					<h2 class="text-lg font-medium"><?php _e('Options d’affichage', 'genius-reviews'); ?></h2>
					<p class="text-gray-600 text-sm leading-relaxed">
						<?php _e(
							'Vous pouvez activer automatiquement la <strong>grille d’avis</strong> en bas des pages produits WooCommerce, 
                        ou utiliser les shortcodes suivants :',
							'genius-reviews'
						); ?>
						<br>
						<code>[genius_reviews_grid product_id="123" limit="6"]</code> <?php _e('ou simplement', 'genius-reviews'); ?> <code>[genius_reviews_grid]</code>
						<?php _e('pour le produit courant.', 'genius-reviews'); ?><br>
						<?php _e('Pour le carrousel, utilisez :', 'genius-reviews'); ?> <code>[genius_reviews_slider]</code>.
					</p>

					<div class="space-y-2">
						<div class="flex items-center gap-3">
							<label for="gr-option-reviews-on-product-page" class="text-sm font-medium text-gray-800">
								<?php _e('Activer la grille des avis sur la page produit', 'genius-reviews'); ?>
							</label>

							<label for="gr-option-reviews-on-product-page"
								class="relative block h-6 w-11 rounded-full bg-gray-300 transition-colors cursor-pointer has-[:checked]:bg-[var(--color-brand)]">

								<input type="checkbox"
									id="gr-option-reviews-on-product-page"
									name="gr_option_active_reviews_on_product_page"
									value="1"
									class="sr-only peer"
									<?php checked($active_reviews_on_product_page, 1); ?>>

								<span
									class="absolute inset-y-0 start-0 m-[2px] size-5 rounded-full bg-white shadow transition-[inset-inline-start] peer-checked:start-5">
								</span>
							</label>
						</div>

						<div class="flex items-center gap-3">
							<label for="gr-option-badge-on-product-page" class="text-sm font-medium text-gray-800">
								<?php _e('Activer le badge d\'avis sur la page produit', 'genius-reviews'); ?>
							</label>

							<label for="gr-option-badge-on-product-page"
								class="relative block h-6 w-11 rounded-full bg-gray-300 transition-colors cursor-pointer has-[:checked]:bg-[var(--color-brand)]">

								<input type="checkbox"
									id="gr-option-badge-on-product-page"
									name="gr_option_active_badge_on_product_page"
									value="1"
									class="sr-only peer"
									<?php checked($active_badge_on_product_page, 1); ?>>

								<span
									class="absolute inset-y-0 start-0 m-[2px] size-5 rounded-full bg-white shadow transition-[inset-inline-start] peer-checked:start-5">
								</span>
							</label>
						</div>

						<div class="flex items-center gap-3">
							<label for="gr-option-badge-on-collection-page" class="text-sm font-medium text-gray-800">
								<?php _e('Activer le badge d\'avis sur la page collection', 'genius-reviews'); ?>
							</label>

							<label for="gr-option-badge-on-collection-page"
								class="relative block h-6 w-11 rounded-full bg-gray-300 transition-colors cursor-pointer has-[:checked]:bg-[var(--color-brand)]">

								<input type="checkbox"
									id="gr-option-badge-on-collection-page"
									name="gr_option_active_badge_on_collection_page"
									value="1"
									class="sr-only peer"
									<?php checked($active_badge_on_collection_page, 1); ?>>

								<span
									class="absolute inset-y-0 start-0 m-[2px] size-5 rounded-full bg-white shadow transition-[inset-inline-start] peer-checked:start-5">
								</span>
							</label>
						</div>

					</div>
					

					<div class="flex gap-2 items-center">
						<label for="gr-color-brand-custom" class="block text-sm font-medium">
							<?php _e('Couleur principale', 'genius-reviews'); ?>
						</label>
						<input type="color"
							id="gr-color-brand-custom"
							name="gr_color_brand_custom"
							value="<?php echo esc_attr($color_brand_custom); ?>"
							class="w-12 h-8 border rounded cursor-pointer p-0">
					</div>


					<button type="submit" class="gr-btn">
						<?php _e('Sauvegarder', 'genius-reviews'); ?>
					</button>
				</form>

				<!-- Import -->
				<div class="bg-white border rounded-2xl shadow-sm p-6 space-y-4">
					<h2 class="text-lg font-medium mb-4"><?php _e('Import', 'genius-reviews'); ?></h2>

					<form id="gr-upload-form" method="post" class="space-y-3" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" enctype="multipart/form-data">
						<input type="hidden" name="action" value="gr_upload_csv" />
						<input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('gr_import_nonce')); ?>" />

						<label class="block">
							<span class="block text-sm font-medium mb-1">Fichier CSV</span>
							<input class="block w-full border !rounded-lg !px-3 !py-2" type="file" name="csv" accept=".csv" required>
						</label>

						<button type="submit"
							class="gr-btn">
							<?php _e('Lancer l’import', 'genius-reviews'); ?>
						</button>
					</form>

					<div id="gr-progress" class="hidden space-y-2">
						<div class="flex justify-between text-sm">
							<span id="gr-progress-label" class="text-slate-600"><?php _e('Préparation…', 'genius-reviews'); ?></span>
							<span><span id="gr-progress-percent">0</span>%</span>
						</div>
						<div class="w-full bg-slate-200/80 rounded-full h-2 overflow-hidden">
							<div id="gr-progress-bar" class="h-2 bg-emerald-500 transition-all duration-500 ease-in-out" style="width: 0%;"></div>
						</div>
						<div class="text-xs text-slate-500" id="gr-stats-line"></div>
					</div>

					<div id="gr-per-product" class="mt-8 bg-white border rounded-2xl shadow-sm p-6">
						<h2 class="text-lg font-medium mb-4"><?php _e('Détail par produit', 'genius-reviews'); ?></h2>
						<div class="overflow-x-auto">
							<table class="min-w-full text-sm">
								<thead>
									<tr class="text-left text-slate-600">
										<th class="py-2"><?php _e('Produit', 'genius-reviews'); ?></th>
										<th class="py-2"><?php _e('ID', 'genius-reviews'); ?></th>
										<th class="py-2"><?php _e('Ajoutés', 'genius-reviews'); ?></th>
										<th class="py-2"><?php _e('MàJ', 'genius-reviews'); ?></th>
										<th class="py-2"><?php _e('Ignorés', 'genius-reviews'); ?></th>
										<th class="py-2"><?php _e('Moyenne', 'genius-reviews'); ?></th>
										<th class="py-2"><?php _e('Total', 'genius-reviews'); ?></th>
									</tr>
								</thead>
								<tbody id="gr-product-rows"></tbody>
							</table>
						</div>
					</div>

					<p class="mt-6 text-xs text-slate-500">
						<?php _e('Colonnes :', 'genius-reviews'); ?>
						<code>title, body, rating, review_date, source, curated, reviewer_name, reviewer_email, product_id, product_handle, reply, reply_date, picture_urls, ip_address, location</code>
					</p>
				</div>


			</div>
		</div>
<?php
	}
}
