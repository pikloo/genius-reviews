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
			update_option('gr_option_fallback_reviews_all', !empty($_POST['gr_option_fallback_reviews_all']) ? 1 : 0);


			if (isset($_POST['gr_color_brand_custom'])) {
				update_option('gr_color_brand_custom', sanitize_hex_color($_POST['gr_color_brand_custom']));
			}

			echo '<div class="notice notice-success is-dismissible"><p>' . __('Options sauvegardées.', 'genius-reviews') . '</p></div>';
		}

		$active_reviews_on_product_page   = (int) get_option('gr_option_active_reviews_on_product_page', 0);
		$active_badge_on_product_page = (int) get_option('gr_option_active_badge_on_product_page', 0);
		$active_badge_on_collection_page = (int) get_option('gr_option_active_badge_on_collection_page', 0);
		$fallback_reviews_all = (int) get_option('gr_option_fallback_reviews_all', 0);

		$color_brand_custom = get_option('gr_color_brand_custom', '#58AF59');
		?>
		<div class="wrap !p-0">
			<div class="tw bg-white container mx-auto p-6">
				<h1 class="text-2xl font-semibold mb-6"><?php _e('Genius Reviews — Options & Import CSV', 'genius-reviews'); ?></h1>

				<!-- Options -->
				<form method="post" class="mb-8 bg-white border rounded-2xl shadow-sm p-6 space-y-6">
	<?php wp_nonce_field('gr_save_options', 'gr_options_nonce'); ?>

	<!-- HEADER -->
	<div class="flex items-start justify-between gap-4">
		<div>
			<h2 class="text-xl font-semibold mb-1">
				<?php _e('Options d’affichage', 'genius-reviews'); ?>
			</h2>
			<p class="text-gray-600 leading-relaxed">
				<?php _e(
					'Activez automatiquement les blocs côté boutique et/ou utilise les shortcodes ci-dessous selon vos besoins.',
					'genius-reviews'
				); ?>
			</p>
		</div>
		<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
			<?php esc_html_e('Genius Reviews', 'genius-reviews'); ?>
		</span>
	</div>

	<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
		<div class="space-y-4">
			<div class="p-4 border rounded-xl">
				<label for="gr-option-reviews-on-product-page" class="block text-sm font-medium text-gray-900 mb-1">
					<?php _e('Activer la grille des avis sur la page produit', 'genius-reviews'); ?>
				</label>
				<div class="flex items-center justify-between">
					<p class="text-sm text-gray-600 max-w-6/10">
						<?php _e('Affiche la grille en bas de chaque fiche produit (si des avis sont disponibles).', 'genius-reviews'); ?>
					</p>
					<label for="gr-option-reviews-on-product-page"
						   class="relative block h-6 w-11 rounded-full bg-gray-300 transition-colors cursor-pointer has-[:checked]:bg-[var(--color-brand)]">
						<input type="checkbox"
							   id="gr-option-reviews-on-product-page"
							   name="gr_option_active_reviews_on_product_page"
							   value="1"
							   class="sr-only peer"
							   <?php checked($active_reviews_on_product_page, 1); ?>>
						<span class="absolute inset-y-0 start-0 m-[2px] size-5 rounded-full bg-white shadow transition-[inset-inline-start] peer-checked:start-5"></span>
					</label>
				</div>
			</div>

			<div class="p-4 border rounded-xl">
				<label for="gr-option-fallback-reviews-all" class="block text-sm font-medium text-gray-900 mb-1">
					<?php _e('Afficher des avis globaux si le produit n\'en a pas', 'genius-reviews'); ?>
				</label>
				<div class="flex items-center justify-between">
					<p class="text-sm text-gray-600 max-w-6/10">
						<?php _e('Si la fiche produit n’a aucun avis, afficher les avis globaux du catalogue à la place (la moyenne du produit ne change pas).', 'genius-reviews'); ?>
					</p>
					<label for="gr-option-fallback-reviews-all"
						   class="relative block h-6 w-11 rounded-full bg-gray-300 transition-colors cursor-pointer has-[:checked]:bg-[var(--color-brand)]">
						<input type="checkbox"
							   id="gr-option-fallback-reviews-all"
							   name="gr_option_fallback_reviews_all"
							   value="1"
							   class="sr-only peer"
							   <?php checked($fallback_reviews_all, 1); ?>>
						<span class="absolute inset-y-0 start-0 m-[2px] size-5 rounded-full bg-white shadow transition-[inset-inline-start] peer-checked:start-5"></span>
					</label>
				</div>
			</div>

			<div class="p-4 border rounded-xl">
				<label for="gr-option-badge-on-product-page" class="block text-sm font-medium text-gray-900 mb-1">
					<?php _e('Activer le badge d\'avis sur la page produit', 'genius-reviews'); ?>
				</label>
				<div class="flex items-center justify-between">
					<p class="text-sm text-gray-600 max-w-6/10">
						<?php _e('Petit badge (moyenne + nombre d’avis) visible en haut de la fiche produit.', 'genius-reviews'); ?>
					</p>
					<label for="gr-option-badge-on-product-page"
						   class="relative block h-6 w-11 rounded-full bg-gray-300 transition-colors cursor-pointer has-[:checked]:bg-[var(--color-brand)]">
						<input type="checkbox"
							   id="gr-option-badge-on-product-page"
							   name="gr_option_active_badge_on_product_page"
							   value="1"
							   class="sr-only peer"
							   <?php checked($active_badge_on_product_page, 1); ?>>
						<span class="absolute inset-y-0 start-0 m-[2px] size-5 rounded-full bg-white shadow transition-[inset-inline-start] peer-checked:start-5"></span>
					</label>
				</div>
			</div>

			<div class="p-4 border rounded-xl">
				<label for="gr-option-badge-on-collection-page" class="block text-sm font-medium text-gray-900 mb-1">
					<?php _e('Activer le badge d\'avis sur la page collection', 'genius-reviews'); ?>
				</label>
				<div class="flex items-center justify-between">
					<p class="text-sm text-gray-600 max-w-6/10">
						<?php _e('Affiche le badge dans les listings/collections produits.', 'genius-reviews'); ?>
					</p>
					<label for="gr-option-badge-on-collection-page"
						   class="relative block h-6 w-11 rounded-full bg-gray-300 transition-colors cursor-pointer has-[:checked]:bg-[var(--color-brand)]">
						<input type="checkbox"
							   id="gr-option-badge-on-collection-page"
							   name="gr_option_active_badge_on_collection_page"
							   value="1"
							   class="sr-only peer"
							   <?php checked($active_badge_on_collection_page, 1); ?>>
						<span class="absolute inset-y-0 start-0 m-[2px] size-5 rounded-full bg-white shadow transition-[inset-inline-start] peer-checked:start-5"></span>
					</label>
				</div>
			</div>
		</div>

		<div class="space-y-4">
			<div class="p-4 border rounded-xl">
				<label for="gr-color-brand-custom" class="block text-sm font-medium text-gray-900 mb-2">
					<?php _e('Couleur principale', 'genius-reviews'); ?>
				</label>
				<div class="flex items-center gap-3">
					<input type="text"
						   id="gr-color-brand-custom"
						   name="gr_color_brand_custom"
						   value="<?php echo esc_attr($color_brand_custom); ?>"
						   class="border rounded cursor-pointer p-0"
						   data-coloris>
					<span class="text-sm text-gray-600">
						<?php _e('Utilisée pour les boutons et accents (hover auto-généré).', 'genius-reviews'); ?>
					</span>
				</div>
			</div>

			<div class="p-4 border rounded-xl">
				<h3 class="text-sm font-semibold mb-3"><?php _e('Shortcodes disponibles', 'genius-reviews'); ?></h3>

				<ul class="space-y-3">
					<li>
						<p class="text-xs text-gray-500 mb-1"><?php _e('Grille d’avis (produit courant ou ID précis)', 'genius-reviews'); ?></p>
						<div class="flex items-center gap-2">
							<code class="px-2 py-1 bg-gray-100 rounded text-sm">[genius_reviews_grid]</code>
							<button type="button" class="gr-copy px-2 py-1 text-xs border rounded" data-copy="[genius_reviews_grid]">
								<?php _e('Copier', 'genius-reviews'); ?>
							</button>
						</div>
						<div class="flex items-center gap-2 mt-1">
							<code class="px-2 py-1 bg-gray-100 rounded text-sm">[genius_reviews_grid product_id="123" limit="6" sort="date_desc"]</code>
							<button type="button" class="gr-copy px-2 py-1 text-xs border rounded" data-copy='[genius_reviews_grid product_id="123" limit="6" sort="date_desc"]'>
								<?php _e('Copier', 'genius-reviews'); ?>
							</button>
						</div>
					</li>

					<li>
						<p class="text-xs text-gray-500 mb-1"><?php _e('Carrousel (sélection d’avis globales)', 'genius-reviews'); ?></p>
						<div class="flex items-center gap-2">
							<code class="px-2 py-1 bg-gray-100 rounded text-sm">[genius_reviews_slider limit="12" sort="rating_desc"]</code>
							<button type="button" class="gr-copy px-2 py-1 text-xs border rounded" data-copy='[genius_reviews_slider limit="12" sort="rating_desc"]'>
								<?php _e('Copier', 'genius-reviews'); ?>
							</button>
						</div>
					</li>

					<li>
						<p class="text-xs text-gray-500 mb-1"><?php _e('Badge (produit courant ou ID précis) (dans un template/page au choix)', 'genius-reviews'); ?></p>
						<div class="flex items-center gap-2">
							<code class="px-2 py-1 bg-gray-100 rounded text-sm">[genius_reviews_badge]</code>
							<button type="button" class="gr-copy px-2 py-1 text-xs border rounded" data-copy="[genius_reviews_badge]">
								<?php _e('Copier', 'genius-reviews'); ?>
							</button>
						</div>
						<div class="flex items-center gap-2 mt-1">
							<code class="px-2 py-1 bg-gray-100 rounded text-sm">[genius_reviews_badge product_id="123"]</code>
							<button type="button" class="gr-copy px-2 py-1 text-xs border rounded" data-copy='[genius_reviews_badge product_id="123"]'>
								<?php _e('Copier', 'genius-reviews'); ?>
							</button>
						</div>
					</li>

					<li>
						<p class="text-xs text-gray-500 mb-1">
							<?php _e('Tous les avis (onglets Produits / Boutique + stats globales)', 'genius-reviews'); ?>
						</p>
						<div class="flex items-center gap-2">
							<code class="px-2 py-1 bg-gray-100 rounded text-sm">[genius_reviews_all limit="12" sort="date_desc"]</code>
							<button type="button" class="gr-copy px-2 py-1 text-xs border rounded" data-copy='[genius_reviews_all limit="12" sort="date_desc"]'>
								<?php _e('Copier', 'genius-reviews'); ?>
							</button>
						</div>
					</li>
				</ul>

				<p class="text-[11px] text-gray-500 mt-3">
					<?php _e('Paramètres de tri disponibles : date_desc, date_asc, rating_desc, rating_asc.', 'genius-reviews'); ?>
				</p>
			</div>
		</div>
	</div>

	<!-- SUBMIT -->
	<div class="pt-2 border-t">
		<button type="submit" class="gr-btn">
			<?php _e('Sauvegarder', 'genius-reviews'); ?>
		</button>
	</div>
