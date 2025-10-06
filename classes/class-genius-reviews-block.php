<?php
if (!defined('ABSPATH')) exit;
if (!function_exists('register_block_type')) return;

class Genius_Reviews_Block {

    public static function init() {
        add_action('init', [__CLASS__, 'register_block']);
    }

    public static function register_block() {
        register_block_type('genius-reviews/grid', [
            'render_callback' => [__CLASS__, 'render_grid'],
            'attributes'      => [
                'product_id' => ['type' => 'integer', 'default' => 0],
                'limit'      => ['type' => 'integer', 'default' => 6],
            ],
        ]);
    }

    public static function render_grid($atts) {
        $atts = wp_parse_args($atts, [
            'product_id' => 0,
            'limit'      => 6,
        ]);

        ob_start();
        echo Genius_Reviews_Render::grid($atts);
        return ob_get_clean();
    }
}
