<?php
if (!defined('ABSPATH'))
    exit;

class Genius_Reviews_Shortcodes
{

    /**
     * Shortcode [genius_reviews_grid product_id="123" limit="6"] ou  [genius_reviews_grid]
     */
    public static function grid($atts = [])
    {
        global $product;

        $atts = shortcode_atts([
            'product_id' => 0,
            'limit' => 6,
            'remove_spacing' => 0,
        ], $atts, 'genius_reviews_grid');

        if (empty($atts['product_id']) && function_exists('is_product') && is_product() && $product) {
            $atts['product_id'] = $product->get_id();
        }

        ob_start();
        echo Genius_Reviews_Render::grid($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode [genius_reviews_slider limit="10"]
     */
    public static function slider($atts = [])
    {
        $atts = shortcode_atts([
            'product_id' => 0,
            'limit' => 10,
        ], $atts, 'genius_reviews_slider');

        ob_start();
        echo '<div class="gr-slider">';
        echo Genius_Reviews_Render::slider($atts);
        echo '</div>';
        return ob_get_clean();
    }


    /**
     * Shortcode [genius_reviews_badge product_id="123"]
     *
     */
    public static function badge($atts = [])
    {
        global $product;

        $atts = shortcode_atts([
            'product_id' => 0,
        ], $atts, 'genius_reviews_badge');

        if (empty($atts['product_id']) && function_exists('is_product') && is_product() && $product) {
            $atts['product_id'] = $product->get_id();
        }

        ob_start();
        echo Genius_Reviews_Render::badge($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode [genius_reviews_all limit="12"]
     * Affiche les avis produits + avis boutique avec onglets.
     */
    public static function grid_all_reviews($atts = [])
    {
        $atts = shortcode_atts([
            'limit' => 12,
            'sort' => 'date_desc',
        ], $atts, 'genius_reviews_all');

        ob_start();
        echo Genius_Reviews_Render::grid_all_reviews($atts);
        return ob_get_clean();
    }
}
