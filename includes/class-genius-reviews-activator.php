<?php

/**
 * Fired during plugin activation.
 *
 * @package    Genius_Reviews
 * @subpackage Genius_Reviews/includes
 */

if (!defined('ABSPATH')) exit;

/**
 * Handles plugin activation (network and single site).
 *
 * @since 1.0.0
 */
class Genius_Reviews_Activator
{
	/**
	 * Run activation logic for all sites if network-activated,
	 * or just for the current site otherwise.
	 *
	 * @since 1.0.0
	 */
	public static function activate($network_wide = false)
	{
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		// Vérifie la présence de WooCommerce
		if (!is_plugin_active('woocommerce/woocommerce.php')) {
			deactivate_plugins(plugin_basename(dirname(__DIR__) . '/genius-reviews.php'));
			wp_die(
				sprintf(
					wp_kses(
						__('<b>%s</b> nécessite WooCommerce. Merci d’installer et d’activer WooCommerce.', 'genius-reviews'),
						['b' => []]
					),
					'Genius Reviews'
				),
				'',
				['back_link' => true]
			);
		}

		// Mode multisite
		if (is_multisite() && $network_wide) {
			$sites = get_sites();
			foreach ($sites as $site) {
				switch_to_blog($site->blog_id);
				self::activate_single_site();
				restore_current_blog();
			}
		} else {
			// Activation classique
			self::activate_single_site();
		}
	}

	/**
	 * Activation logic specific to a single site.
	 *
	 * @since 1.0.0
	 */
	private static function activate_single_site()
	{
		// Enregistre le CPT
		if (!class_exists('Genius_Reviews_CPT')) {
			require_once plugin_dir_path(__FILE__) . 'class-genius-reviews-cpt.php';
		}
		Genius_Reviews_CPT::register();

		flush_rewrite_rules();

		// Désactiver les avis natifs WooCommerce
		remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
		remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5);

		// Crée les options par défaut pour ce site
		add_option('gr_option_active_reviews_on_product_page', 1);
		add_option('gr_option_active_badge_on_product_page', 1);
		add_option('gr_option_active_badge_on_collection_page', 1);
		add_option('gr_color_brand_custom', '#58AF59');
	}
}
