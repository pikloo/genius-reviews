<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Genius_Reviews
 * @subpackage Genius_Reviews/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Genius_Reviews
 * @subpackage Genius_Reviews/includes
 * @author     Your Name <email@example.com>
 */
class Genius_Reviews
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Genius_Reviews_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if (defined('GENIUS_REVIEWS_VERSION')) {
			$this->version = GENIUS_REVIEWS_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'genius-reviews';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->register_cpt_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Genius_Reviews_Loader. Orchestrates the hooks of the plugin.
	 * - Genius_Reviews_i18n. Defines internationalization functionality.
	 * - Genius_Reviews_Admin. Defines all hooks for the admin area.
	 * - Genius_Reviews_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-genius-reviews-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-genius-reviews-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-genius-reviews-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-genius-reviews-public.php';

		//Reviews CPT
		require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-genius-reviews-cpt.php';

		// Page Options/Import (menu + vue + ajax)
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-genius-reviews-admin-page.php';

		//Output JSON LD
		require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-genius-reviews-output-json-ld.php';

		// Render partagé
		require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-genius-reviews-render.php';

		// Gutenberg Block
		if (function_exists('register_block_type')) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-genius-reviews-block.php';
			Genius_Reviews_Block::init();
		}

		// Elementor widget
		// if (did_action('elementor/loaded')) {
		// 	require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-genius-reviews-elementor.php';
		// 	add_action('elementor/widgets/register', function ($widgets_manager) {
		// 		$widgets_manager->register(new Genius_Reviews_Elementor());
		// 	});
		// }

		// Shortcodes
		require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-genius-reviews-shortcodes.php';

		//AJAX
		require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-genius-reviews-ajax.php';

		// Query Helper
		require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-genius-reviews-query-helper.php';


		$this->loader = new Genius_Reviews_Loader();


	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Genius_Reviews_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new Genius_Reviews_i18n();

		// $this->loader->add_action('init', $plugin_i18n, 'load_plugin_textdomain', 1);


		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{

		// Assets globaux
		$plugin_admin = new Genius_Reviews_Admin($this->get_plugin_name(), $this->get_version());
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles', 10, 1);
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

		// Page Options/Import
		$this->loader->add_action('admin_menu', 'Genius_Reviews_Admin_Page', 'add_menu');
		$this->loader->add_action('wp_ajax_gr_upload_csv', 'Genius_Reviews_Ajax', 'ajax_upload_csv');
		$this->loader->add_action('wp_ajax_gr_process_chunk', 'Genius_Reviews_Ajax', 'ajax_process_chunk');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{

		$plugin_public = new Genius_Reviews_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

		new Genius_Reviews_Output_Json_Ld();

		$this->loader->add_action('wp_head', 'Genius_Reviews_Render', 'inject_brand_color');
		$this->loader->add_action('admin_head', 'Genius_Reviews_Render', 'inject_brand_color');

		$this->loader->add_action('wp_ajax_load_reviews', 'Genius_Reviews_Ajax', 'gr_load_reviews');
		$this->loader->add_action('wp_ajax_nopriv_load_reviews', 'Genius_Reviews_Ajax', 'gr_load_reviews');

		$active_reviews_on_product_page = get_option('gr_option_active_reviews_on_product_page');
		$active_badge_on_product_page = get_option('gr_option_active_badge_on_product_page');
		$active_badge_on_collection_page = get_option('gr_option_active_badge_on_collection_page');


		$this->loader->add_action('init', 'Genius_Reviews', 'register_shortcodes');


		if ($active_reviews_on_product_page != 0) {
			add_action('woocommerce_after_single_product', function () {
				global $product;
				if (!$product)
					return;


				echo Genius_Reviews_Render::grid([
					'product_id' => $product->get_id(),
					'limit' => 6,
				]);
			}, 14);
		}

		if ($active_badge_on_product_page != 0) {
			add_action('woocommerce_single_product_summary', function () {
				global $product;
				if (!$product)
					return;

				echo Genius_Reviews_Render::badge([
					'product_id' => $product->get_id(),
				]);
			}, 6);
		}


		if ($active_badge_on_collection_page != 0) {
			add_action('woocommerce_after_shop_loop_item_title', function () {
				global $product;
				if (!$product)
					return;

				echo Genius_Reviews_Render::badge([
					'product_id' => $product->get_id(),
				]);
			}, 6);
		}


	}

	private function register_cpt_hooks()
	{
		$this->loader->add_action('init', 'Genius_Reviews_CPT', 'register');
		$this->loader->add_action('add_meta_boxes', 'Genius_Reviews_CPT', 'register_metaboxes');
	}

	/**
	 * Enregistre les shortcodes du plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register_shortcodes()
	{
		add_shortcode('genius_reviews_grid', ['Genius_Reviews_Shortcodes', 'grid']);
		add_shortcode('genius_reviews_slider', ['Genius_Reviews_Shortcodes', 'slider']);
		add_shortcode('genius_reviews_badge', ['Genius_Reviews_Shortcodes', 'badge']);
		add_shortcode('genius_reviews_all', ['Genius_Reviews_Shortcodes', 'grid_all_reviews']);

	}



	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Genius_Reviews_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}
}
