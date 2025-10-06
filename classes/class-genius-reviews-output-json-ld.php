<?php
if (! defined('ABSPATH')) exit;

/**
 * Handle JSON-LD output for SEO
 *
 * @since      1.0.0
 * @package    Genius_Reviews
 * @subpackage Genius_Reviews/classes
 */
class Genius_Reviews_Output_Json_Ld
{

    public function __construct()
    {
        add_action('wp_footer', [$this, 'output_product_jsonld'], 20);
        add_action('wp_footer', [$this, 'output_slider_jsonld'], 20, 1);
    }

    /**
     * Output Product + AggregateRating JSON-LD on product pages
     */
    public function output_product_jsonld()
    {
        if (! is_product()) return;

        global $product;
        if (! $product instanceof WC_Product) return;

        $product_id = $product->get_id();
        $avg   = get_post_meta($product_id, '_gr_avg_rating', true);
        $count = get_post_meta($product_id, '_gr_review_count', true);

        if (empty($avg) || empty($count)) return;

        $jsonld = [
            '@context'        => 'https://schema.org/',
            '@type'           => 'Product',
            '@id'             => get_permalink($product_id) . '#product',
            'name'            => $product->get_name(),
            'image'           => wp_get_attachment_url($product->get_image_id()),
            'sku'             => $product->get_sku(),
            'offers'          => [
                '@type'         => 'Offer',
                'price'         => $product->get_price(),
                'priceCurrency' => get_woocommerce_currency(),
                'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url'           => get_permalink($product_id),
            ],
            'aggregateRating' => [
                '@type'       => 'AggregateRating',
                'ratingValue' => (float) $avg,
                'reviewCount' => (int) $count,
            ],
        ];

        // Ajout des 10 derniers avis en JSON-LD ?
        // $reviews_jsonld = $this->get_reviews_jsonld( $product_id );
        // if ( ! empty( $reviews_jsonld ) ) {
        //     $jsonld['review'] = $reviews_jsonld;
        // }

        echo '<script type="application/ld+json">'
            . wp_json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            . '</script>';
    }

    public static function output_slider_jsonld($reviews)
    {
        if (empty($reviews)) return;

        $items = [];
        foreach ($reviews as $i => $review) {
            $rating = get_post_meta($review->ID, '_gr_rating', true);
            $author = get_post_meta($review->ID, '_gr_reviewer_name', true);
            $date   = get_post_meta($review->ID, '_gr_review_date', true);
            $product_id = get_post_meta($review->ID, '_gr_product_id', true);
            $product_name = get_the_title($product_id);

            $best_rating  = apply_filters('genius_reviews_best_rating', 5);
            $worst_rating = apply_filters('genius_reviews_worst_rating', 1);

            $items[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'item' => [
                    '@type' => 'Review',
                    'author' => [
                        '@type' => 'Person',
                        'name'  => $author ?: __('Client', 'genius-reviews'),
                    ],
                    'datePublished' => $date ?: get_the_date('c', $review),
                    'reviewBody' => wp_strip_all_tags($review->post_content),
                    'name' => $review->post_title,
                    'reviewRating' => [
                        '@type' => 'Rating',
                        'ratingValue' => (float) $rating,
                        'bestRating'  => $best_rating,
                        'worstRating' => $worst_rating,
                    ],
                    'itemReviewed' => [
                        '@type' => 'Product',
                        'name' => $product_name,
                        'url'  => get_permalink($product_id),
                    ],
                ],
            ];
        }

        $jsonld = [
            '@context' => 'https://schema.org',
            '@type'    => 'ItemList',
            'itemListElement' => $items,
        ];

        return wp_json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }


    /**
     * Return an array of reviews in JSON-LD format
     */
    private function get_reviews_jsonld($product_id)
    {
        $reviews = get_posts([
            'post_type'      => 'genius_review',
            'post_status'    => 'publish',
            'numberposts'    => 10,
            'post_parent'    => $product_id,
        ]);

        $items = [];
        foreach ($reviews as $review) {
            $rating = get_post_meta($review->ID, '_gr_rating', true);
            $author = get_post_meta($review->ID, '_gr_reviewer_name', true);
            $date   = get_post_meta($review->ID, '_gr_review_date', true);

            $items[] = [
                '@type'         => 'Review',
                'author'        => [
                    '@type' => 'Person',
                    'name'  => $author ?: __('Client', 'genius-reviews'),
                ],
                'datePublished' => $date ?: get_the_date('c', $review),
                'reviewBody'    => wp_strip_all_tags($review->post_content),
                'name'          => $review->post_title,
                'reviewRating'  => [
                    '@type'       => 'Rating',
                    'ratingValue' => (float) $rating,
                    'bestRating'  => 5,
                    'worstRating' => 1,
                ],
            ];
        }
        return $items;
    }
}
