<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Genius_Reviews
 * @subpackage Genius_Reviews/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Genius_Reviews
 * @subpackage Genius_Reviews/includes
 * @author     Your Name <email@example.com>
 */
class Genius_Reviews_Activator
{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */


	public static function activate()
	{
		// Vérifie WooCommerce
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		if (! is_plugin_active('woocommerce/woocommerce.php')) {
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

		if (! class_exists('Genius_Reviews_CPT')) {
			require_once plugin_dir_path(__FILE__) . 'class-genius-reviews-cpt.php';
		}
		Genius_Reviews_CPT::register();

		flush_rewrite_rules();

		//Désactiver les avis woocommerce
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );

	}
}