</form>

				<!-- Synchronisation -->
				<div class="bg-white border rounded-2xl shadow-sm p-6 space-y-4 my-8">
					<h2 class="text-lg font-medium"><?php _e('Synchronisation', 'genius-reviews'); ?></h2>
					<p class="text-sm text-gray-600">
						<?php _e('Recalcule la moyenne et le volume d’avis stockés sur vos fiches produits.', 'genius-reviews'); ?>
					</p>
					<div class="flex flex-col gap-3 sm:flex-row sm:items-center">
						<button type="button"
							id="gr-sync-ratings"
							class="gr-btn w-full sm:w-auto"
							data-ajax="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
							data-nonce="<?php echo esc_attr(wp_create_nonce('gr_sync_products')); ?>">
							<?php _e('Synchroniser les notes produits', 'genius-reviews'); ?>
						</button>
						<span id="gr-sync-status" class="text-sm text-gray-600"></span>
					</div>
				</div>

<script>
	// Petit copier/coller pour les shortcodes
	document.querySelectorAll('.gr-copy').forEach(btn => {
		btn.addEventListener('click', () => {
			const txt = btn.getAttribute('data-copy') || '';
			navigator.clipboard.writeText(txt).then(() => {
				btn.textContent = '<?php echo esc_js( __( 'Copié !', 'genius-reviews' ) ); ?>';
				setTimeout(()=>{ btn.textContent = '<?php echo esc_js( __( 'Copier', 'genius-reviews' ) ); ?>'; }, 1200);
			});
		});
	});

	const syncBtn = document.getElementById('gr-sync-ratings');
	if (syncBtn) {
		const syncStatus = document.getElementById('gr-sync-status');
		const syncingLabel = '<?php echo esc_js(__('Synchronisation en cours…', 'genius-reviews')); ?>';
		const successLabel = '<?php echo esc_js(__('Synchronisation terminée : %d produit(s) mis à jour.', 'genius-reviews')); ?>';
		const errorLabel = '<?php echo esc_js(__('Erreur lors de la synchronisation.', 'genius-reviews')); ?>';

		syncBtn.addEventListener('click', () => {
			const ajaxUrl = syncBtn.getAttribute('data-ajax');
			const nonce = syncBtn.getAttribute('data-nonce');
			if (!ajaxUrl || !nonce) {
				return;
			}

			syncBtn.disabled = true;
			syncBtn.classList.add('opacity-70', 'cursor-not-allowed');
			syncStatus.textContent = syncingLabel;

			const body = 'action=gr_sync_products&nonce=' + encodeURIComponent(nonce);

			fetch(ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
				},
				body
			})
				.then(response => response.json())
				.then(data => {
					if (!data || !data.success) {
						throw new Error('sync_failed');
					}
					const count = data.data && typeof data.data.count !== 'undefined'
						? parseInt(data.data.count, 10)
						: 0;
					const safeCount = isNaN(count) ? 0 : count;
					syncStatus.textContent = successLabel.replace('%d', safeCount);
				})
				.catch(() => {
					syncStatus.textContent = errorLabel;
				})
				.then(() => {
					syncBtn.disabled = false;
					syncBtn.classList.remove('opacity-70', 'cursor-not-allowed');
				});
		});
	}
</script>

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
